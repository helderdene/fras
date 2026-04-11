import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';
import type { Ref } from 'vue';
import { onMounted, onUnmounted, ref } from 'vue';
import { show } from '@/routes/cameras';
import type { Camera } from '@/types';

export interface DashboardCamera extends Camera {
    today_recognition_count: number;
}

interface MarkerEntry {
    marker: mapboxgl.Marker;
    element: HTMLDivElement;
    popup: mapboxgl.Popup;
    camera: DashboardCamera;
}

interface UseDashboardMapOptions {
    container: Ref<HTMLElement | null>;
    accessToken: string;
    styleUrl: string;
    cameras: DashboardCamera[];
    onCameraClick?: (cameraId: number) => void;
}

export interface UseDashboardMapReturn {
    isLoaded: Ref<boolean>;
    hasError: Ref<boolean>;
    triggerPulse: (cameraId: number) => void;
    updateMarkerStatus: (
        cameraId: number,
        isOnline: boolean,
        lastSeenAt: string | null,
    ) => void;
    flyTo: (cameraId: number) => void;
    switchStyle: (styleUrl: string) => void;
    resizeMap: () => void;
    cleanup: () => void;
}

function formatRelativeTime(dateString: string | null): string {
    if (!dateString) {
        return 'Never';
    }

    const date = new Date(dateString);
    const now = new Date();
    const diffSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (diffSeconds < 60) {
        return 'Just now';
    }

    if (diffSeconds < 3600) {
        const mins = Math.floor(diffSeconds / 60);

        return `${mins} min ago`;
    }

    if (diffSeconds < 86400) {
        const hours = Math.floor(diffSeconds / 3600);

        return `${hours} hr ago`;
    }

    const days = Math.floor(diffSeconds / 86400);

    return `${days} day${days > 1 ? 's' : ''} ago`;
}

function buildPopupContent(camera: DashboardCamera): HTMLDivElement {
    const container = document.createElement('div');
    container.className = 'space-y-1.5';

    // Camera name
    const name = document.createElement('div');
    name.className = 'font-semibold text-sm';
    name.textContent = camera.name;
    container.appendChild(name);

    // Status row
    const statusRow = document.createElement('div');
    statusRow.className = 'flex items-center gap-1.5 text-xs';
    const statusDot = document.createElement('span');
    statusDot.className = `inline-block size-1.5 rounded-full ${camera.is_online ? 'bg-emerald-500' : 'bg-neutral-400'}`;
    statusRow.appendChild(statusDot);
    const statusText = document.createElement('span');
    statusText.textContent = camera.is_online ? 'Online' : 'Offline';
    statusRow.appendChild(statusText);
    container.appendChild(statusRow);

    // Last seen
    const lastSeen = document.createElement('div');
    lastSeen.className = 'text-xs text-muted-foreground';
    lastSeen.textContent = `Last seen: ${formatRelativeTime(camera.last_seen_at)}`;
    container.appendChild(lastSeen);

    // Recognition count
    const recCount = document.createElement('div');
    recCount.className = 'text-xs';
    recCount.textContent = `Recognitions today: ${camera.today_recognition_count}`;
    container.appendChild(recCount);

    // View Details link
    const link = document.createElement('a');
    link.href = show.url(camera);
    link.className =
        'mt-1 inline-block text-xs font-semibold text-primary underline underline-offset-2';
    link.textContent = 'View Details';
    container.appendChild(link);

    return container;
}

export function useDashboardMap(
    options: UseDashboardMapOptions,
): UseDashboardMapReturn {
    const isLoaded = ref(false);
    const hasError = ref(false);

    // CRITICAL: Do NOT use ref() for map instance -- Vue 3 Proxy breaks mapbox-gl internals
    let map: mapboxgl.Map | null = null;
    const markers: Map<number, MarkerEntry> = new Map();

    function addMarkers(cameras: DashboardCamera[]): void {
        if (!map) {
            return;
        }

        for (const camera of cameras) {
            const markerElement = document.createElement('div');
            markerElement.className = `camera-marker ${camera.is_online ? 'camera-marker--online' : 'camera-marker--offline'}`;

            const popupContent = buildPopupContent(camera);
            const popup = new mapboxgl.Popup({
                offset: 12,
                maxWidth: '220px',
                className: 'dashboard-map-popup',
                closeButton: true,
                closeOnClick: true,
            }).setDOMContent(popupContent);

            const marker = new mapboxgl.Marker({ element: markerElement })
                .setLngLat([camera.longitude, camera.latitude])
                .setPopup(popup)
                .addTo(map);

            // Custom click listener only emits camera-select callback
            // Mapbox native .setPopup() handles popup open/close on marker click
            markerElement.addEventListener('click', () => {
                options.onCameraClick?.(camera.id);
            });

            markers.set(camera.id, {
                marker,
                element: markerElement,
                popup,
                camera,
            });
        }
    }

    function fitBounds(cameras: DashboardCamera[]): void {
        if (!map || cameras.length === 0) {
            return;
        }

        const bounds = new mapboxgl.LngLatBounds();

        for (const camera of cameras) {
            bounds.extend([camera.longitude, camera.latitude]);
        }

        map.fitBounds(bounds, { padding: 48 });
    }

    function triggerPulse(cameraId: number, onIteration?: () => void): void {
        const entry = markers.get(cameraId);

        if (!entry) {
            return;
        }

        const pulseCount = 3;
        const interval = 1500; // ms between pulses

        for (let i = 0; i < pulseCount; i++) {
            setTimeout(() => {
                const ring = document.createElement('div');
                ring.className = 'pulse-ring';
                entry.element.appendChild(ring);

                ring.addEventListener('animationend', () => {
                    ring.remove();
                });

                if (onIteration) {
                    onIteration();
                }
            }, i * interval);
        }
    }

    function updateMarkerStatus(
        cameraId: number,
        isOnline: boolean,
        lastSeenAt: string | null,
    ): void {
        const entry = markers.get(cameraId);

        if (!entry) {
            return;
        }

        // Update marker element classes
        entry.element.classList.toggle('camera-marker--online', isOnline);
        entry.element.classList.toggle('camera-marker--offline', !isOnline);

        // Update stored camera data
        entry.camera.is_online = isOnline;
        entry.camera.last_seen_at = lastSeenAt;

        // Rebuild popup content with updated data
        const newContent = buildPopupContent(entry.camera);
        entry.popup.setDOMContent(newContent);
    }

    function flyTo(cameraId: number): void {
        const entry = markers.get(cameraId);

        if (!entry || !map) {
            return;
        }

        map.flyTo({ center: entry.marker.getLngLat(), zoom: 17 });

        // Open the popup explicitly -- getPopup().addTo(map) ensures the popup
        // is always opened when flying to a camera from the left rail click.
        // Do NOT use marker.togglePopup() which would close an already-open popup.
        const popup = entry.marker.getPopup();

        if (popup && !popup.isOpen()) {
            popup.addTo(map!);
        }
    }

    function switchStyle(styleUrl: string): void {
        if (!map) {
            return;
        }

        map.setStyle(styleUrl);
        // HTML markers persist automatically across setStyle (they are DOM elements)
    }

    function resizeMap(): void {
        map?.resize();
    }

    function cleanup(): void {
        markers.forEach(({ marker }) => marker.remove());
        markers.clear();
        map?.remove();
        map = null;
    }

    function initMap(): void {
        if (!options.container.value || !options.accessToken) {
            hasError.value = true;

            return;
        }

        mapboxgl.accessToken = options.accessToken;

        map = new mapboxgl.Map({
            container: options.container.value,
            style: options.styleUrl,
            center: [125.5406, 8.9475],
            zoom: 15,
            interactive: true,
            attributionControl: false,
        });

        map.on('load', () => {
            isLoaded.value = true;
            addMarkers(options.cameras);
            fitBounds(options.cameras);
        });

        map.on('error', () => {
            hasError.value = true;
        });
    }

    onMounted(() => {
        initMap();
    });

    onUnmounted(() => {
        cleanup();
    });

    return {
        isLoaded,
        hasError,
        triggerPulse,
        updateMarkerStatus,
        flyTo,
        switchStyle,
        resizeMap,
        cleanup,
    };
}

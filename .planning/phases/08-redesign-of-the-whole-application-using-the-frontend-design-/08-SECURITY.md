---
phase: 8
slug: redesign-of-the-whole-application-using-the-frontend-design
status: secured
threats_open: 0
asvs_level: 1
created: 2026-04-11
---

# Phase 8 — Security

> Per-phase security contract: threat register, accepted risks, and audit trail.

---

## Trust Boundaries

| Boundary | Description | Data Crossing |
|----------|-------------|---------------|
| Browser ↔ Server | Inertia SSR + client hydration | HTML, CSS, JS assets (no new data flows in this phase) |

This phase is purely visual/structural — no new trust boundaries, APIs, data flows, or authentication changes introduced. All changes are CSS custom properties, Tailwind utility classes, and Vue template restructuring.

---

## Threat Register

| Threat ID | Category | Component | Disposition | Mitigation | Status |
|-----------|----------|-----------|-------------|------------|--------|
| T-8-01 | Tampering | CSS custom properties | accept | CSS custom properties set in server-rendered HTML; no user input. Client manipulation is cosmetic only. | closed |
| T-8-02 | Tampering | CSS classes in Vue templates | accept | All styling uses Tailwind utilities, no dynamic user input in class strings. Cosmetic only. | closed |
| T-8-03 | Spoofing | AppLogo branding | accept | Branding text change is cosmetic. No security implication from changing display name. | closed |
| T-8-04 | Tampering | Page template classes | accept | No user input in class strings. All changes are static Tailwind utilities. | closed |
| T-8-06 | Information Disclosure | Welcome page | accept | Welcome page shows only product name and login/register links. No sensitive information exposed. | closed |

*Status: open · closed*
*Disposition: mitigate (implementation required) · accept (documented risk) · transfer (third-party)*

---

## Accepted Risks Log

| Risk ID | Threat Ref | Rationale | Accepted By | Date |
|---------|------------|-----------|-------------|------|
| AR-8-01 | T-8-01 | CSS custom properties are cosmetic; client-side tampering has no security impact | Phase plan design | 2026-04-11 |
| AR-8-02 | T-8-02 | Static Tailwind classes with no user input; tampering is cosmetic only | Phase plan design | 2026-04-11 |
| AR-8-03 | T-8-03 | Branding text change has no security implications | Phase plan design | 2026-04-11 |
| AR-8-04 | T-8-04 | Static template classes with no user input; cosmetic only | Phase plan design | 2026-04-11 |
| AR-8-05 | T-8-06 | Welcome page exposes only public product name and auth links | Phase plan design | 2026-04-11 |

*Accepted risks do not resurface in future audit runs.*

---

## Additional Security Note

The code review (08-REVIEW.md) identified and fixed one **critical** XSS vulnerability (CR-01) in `app.blade.php` where the `$appearance` cookie value was interpolated raw into an inline `<script>`. This was fixed in commit `0373e79` using `Js::from()` — Laravel's built-in safe JSON encoder. This fix is unrelated to the threat register (which covers phase-scoped design threats) but is documented here for completeness.

---

## Security Audit 2026-04-11

| Metric | Count |
|--------|-------|
| Threats found | 5 |
| Closed | 5 |
| Open | 0 |

---

*Phase: 08-redesign-of-the-whole-application-using-the-frontend-design-*
*Security verified: 2026-04-11*

<?php

namespace App\Enums;

enum AlertSeverity: string
{
    case Critical = 'critical';
    case Warning = 'warning';
    case Info = 'info';
    case Ignored = 'ignored';

    /**
     * Classify alert severity from camera recognition event fields.
     *
     * Priority: block-list (Critical) > refused (Warning) > stranger/nothing (Ignored) > allow-list (Info).
     */
    public static function fromEvent(int $personType, int $verifyStatus): self
    {
        if ($personType === 1) {
            return self::Critical;
        }

        if ($verifyStatus === 2) {
            return self::Warning;
        }

        if (in_array($verifyStatus, [0, 3], true)) {
            return self::Ignored;
        }

        return self::Info;
    }

    /** Whether this severity level should be broadcast to connected browsers. */
    public function shouldBroadcast(): bool
    {
        return $this !== self::Ignored;
    }

    /** Whether this severity level should trigger an audio alert. */
    public function shouldAlert(): bool
    {
        return $this === self::Critical;
    }

    /** Human-readable label for the severity level. */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}

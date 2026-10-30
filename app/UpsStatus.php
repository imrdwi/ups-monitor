<?php

namespace App;

enum UpsStatus: string
{
    case Online = 'online';
    case OnBattery = 'on_battery';
    case Bypass = 'bypass';
    case Fault = 'fault';
    case Unknown = 'unknown';

    /**
     * Map the free-text status a UPS network card displays onto a known state.
     */
    public static function fromDeviceText(string $text): self
    {
        $text = strtolower($text);

        return match (true) {
            str_contains($text, 'fault'), str_contains($text, 'alarm') => self::Fault,
            str_contains($text, 'bypass') => self::Bypass,
            str_contains($text, 'battery') => self::OnBattery,
            str_contains($text, 'normal'), str_contains($text, 'online'), str_contains($text, 'on line') => self::Online,
            default => self::Unknown,
        };
    }
}

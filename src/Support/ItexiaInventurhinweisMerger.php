<?php

declare(strict_types=1);

namespace Hwkdo\SeventhingsLaravel\Support;

class ItexiaInventurhinweisMerger
{
    public static function merge(?string $existing, string $incoming, bool $append): string
    {
        $incoming = trim($incoming);
        $existing = trim((string) $existing);

        if ($incoming === '') {
            return $existing;
        }

        if (! $append || $existing === '') {
            return $incoming;
        }

        return $existing."\n\n".$incoming;
    }
}

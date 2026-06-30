<?php

declare(strict_types=1);

namespace Hwkdo\SeventhingsLaravel\Support;

use Hwkdo\SeventhingsLaravel\Models\Asset as ItexiaAsset;

class ItexiaInventurhinweisPatchBuilder
{
    /**
     * @return array<string, string>|null
     */
    public static function build(?ItexiaAsset $itexiaAsset, ?string $inventurhinweis, bool $append): ?array
    {
        $incoming = trim((string) $inventurhinweis);
        if ($incoming === '') {
            return null;
        }

        $fieldKey = trim((string) config('seventhings-laravel.inventurhinweis_field_key', ''));
        if ($fieldKey === '') {
            return null;
        }

        $existing = null;
        if ($itexiaAsset instanceof ItexiaAsset) {
            $raw = $itexiaAsset->getRawData($fieldKey);
            $existing = is_scalar($raw) ? (string) $raw : null;
        }

        return [
            $fieldKey => ItexiaInventurhinweisMerger::merge($existing, $incoming, $append),
        ];
    }
}

<?php

namespace Hwkdo\SeventhingsLaravel\Support;

use Hwkdo\SeventhingsLaravel\Models\Asset as ItexiaAsset;

/**
 * Ermittelt die Objekt-UUID für PATCH object/{uuid} aus der Customer-API-Response.
 */
class SeventhingsObjectUuid
{
    public static function fromItexiaAsset(ItexiaAsset $itexiaAsset): ?string
    {
        if (! method_exists($itexiaAsset, 'getRawData')) {
            return null;
        }

        $row = $itexiaAsset->getRawData();
        if ($row === null) {
            return null;
        }

        $configKey = config('seventhings-laravel.object_uuid_key');
        if (is_string($configKey) && $configKey !== '') {
            $value = property_exists($row, $configKey) ? $row->{$configKey} : null;
            if ($value !== null && $value !== '') {
                return is_scalar($value) ? (string) $value : null;
            }
        }

        $uuidKeys = ['asset_uuid', 'uuid', 'object_id', 'internal_id', 'id'];
        foreach ($uuidKeys as $key) {
            $value = property_exists($row, $key) ? $row->{$key} : null;
            if ($value === null || $value === '') {
                continue;
            }
            if (in_array($key, ['asset_uuid', 'uuid', 'object_id', 'internal_id'], true) && is_string($value)) {
                return $value;
            }
            if (is_numeric($value)) {
                return (string) $value;
            }
        }

        return null;
    }
}

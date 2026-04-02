<?php

namespace Hwkdo\SeventhingsLaravel\Support;

/**
 * Wandelt API-Werte für actual_room / target_room in eine nullable Raum-ID um (0 und leer = null).
 */
final class ItexiaRoomReferenceId
{
    public static function fromApiValue(mixed $ref): ?int
    {
        if ($ref === null || $ref === false) {
            return null;
        }

        if (is_object($ref) && property_exists($ref, 'id') && is_numeric($ref->id)) {
            $id = (int) $ref->id;

            return $id !== 0 ? $id : null;
        }

        if (is_numeric($ref)) {
            $id = (int) $ref;

            return $id !== 0 ? $id : null;
        }

        return null;
    }
}

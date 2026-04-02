<?php

namespace Hwkdo\SeventhingsLaravel\Support;

use Hwkdo\SeventhingsLaravel\Client;
use Hwkdo\SeventhingsLaravel\Models\Asset as ItexiaAsset;

/**
 * Gemeinsame Logik für Itexia-Ist-Raum (actual_room): laden, Label, erwartete Raum-ID aus Suchbegriff.
 */
class ItexiaRoomInspection
{
    /**
     * @return array{
     *     barcode: string,
     *     found: bool,
     *     message: string|null,
     *     object_uuid: string|null,
     *     current_actual_room_id: int|null,
     *     current_actual_room_label: string|null,
     *     room_search_hint: string|null,
     *     expected_actual_room_id: int|null,
     *     expected_resolved: bool|null,
     *     expected_ambiguous: bool|null,
     *     matching_rooms: list<array{id: int, name: string, label: string, nummer: string}>|null,
     *     rooms_match: bool|null,
     * }
     */
    public static function inspect(Client $client, string $barcode, string $roomSearchHint): array
    {
        $barcode = trim($barcode);
        $roomSearchHint = trim($roomSearchHint);

        $itexiaAsset = $client->findAsset($barcode);
        if ($itexiaAsset === null) {
            return [
                'barcode' => $barcode,
                'found' => false,
                'message' => 'Kein Itexia-Objekt mit diesem Barcode gefunden.',
                'object_uuid' => null,
                'current_actual_room_id' => null,
                'current_actual_room_label' => null,
                'room_search_hint' => $roomSearchHint !== '' ? $roomSearchHint : null,
                'expected_actual_room_id' => null,
                'expected_resolved' => null,
                'expected_ambiguous' => null,
                'matching_rooms' => null,
                'rooms_match' => null,
            ];
        }

        $objectUuid = SeventhingsObjectUuid::fromItexiaAsset($itexiaAsset);
        $currentRoomId = self::normalizeRoomIdFromItexiaAsset($itexiaAsset);
        $currentRoomLabel = self::resolveRoomLabel($client, $currentRoomId);

        $matchingRooms = null;
        $expectedRoomId = null;
        $expectedAmbiguous = null;
        if ($roomSearchHint !== '') {
            $matchingRooms = ItexiaActualRoomResolver::findMatchingRooms($client, $roomSearchHint);
            $count = count($matchingRooms);
            $expectedAmbiguous = $count > 1;
            if ($count === 1) {
                $expectedRoomId = $matchingRooms[0]['id'];
            }
        }

        $roomsMatch = null;
        if ($roomSearchHint !== '' && $expectedRoomId !== null && $currentRoomId !== null) {
            $roomsMatch = $expectedRoomId === $currentRoomId;
        }

        return [
            'barcode' => $barcode,
            'found' => true,
            'message' => null,
            'object_uuid' => $objectUuid,
            'current_actual_room_id' => $currentRoomId,
            'current_actual_room_label' => $currentRoomLabel,
            'room_search_hint' => $roomSearchHint !== '' ? $roomSearchHint : null,
            'expected_actual_room_id' => $expectedRoomId,
            'expected_resolved' => $roomSearchHint !== '' ? ($expectedRoomId !== null) : null,
            'expected_ambiguous' => $roomSearchHint !== '' ? $expectedAmbiguous : null,
            'matching_rooms' => $matchingRooms,
            'rooms_match' => $roomsMatch,
        ];
    }

    public static function normalizeRoomIdFromItexiaAsset(ItexiaAsset $itexiaAsset): ?int
    {
        $row = $itexiaAsset->getRawData();
        if ($row === null || ! is_object($row)) {
            return null;
        }

        $raw = $row->actual_room ?? null;

        return ItexiaRoomReferenceId::fromApiValue($raw);
    }

    public static function normalizeTargetRoomIdFromItexiaAsset(ItexiaAsset $itexiaAsset): ?int
    {
        $row = $itexiaAsset->getRawData();
        if ($row === null || ! is_object($row)) {
            return null;
        }

        $raw = $row->target_room ?? null;

        return ItexiaRoomReferenceId::fromApiValue($raw);
    }

    public static function resolveRoomLabel(Client $client, ?int $roomId): ?string
    {
        if ($roomId === null) {
            return null;
        }

        try {
            $room = $client->findRaumById($roomId);
        } catch (\Throwable) {
            return null;
        }

        if ($room === null) {
            return null;
        }

        $label = $room->label ?? '';
        if (is_string($label) && trim($label) !== '') {
            return trim($label);
        }

        $name = $room->name ?? '';

        return is_string($name) && trim($name) !== '' ? trim($name) : null;
    }
}

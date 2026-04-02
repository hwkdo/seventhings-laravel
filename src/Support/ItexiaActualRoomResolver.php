<?php

namespace Hwkdo\SeventhingsLaravel\Support;

use Hwkdo\SeventhingsLaravel\Client;

/**
 * Ermittelt eine Seventhings-Raum-ID aus einem Suchbegriff (Standort / Raumname),
 * analog zur Logik beim Anlegen von Objekten (Name, Label, Nummer, Teilstring).
 *
 * Zusätzlich: Vergleich nach Entfernen aller Leerzeichen (z. B. lokales "A 366" zu Itexia-"A366").
 */
class ItexiaActualRoomResolver
{
    /**
     * Liefert genau dann eine Raum-ID, wenn der Suchbegriff **eindeutig** einem Raum entspricht.
     * Bei keinem oder mehreren Treffern: null (bei Anlage/Update nicht raten).
     */
    public static function resolveRoomIdFromSearchHint(Client $client, string $search): ?int
    {
        $matches = self::findMatchingRooms($client, $search);

        return count($matches) === 1 ? $matches[0]['id'] : null;
    }

    /**
     * Alle Seventhings-Räume, die zum Suchbegriff passen (gleiche Match-Regeln wie resolveRoomIdFromSearchHint).
     *
     * @return list<array{id: int, name: string, label: string, nummer: string}>
     */
    public static function findMatchingRooms(Client $client, string $search): array
    {
        $search = trim($search);
        if ($search === '') {
            return [];
        }

        try {
            $rooms = $client->getRaeume();
        } catch (\Throwable) {
            return [];
        }

        $searchLower = mb_strtolower($search);
        $searchCompact = self::compactForRoomMatch($search);

        $out = [];
        foreach ($rooms as $room) {
            $name = (string) ($room->name ?? '');
            $label = (string) ($room->label ?? '');
            $nummer = (string) ($room->nummer ?? '');
            if (! self::matchesRoomFields($searchLower, $searchCompact, $name, $label, $nummer)) {
                continue;
            }

            $out[] = [
                'id' => (int) $room->id,
                'name' => $name,
                'label' => $label,
                'nummer' => $nummer,
            ];
        }

        return $out;
    }

    /**
     * Kleinbuchstaben, trim, dann alle Whitespace-Zeichen entfernen (Unicode).
     */
    public static function compactForRoomMatch(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return preg_replace('/\s+/u', '', $value) ?? '';
    }

    private static function matchesRoomFields(
        string $searchLower,
        string $searchCompact,
        string $name,
        string $label,
        string $nummer,
    ): bool {
        $nameLower = mb_strtolower(trim($name));
        $labelLower = mb_strtolower(trim($label));
        $nummerLower = mb_strtolower(trim($nummer));

        if ($searchLower === $nameLower
            || $searchLower === $labelLower
            || $searchLower === $nummerLower
            || ($nameLower !== '' && mb_strpos($nameLower, $searchLower) !== false)
            || ($labelLower !== '' && mb_strpos($labelLower, $searchLower) !== false)
        ) {
            return true;
        }

        if ($searchCompact === '') {
            return false;
        }

        $nameCompact = self::compactForRoomMatch($name);
        $labelCompact = self::compactForRoomMatch($label);
        $nummerCompact = self::compactForRoomMatch($nummer);

        if ($searchCompact === $nameCompact
            || $searchCompact === $labelCompact
            || $searchCompact === $nummerCompact
        ) {
            return true;
        }

        if ($nameCompact !== '' && str_contains($nameCompact, $searchCompact)) {
            return true;
        }

        if ($labelCompact !== '' && str_contains($labelCompact, $searchCompact)) {
            return true;
        }

        return false;
    }
}

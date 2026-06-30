<?php

declare(strict_types=1);

namespace Hwkdo\SeventhingsLaravel\Services;

use Hwkdo\SeventhingsLaravel\Data\ItexiaRoomUpdateResult;
use Hwkdo\SeventhingsLaravel\Events\ItexiaAssetActualRoomUpdated;
use Hwkdo\SeventhingsLaravel\SeventhingsLaravel;
use Hwkdo\SeventhingsLaravel\Support\ItexiaInventurhinweisPatchBuilder;
use Hwkdo\SeventhingsLaravel\Support\SeventhingsObjectUuid;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;

class ItexiaRoomUpdateService
{
    public function __construct(
        private readonly SeventhingsLaravel $client,
    ) {}

    public function updateActualAndTargetRoom(
        ?string $objectUuid,
        ?string $barcode,
        int $roomId,
        ?string $inventurhinweis = null,
        bool $appendInventurhinweis = true,
    ): ItexiaRoomUpdateResult {
        $objectUuid = $objectUuid !== null ? trim($objectUuid) : '';
        $barcode = $barcode !== null ? trim($barcode) : '';
        $barcodeForCache = $barcode !== '' ? $barcode : null;

        if ($objectUuid === '' && $barcode === '') {
            return ItexiaRoomUpdateResult::failure('Bitte entweder object_uuid oder barcode angeben.');
        }

        if ($roomId < 1) {
            return ItexiaRoomUpdateResult::failure('Ungültige Raum-ID.');
        }

        $itexiaAsset = null;

        if ($objectUuid === '') {
            try {
                $itexiaAsset = $this->client->findAsset($barcode);
            } catch (Throwable $e) {
                Log::error('ItexiaRoomUpdateService find failed', ['message' => $e->getMessage()]);

                return ItexiaRoomUpdateResult::failure('Itexia-Abfrage fehlgeschlagen: '.$e->getMessage());
            }

            if ($itexiaAsset === null) {
                return ItexiaRoomUpdateResult::failure('Kein Itexia-Objekt mit diesem Barcode gefunden.');
            }

            $resolved = SeventhingsObjectUuid::fromItexiaAsset($itexiaAsset);
            if ($resolved === null || $resolved === '') {
                return ItexiaRoomUpdateResult::failure('Objekt-UUID konnte aus der Itexia-Antwort nicht ermittelt werden.');
            }

            $objectUuid = $resolved;
        } elseif ($barcode !== '') {
            try {
                $itexiaAsset = $this->client->findAsset($barcode);
            } catch (Throwable $e) {
                Log::warning('ItexiaRoomUpdateService find for inventurhinweis failed', ['message' => $e->getMessage()]);
            }
        }

        $payload = [
            'actual_room' => $roomId,
            'target_room' => $roomId,
        ];

        $inventurhinweisPatch = ItexiaInventurhinweisPatchBuilder::build($itexiaAsset, $inventurhinweis, $appendInventurhinweis);
        if ($inventurhinweisPatch !== null) {
            $payload = array_merge($payload, $inventurhinweisPatch);
        }

        try {
            $this->client->updateAsset($objectUuid, $payload);
        } catch (Throwable $e) {
            Log::error('ItexiaRoomUpdateService update failed', ['message' => $e->getMessage()]);

            return ItexiaRoomUpdateResult::failure('Raum konnte nicht gesetzt werden: '.$e->getMessage());
        }

        Event::dispatch(new ItexiaAssetActualRoomUpdated($objectUuid, $roomId, $barcodeForCache));

        return ItexiaRoomUpdateResult::success($objectUuid, $roomId);
    }
}

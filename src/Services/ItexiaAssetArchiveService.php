<?php

declare(strict_types=1);

namespace Hwkdo\SeventhingsLaravel\Services;

use Hwkdo\SeventhingsLaravel\Data\ItexiaAssetArchiveResult;
use Hwkdo\SeventhingsLaravel\Models\Asset as ItexiaAsset;
use Hwkdo\SeventhingsLaravel\SeventhingsLaravel;
use Hwkdo\SeventhingsLaravel\Support\ItexiaInventurhinweisPatchBuilder;
use Hwkdo\SeventhingsLaravel\Support\SeventhingsObjectUuid;
use Illuminate\Support\Facades\Log;
use Throwable;

class ItexiaAssetArchiveService
{
    public function __construct(
        private readonly SeventhingsLaravel $client,
    ) {}

    public function archiveByBarcode(
        ?string $objectUuid,
        ?string $barcode,
        ?string $inventurhinweis = null,
        bool $appendInventurhinweis = true,
    ): ItexiaAssetArchiveResult {
        $objectUuid = $objectUuid !== null ? trim($objectUuid) : '';
        $barcode = $barcode !== null ? trim($barcode) : '';

        if ($objectUuid === '' && $barcode === '') {
            return ItexiaAssetArchiveResult::failure('Bitte entweder object_uuid oder Itexia-ID (Barcode) angeben.');
        }

        $itexiaAsset = null;

        if ($barcode !== '') {
            try {
                $itexiaAsset = $this->client->findAsset($barcode);
            } catch (Throwable $e) {
                Log::error('ItexiaAssetArchiveService find failed', ['message' => $e->getMessage()]);

                return ItexiaAssetArchiveResult::failure('Itexia-Abfrage fehlgeschlagen: '.$e->getMessage());
            }

            if ($itexiaAsset === null) {
                return ItexiaAssetArchiveResult::failure(
                    'Kein Objekt mit dieser Itexia-ID in Seventhings eindeutig gefunden.',
                );
            }
        }

        if ($objectUuid === '') {
            if (! $itexiaAsset instanceof ItexiaAsset) {
                return ItexiaAssetArchiveResult::failure('Objekt-UUID konnte nicht ermittelt werden.');
            }

            $resolved = SeventhingsObjectUuid::fromItexiaAsset($itexiaAsset);
            if ($resolved === null || $resolved === '') {
                return ItexiaAssetArchiveResult::failure('Objekt-UUID konnte aus der Itexia-Antwort nicht ermittelt werden.');
            }

            $objectUuid = $resolved;
        }

        $inventurhinweisPatch = ItexiaInventurhinweisPatchBuilder::build($itexiaAsset, $inventurhinweis, $appendInventurhinweis);
        if ($inventurhinweisPatch !== null) {
            try {
                $this->client->updateAsset($objectUuid, $inventurhinweisPatch);
            } catch (Throwable $e) {
                Log::error('ItexiaAssetArchiveService inventurhinweis patch failed', ['message' => $e->getMessage()]);

                return ItexiaAssetArchiveResult::failure('Inventurhinweis konnte nicht gesetzt werden: '.$e->getMessage());
            }
        }

        try {
            $this->client->archiveAsset($objectUuid);
        } catch (Throwable $e) {
            Log::error('ItexiaAssetArchiveService archive failed', ['message' => $e->getMessage()]);

            return ItexiaAssetArchiveResult::failure('Archivierung fehlgeschlagen: '.$e->getMessage());
        }

        return ItexiaAssetArchiveResult::success($objectUuid);
    }
}

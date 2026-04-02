<?php

namespace Hwkdo\SeventhingsLaravel\Mcp\Tools;

use Hwkdo\SeventhingsLaravel\Client;
use Hwkdo\SeventhingsLaravel\Events\ItexiaAssetActualRoomUpdated;
use Hwkdo\SeventhingsLaravel\SeventhingsLaravel;
use Hwkdo\SeventhingsLaravel\Support\SeventhingsObjectUuid;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsOpenWorld]
class ItexiaRaumAktualisierenTool extends Tool
{
    protected string $name = 'itexia_raum_aktualisieren';

    protected string $description = 'Setzt den Ist-Raum (actual_room) eines Itexia/Seventhings-Objekts per PATCH. Entweder object_uuid oder barcode muss angegeben sein; bei barcode wird die UUID per API ermittelt. Der Assistent soll dieses Tool direkt ausführen, wenn eine Aktualisierung gewünscht ist — keine JSON-Beispiele oder manuellen API-Aufrufe an den Nutzer geben.';

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();
        if ($user === null) {
            return Response::error('Nicht authentifiziert.');
        }

        Gate::forUser($user)->authorize('manage-app-assets');

        $validated = $request->validate([
            'object_uuid' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'actual_room_id' => ['required', 'integer', 'min:1'],
        ]);

        $objectUuid = isset($validated['object_uuid']) ? trim((string) $validated['object_uuid']) : '';
        $barcode = isset($validated['barcode']) ? trim((string) $validated['barcode']) : '';
        $barcodeForCache = $barcode !== '' ? $barcode : null;
        $actualRoomId = (int) $validated['actual_room_id'];

        if ($objectUuid === '' && $barcode === '') {
            return Response::error('Bitte entweder object_uuid oder barcode angeben.');
        }

        if (! class_exists(SeventhingsLaravel::class) || ! app()->bound(SeventhingsLaravel::class)) {
            return Response::error('Seventhings/Itexia ist in dieser Umgebung nicht gebunden (Paket oder Konfiguration fehlt).');
        }

        /** @var Client $client */
        $client = app()->make(SeventhingsLaravel::class);

        if ($objectUuid === '') {
            try {
                $itexiaAsset = $client->findAsset($barcode);
            } catch (\Throwable $e) {
                Log::error('itexia_raum_aktualisieren find failed', ['message' => $e->getMessage()]);

                return Response::error('Itexia-Abfrage fehlgeschlagen: '.$e->getMessage());
            }

            if ($itexiaAsset === null) {
                return Response::error('Kein Itexia-Objekt mit diesem Barcode gefunden.');
            }

            $resolved = SeventhingsObjectUuid::fromItexiaAsset($itexiaAsset);
            if ($resolved === null || $resolved === '') {
                return Response::error('Objekt-UUID konnte aus der Itexia-Antwort nicht ermittelt werden.');
            }
            $objectUuid = $resolved;
        }

        Log::info('itexia_raum_aktualisieren patch', [
            'object_uuid_prefix' => substr($objectUuid, 0, 8).'…',
            'actual_room_id' => $actualRoomId,
        ]);

        try {
            $client->updateAsset($objectUuid, ['actual_room' => $actualRoomId]);
        } catch (\Throwable $e) {
            Log::error('itexia_raum_aktualisieren update failed', ['message' => $e->getMessage()]);

            return Response::error('actual_room konnte nicht gesetzt werden: '.$e->getMessage());
        }

        Event::dispatch(new ItexiaAssetActualRoomUpdated($objectUuid, $actualRoomId, $barcodeForCache));

        return Response::structured([
            'success' => true,
            'object_uuid' => $objectUuid,
            'actual_room_id' => $actualRoomId,
            'message' => 'Ist-Raum (actual_room) wurde in Itexia aktualisiert.',
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'object_uuid' => $schema->string()
                ->description('Seventhings-Objekt-UUID für PATCH object/{uuid}. Optional, wenn barcode gesetzt ist.')
                ->nullable(),
            'barcode' => $schema->string()
                ->description('Itexia-Barcode; wird genutzt, um die object_uuid aufzulösen, falls diese nicht übergeben wurde.')
                ->nullable(),
            'actual_room_id' => $schema->integer()
                ->description('Numerische Raum-ID in Seventhings (actual_room).')
                ->required(),
        ];
    }

    /**
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'success' => $schema->boolean()->required(),
            'object_uuid' => $schema->string()->required(),
            'actual_room_id' => $schema->integer()->required(),
            'message' => $schema->string()->required(),
        ];
    }
}

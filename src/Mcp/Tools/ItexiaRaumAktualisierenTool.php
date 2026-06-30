<?php

namespace Hwkdo\SeventhingsLaravel\Mcp\Tools;

use Hwkdo\SeventhingsLaravel\SeventhingsLaravel;
use Hwkdo\SeventhingsLaravel\Services\ItexiaRoomUpdateService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Gate;
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
        $actualRoomId = (int) $validated['actual_room_id'];

        if (! class_exists(SeventhingsLaravel::class) || ! app()->bound(SeventhingsLaravel::class)) {
            return Response::error('Seventhings/Itexia ist in dieser Umgebung nicht gebunden (Paket oder Konfiguration fehlt).');
        }

        $result = app(ItexiaRoomUpdateService::class)->updateActualAndTargetRoom(
            $objectUuid !== '' ? $objectUuid : null,
            $barcode !== '' ? $barcode : null,
            $actualRoomId,
        );

        if (! $result->success) {
            return Response::error($result->errorMessage ?? 'Raum konnte nicht gesetzt werden.');
        }

        return Response::structured([
            'success' => true,
            'object_uuid' => $result->objectUuid,
            'actual_room_id' => $result->roomId,
            'message' => 'Ist-Raum (actual_room) und Soll-Raum (target_room) wurden in Itexia aktualisiert.',
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

<?php

namespace Hwkdo\SeventhingsLaravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Nach erfolgreichem PATCH von actual_room. Optional `barcode`: Intranet-Cache-Fallback per itexia_id, wenn UUID nicht matcht.
 */
class ItexiaAssetActualRoomUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $objectUuid,
        public int $actualRoomId,
        public ?string $barcode = null,
    ) {}
}

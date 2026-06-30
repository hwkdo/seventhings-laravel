<?php

declare(strict_types=1);

namespace Hwkdo\SeventhingsLaravel\Data;

readonly class ItexiaRoomUpdateResult
{
    public function __construct(
        public bool $success,
        public ?string $objectUuid = null,
        public ?int $roomId = null,
        public ?string $errorMessage = null,
    ) {}

    public static function success(string $objectUuid, int $roomId): self
    {
        return new self(true, $objectUuid, $roomId);
    }

    public static function failure(string $errorMessage): self
    {
        return new self(false, errorMessage: $errorMessage);
    }
}

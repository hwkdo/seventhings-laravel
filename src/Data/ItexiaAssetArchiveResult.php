<?php

declare(strict_types=1);

namespace Hwkdo\SeventhingsLaravel\Data;

readonly class ItexiaAssetArchiveResult
{
    public function __construct(
        public bool $success,
        public ?string $objectUuid = null,
        public ?string $errorMessage = null,
    ) {}

    public static function success(string $objectUuid): self
    {
        return new self(true, $objectUuid);
    }

    public static function failure(string $errorMessage): self
    {
        return new self(false, errorMessage: $errorMessage);
    }
}

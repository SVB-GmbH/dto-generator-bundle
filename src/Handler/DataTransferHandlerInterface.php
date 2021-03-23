<?php

namespace SVB\DataTransfer\Handler;

use SVB\DataTransfer\DtoGenerator;
use SVB\DataTransfer\Object\DTO;

interface DataTransferHandlerInterface
{
    /**
     * Describes which object will be handled by this Data Transfer Handler.
     */
    public function handles(): string;

    /**
     * Sets the cache key for second-level caching. If this returns null, the cache is disabled for this DTO.
     * @param mixed|null $data
     */
    public function getCacheKey($data): ?string;

    /**
     * Generate DTO
     * Generates a Data Transfer Object for the given Object.
     * @param mixed|null $data
     */
    public function generate($data = null): ?DTO;

    public function setDtoGenerator(DtoGenerator $dtoGenerator): DataTransferHandlerInterface;

    /**
     * Will be executed after the dto has been build or loaded from cache to add dynamic contents (like prices)
     */
    public function afterburn(DTO $dto): DTO;
}

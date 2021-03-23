<?php

namespace SVB\DataTransfer\Handler;

use SVB\DataTransfer\DtoGenerator;
use SVB\DataTransfer\Object\DTO;

abstract class AbstractDataTransferHandler implements DataTransferHandlerInterface
{
    /** @var DtoGenerator */
    protected $dtoGenerator;

    public function getCacheKey($data): ?string
    {
        return null;
    }

    public function setDtoGenerator(DtoGenerator $dtoGenerator): DataTransferHandlerInterface
    {
        $this->dtoGenerator = $dtoGenerator;

        return $this;
    }

    /**
     * By default we don't need the afterburner for dynamic content. Overwrite it if you need to set dynamic values!
     */
    public function afterburn(DTO $dto): DTO
    {
        return $dto;
    }
}

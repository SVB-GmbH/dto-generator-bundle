<?php

namespace SVB\DataTransfer;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use SVB\DataTransfer\Exception\MissingDataTransferHandlerException;
use SVB\DataTransfer\Handler\DataTransferHandlerInterface;
use SVB\DataTransfer\Object\DTO;

/**
 * Collects all DataTransferHandlers to generate DataTransferObjects.
 */
class DtoGenerator
{
    /** @var DataTransferHandlerInterface[] */
    protected $handlers = [];

    /** @var CacheItemPoolInterface */
    private $cachePool;

    /** @var array */
    private $localCache = [];

    public function addHandler(DataTransferHandlerInterface $handler): DtoGenerator
    {
        $this->handlers[] = $handler;

        return $this;
    }

    public function setCache(CacheItemPoolInterface $cachePool): DtoGenerator
    {
        $this->cachePool = $cachePool;

        return $this;
    }

    /**
     * Generate DTO
     * Generates a DataTransferObject using the matching DataTransferHandler.
     * Optionally allows caching using the $cache argument.
     * Throws an exception if no handler matches the $dtoClass.
     * @param string $dtoClass The FQCN of the dto
     * @param mixed|null $data the data for the dto
     */
    public function generate(
        string $dtoClass,
        $data = null,
        bool $cache = false,
        int $cacheTTL = 86400
    ): ?DTO {
        foreach ($this->handlers as $handler) {
            if (!$handler instanceof DataTransferHandlerInterface || $dtoClass !== $handler->handles()) {
                continue;
            }
            $handler->setDtoGenerator($this);
            $cacheItem = null;
            if ($cache && null !== ($cacheKey = $handler->getCacheKey($data))) {
                if (array_key_exists(md5($cacheKey), $this->localCache)
                    && $this->localCache[md5($cacheKey)] instanceof DTO
                ) {
                    return $this->localCache[md5($cacheKey)];
                }
                if ($this->cachePool instanceof CacheItemPoolInterface) {
                    try {
                        $cacheItem = $this->cachePool->getItem(md5($cacheKey));
                    } catch (InvalidArgumentException $exception) {
                        $cacheItem = null;
                    }
                    if ($cacheItem instanceof CacheItemInterface && $cacheItem->isHit() && $cacheItem->get() instanceof DTO) {
                        return $handler->afterburn($cacheItem->get());
                    }
                }
            }

            $dto = $handler->generate($data);

            if ($cacheItem instanceof CacheItemInterface) {
                $cacheItem->expiresAfter($cacheTTL)->set($dto);
                $this->cachePool->save($cacheItem);
            }
            if (!empty($cacheKey)) {
                $this->localCache[md5($cacheKey)] = $dto;
            }

            return $dto instanceof DTO ? $handler->afterburn($dto) : null;
        }

        throw new MissingDataTransferHandlerException('Could not find a matching DataTransferHandler for DTO `' . $dtoClass . '`. Maybe you forgot to tag the handler service as `svb.data_transfer.handler`?');
    }
}

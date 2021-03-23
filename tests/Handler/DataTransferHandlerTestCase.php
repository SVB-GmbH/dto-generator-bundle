<?php

namespace SVB\DataTransfer\Handler;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as M;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use SVB\DataTransfer\DtoGenerator;
use SVB\DataTransfer\Object\DTO;

abstract class DataTransferHandlerTestCase extends MockeryTestCase
{
    /**
     * @param DataTransferHandlerInterface|DataTransferHandlerInterface[] $handlers
     * @param mixed|null $data
     */
    protected function dtoGeneratorReturnsCachedDTO($handlers, DTO $dto, $data = null)
    {
        $matchedHandler = null;
        if (!is_iterable($handlers)) {
            $matchedHandler = $handlers;
            $handlers = [$handlers];
        } else {
            foreach ($handlers as $handler) {
                if (is_subclass_of($dto, $handler->handles())) {
                    $matchedHandler = $handler;
                }
            }
        }

        $testTTL = 123;

        $cacheMock = M::mock(CacheItemPoolInterface::class);
        $cacheItemMock = M::mock(CacheItemInterface::class);

        $cacheMock
            ->shouldReceive('getItem')
            ->with(md5($matchedHandler->getCacheKey($data)))
            ->once()
            ->andReturn($cacheItemMock)
        ;
        $cacheItemMock->shouldReceive('isHit')->withNoArgs()->once()->andReturnTrue();
        $cacheItemMock->shouldReceive('get')->withNoArgs()->twice()->andReturn($dto);

        $generator = (new DtoGenerator())->setCache($cacheMock);
        foreach ($handlers as $handler) {
            $generator->addHandler($handler);
        }
        self::assertSame($dto, $generator->generate($matchedHandler->handles(), $data, true, $testTTL));
    }

    /**
     * @param DataTransferHandlerInterface|DataTransferHandlerInterface[] $handlers
     * @param mixed|null $data
     */
    protected function dtoGeneratorSavesDTOIntoCache($handlers, DTO $dto, $data = null)
    {
        $matchedHandler = null;
        if (!is_iterable($handlers)) {
            $matchedHandler = $handlers;
            $handlers = [$handlers];
        } else {
            foreach ($handlers as $handler) {
                if ($handler->handles() === get_class($dto)) {
                    $matchedHandler = $handler;
                }
            }
        }

        $testTTL = 123;

        $cacheMock = M::mock(CacheItemPoolInterface::class);

        $cacheItemMock = M::mock(CacheItemInterface::class);

        $cacheMock
            ->shouldReceive('getItem')
            ->with(md5($matchedHandler->getCacheKey($data)))
            ->once()
            ->andReturn($cacheItemMock)
        ;
        $cacheItemMock->shouldReceive('isHit')->withNoArgs()->once()->andReturnFalse();
        $cacheItemMock->shouldReceive('setTTL')->with($testTTL)->once()->andReturn($cacheItemMock);
        $cacheItemMock->shouldReceive('set')->with($dto)->once()->andReturn($cacheItemMock);
        $cacheItemMock->shouldReceive('save')->with($dto)->once()->andReturnTrue();

        $generator = (new DtoGenerator())->setCache($cacheMock);
        foreach ($handlers as $handler) {
            $generator->addHandler($handler);
        }
        self::assertSame($dto, $generator->generate($matchedHandler->handles(), $data, true, $testTTL));
    }

    /**
     * @param DataTransferHandlerInterface|DataTransferHandlerInterface[] $handlers
     * @param mixed|null $data
     */
    protected function useDtoGenerator($handlers, string $dtoClass, $data = null)
    {
        $matchedHandler = null;
        if (!is_iterable($handlers)) {
            $matchedHandler = $handlers;
            $handlers = [$handlers];
        } else {
            foreach ($handlers as $handler) {
                if ($handler->handles() === $dtoClass) {
                    $matchedHandler = $handler;
                }
            }
        }

        $generator = (new DtoGenerator())->setCache(M::mock(CacheItemPoolInterface::class));
        foreach ($handlers as $handler) {
            $generator->addHandler($handler);
        }

        return $generator->generate($matchedHandler->handles(), $data);
    }
}

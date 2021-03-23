<?php

namespace SVB\DataTransfer;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as M;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use SVB\DataTransfer\Exception\MissingDataTransferHandlerException;
use SVB\DataTransfer\Handler\AbstractDataTransferHandler;
use SVB\DataTransfer\Object\DTO;

/**
 * @internal
 * @covers \SVB\DataTransfer\DtoGenerator
 */
class DtoGeneratorTest extends MockeryTestCase
{
    public function testInstantiation(): void
    {
        $this->assertInstanceOf(DtoGenerator::class, new DtoGenerator());
    }

    public function testGenerateWithException(): void
    {
        $this->expectException(MissingDataTransferHandlerException::class);
        $testDtoClass = 'customDtoClassName';

        $dtoGenerator = (new DtoGenerator())->addHandler(new TestDataTransferHandler());

        $dto = $dtoGenerator->generate(TestDTO::class, new TestObject($testDtoClass));

        $this->assertSame($dto->value, $testDtoClass);

        $dtoGenerator->generate('\\No\\Valid\\ClassName', new TestObject(''));
    }

    public function testGenerateStoresDTOInCache(): void
    {
        $testDtoClass = TestDTO::class;
        $testCacheKey = '87d3c3c65d9390343d42631ba78671cf';
        $testCache = true;
        $testCacheTTL = 86400;
        $testDataTransferHandler = new TestDataTransferHandler($testDtoClass);

        $poolMock = M::mock(CacheItemPoolInterface::class);
        $itemMock = M::mock(CacheItemInterface::class);

        $poolMock
            ->shouldReceive('getItem')
            ->with($testCacheKey)
            ->once()
            ->andReturn($itemMock)
        ;
        $itemMock->shouldReceive('isHit')->withNoArgs()->once()->andReturnFalse();
        $itemMock->shouldReceive('expiresAfter')->with($testCacheTTL)->once()->andReturn($itemMock);
        $itemMock->shouldReceive('set')->with(M::type($testDtoClass))->once()->andReturn($itemMock);
        $poolMock->shouldReceive('save')->with($itemMock)->once()->andReturnNull();

        $dtoGenerator = (new DtoGenerator())
            ->setCache($poolMock)
            ->addHandler($testDataTransferHandler)
        ;
        $dtoGenerator->generate(TestDTO::class, new TestObject($testDtoClass), $testCache);
    }

    public function testGenerateReturnsSameObjectOnAllFollowUpCallsWithSameCacheKey(): void
    {
        $testDtoClass = TestDTO::class;
        $testCacheKey = '87d3c3c65d9390343d42631ba78671cf';
        $testCache = true;
        $testCacheTTL = 12345;
        $testDataTransferHandler = new TestDataTransferHandler($testDtoClass);

        $poolMock = M::mock(CacheItemPoolInterface::class);
        $itemMock = M::mock(CacheItemInterface::class);

        $poolMock
            ->shouldReceive('getItem')
            ->with($testCacheKey)
            ->once()
            ->andReturn($itemMock)
        ;
        $itemMock->shouldReceive('isHit')->withNoArgs()->once()->andReturnTrue();
        $itemMock->shouldReceive('get')->withNoArgs()->once()->andReturnFalse();
        $itemMock->shouldReceive('expiresAfter')->with($testCacheTTL)->once()->andReturn($itemMock);
        $itemMock->shouldReceive('set')->with(M::type($testDtoClass))->once()->andReturn($itemMock);
        $poolMock->shouldReceive('save')->with($itemMock)->once()->andReturnNull();

        $dtoGenerator = (new DtoGenerator())
            ->setCache($poolMock)
            ->addHandler($testDataTransferHandler)
        ;
        $i1 = $dtoGenerator->generate(TestDTO::class, new TestObject($testDtoClass), $testCache, $testCacheTTL);
        $i2 = $dtoGenerator->generate(TestDTO::class, new TestObject($testDtoClass), $testCache, $testCacheTTL);

        $this->assertSame($i1, $i2);
    }

    public function testGenerateReturnCachedItems(): void
    {
        $testDtoClass = TestDTO::class;
        $testCacheKey = '87d3c3c65d9390343d42631ba78671cf';
        $testCache = true;
        $testDataTransferHandler = new TestDataTransferHandler($testDtoClass);

        $poolMock = M::mock(CacheItemPoolInterface::class);
        $itemMock = M::mock(CacheItemInterface::class);
        $dtoMock = M::mock(TestDTO::class);

        $poolMock
            ->shouldReceive('getItem')
            ->with($testCacheKey)
            ->once()
            ->andReturn($itemMock)
        ;
        $itemMock->shouldReceive('isHit')->withNoArgs()->once()->andReturnTrue();
        $itemMock->shouldReceive('get')->withNoArgs()->twice()->andReturn($dtoMock);

        $dtoGenerator = (new DtoGenerator())
            ->setCache($poolMock)
            ->addHandler($testDataTransferHandler)
        ;
        $dtoGenerator->generate(TestDTO::class, new TestObject($testDtoClass), $testCache);

        $this->assertInstanceOf(DTO::class, $dtoMock);
    }
}

class TestDataTransferHandler extends AbstractDataTransferHandler
{
    public function handles(): string
    {
        return TestDTO::class;
    }

    public function generate($data = null): ?DTO
    {
        if (!$data instanceof TestObject) {
            return null;
        }

        $dto = new TestDTO();
        $dto->value = $data->getValue();

        return $dto;
    }

    public function getCacheKey($data): string
    {
        return 'testCacheKey';
    }
}

class TestDTO implements DTO
{
    public $value;
}

class TestObject
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}

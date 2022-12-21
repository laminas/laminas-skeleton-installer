<?php

declare(strict_types=1);

namespace LaminasTest\SkeletonInstaller;

use ArrayIterator;
use ArrayObject;
use InvalidArgumentException;
use Laminas\SkeletonInstaller\Collection;
use OutOfRangeException;
use PHPUnit\Framework\TestCase;

use function array_values;
use function strstr;
use function strtoupper;

class CollectionTest extends TestCase
{
    public function testConstructorAcceptsArray(): void
    {
        $this->assertInstanceOf(Collection::class, new Collection([]));
    }

    public function testConstructorAcceptsTraversable(): void
    {
        $this->assertInstanceOf(Collection::class, new Collection(new ArrayObject([])));
    }

    public function testFactoryAcceptsArray(): void
    {
        $this->assertInstanceOf(Collection::class, Collection::create([]));
    }

    public function testFactoryAcceptsTraversable(): void
    {
        $this->assertInstanceOf(Collection::class, Collection::create(new ArrayObject([])));
    }

    public function invalidCollections(): array
    {
        return [
            'null'       => [null],
            'false'      => [false],
            'true'       => [true],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'string'     => ['item'],
            'object'     => [(object) ['item' => 'item']],
        ];
    }

    /**
     * @dataProvider invalidCollections
     * @param mixed $items
     */
    public function testConstructorRaisesExceptionForInvalidItems($items): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Collections require arrays or Traversable objects');

        new Collection($items);
    }

    /**
     * @dataProvider invalidCollections
     * @param mixed $items
     */
    public function testFactoryRaisesExceptionForInvalidItems($items): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Collections require arrays or Traversable objects');

        Collection::create($items);
    }

    public function collectionsForArrays(): array
    {
        $array = [
            'foo' => 'bar',
            'bar' => 'baz',
        ];

        return [
            'array'    => [$array, $array],
            'iterator' => [new ArrayIterator($array), $array],
        ];
    }

    /**
     * @dataProvider collectionsForArrays
     * @param iterable<array-key, mixed> $items
     * @param array $expected
     */
    public function testToArrayCastsToArray($items, array $expected): void
    {
        $collection = Collection::create($items);
        $this->assertEquals($expected, $collection->toArray());
    }

    public function testEachAppliesCallbackToEachItem(): void
    {
        /** @var Collection<int, string> $collection */
        $collection = Collection::create([
            'item1',
            'item2',
        ]);

        $results  = [];
        $received = $collection->each(function ($item) use (&$results) {
            $results[] = strtoupper($item);
        });

        $this->assertSame($collection, $received);
        $this->assertEquals([
            'ITEM1',
            'ITEM2',
        ], $results);
    }

    public function testReduceReturnsAValueByApplyingACallbackToEachItem(): void
    {
        /** @var Collection<int, string> $collection */
        $collection = Collection::create([
            'item1',
            'item2',
        ]);

        $received = $collection->reduce(function ($accumulator, $item) {
            return $accumulator . $item . ':';
        }, ':');

        $this->assertEquals(':item1:item2:', $received);
    }

    public function testFilterCreatesANewCollectionWithOnlyValuesMatchedByTheCallback(): void
    {
        /** @var Collection<int, string> $collection */
        $collection = Collection::create([
            'item1',
            'item2',
            'item11',
            'item12',
            'item21',
            'item22',
            'item31',
            'item32',
        ]);

        $filtered = $collection->filter(function ($item): bool {
            return (bool) strstr($item, '2');
        });

        $this->assertInstanceOf(Collection::class, $filtered);

        $this->assertEquals([
            'item2',
            'item12',
            'item21',
            'item22',
            'item32',
        ], array_values($filtered->toArray()));
    }

    public function testRejectCreatesANewCollectionWithValuesNotMatchedByTheCallback(): void
    {
        /** @var Collection<int, string> $collection */
        $collection = Collection::create([
            'item1',
            'item2',
            'item11',
            'item12',
            'item21',
            'item22',
            'item31',
            'item32',
        ]);

        $filtered = $collection->reject(function ($item): bool {
            return (bool) strstr($item, '2');
        });

        $this->assertInstanceOf(Collection::class, $filtered);

        $this->assertEquals([
            'item1',
            'item11',
            'item31',
        ], array_values($filtered->toArray()));
    }

    public function testMapCreatesANewCollectionWithValuesGeneratedByTheCallback(): void
    {
        $collection = Collection::create([
            'item1',
            'item2',
            'item11',
            'item12',
            'item21',
            'item22',
            'item31',
            'item32',
        ]);

        $mapped = $collection->map(function ($item) {
            return strtoupper($item);
        });

        $this->assertInstanceOf(Collection::class, $mapped);

        $this->assertEquals([
            'ITEM1',
            'ITEM2',
            'ITEM11',
            'ITEM12',
            'ITEM21',
            'ITEM22',
            'ITEM31',
            'ITEM32',
        ], array_values($mapped->toArray()));
    }

    public function testCanInteractWithCollectionAsAnArray(): void
    {
        /** @var Collection<string, string> $collection */
        $collection = Collection::create([
            'foo' => 'bar',
        ]);

        $this->assertTrue(isset($collection['foo']));
        $this->assertEquals('bar', $collection['foo']);
        unset($collection['foo']);
        $this->assertFalse(isset($collection['foo']));
        $collection[] = 'foo';
        $this->assertEquals(['foo'], $collection->toArray());
        $collection['foo'] = 'bar';
        $this->assertEquals('bar', $collection['foo']);
        $this->assertCount(2, $collection);
    }

    public function testRetrievingValueByArrayKeyWhenKeyDoesNotExistRaisesException(): void
    {
        /** @var Collection<string, mixed> $collection */
        $collection = Collection::create([]);

        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('Offset foo does not exist');

        $collection['foo'];
    }

    public function testIsEmptyReturnsTrueForEmptyCollections(): void
    {
        $collection = Collection::create([]);
        $this->assertTrue($collection->isEmpty());
    }

    public function testIsEmptyReturnsFalseForNonEmptyCollections(): void
    {
        $collection = Collection::create(['foo']);
        $this->assertFalse($collection->isEmpty());
    }

    public function testCollectionIsIterable(): void
    {
        $collection = Collection::create([
            'item1',
            'item2',
        ]);

        $results = [];
        foreach ($collection as $item) {
            $results[] = $item;
        }

        $this->assertEquals($collection->toArray(), $results);
    }
}

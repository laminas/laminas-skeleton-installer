<?php
/**
 * @see       https://github.com/zendframework/zend-skeleton-installer for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-skeleton-installer/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\SkeletonInstaller;

use ArrayIterator;
use ArrayObject;
use InvalidArgumentException;
use OutOfRangeException;
use PHPUnit\Framework\TestCase;
use Traversable;
use Zend\SkeletonInstaller\Collection;

class CollectionTest extends TestCase
{
    public function testConstructorAcceptsArray()
    {
        $this->assertInstanceOf(Collection::class, new Collection([]));
    }

    public function testConstructorAcceptsTraversable()
    {
        $this->assertInstanceOf(Collection::class, new Collection(new ArrayObject([])));
    }

    public function testFactoryAcceptsArray()
    {
        $this->assertInstanceOf(Collection::class, Collection::create([]));
    }

    public function testFactoryAcceptsTraversable()
    {
        $this->assertInstanceOf(Collection::class, Collection::create(new ArrayObject([])));
    }

    public function invalidCollections()
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
     *
     * @param mixed $items
     */
    public function testConstructorRaisesExceptionForInvalidItems($items)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Collections require arrays or Traversable objects');

        new Collection($items);
    }

    /**
     * @dataProvider invalidCollections
     *
     * @param mixed $items
     */
    public function testFactoryRaisesExceptionForInvalidItems($items)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Collections require arrays or Traversable objects');

        Collection::create($items);
    }

    public function collectionsForArrays()
    {
        $array = [
            'foo' => 'bar',
            'bar' => 'baz',
        ];

        return [
            'array' => [$array, $array],
            'iterator' => [new ArrayIterator($array), $array],
        ];
    }

    /**
     * @dataProvider collectionsForArrays
     *
     * @param array|Traversable $items
     * @param array $expected
     */
    public function testToArrayCastsToArray($items, array $expected)
    {
        $collection = Collection::create($items);
        $this->assertEquals($expected, $collection->toArray());
    }

    public function testEachAppliesCallbackToEachItem()
    {
        $collection = Collection::create([
            'item1',
            'item2',
        ]);

        $results = [];
        $received = $collection->each(function ($item) use (&$results) {
            $results[] = strtoupper($item);
        });

        $this->assertSame($collection, $received);
        $this->assertEquals([
            'ITEM1',
            'ITEM2',
        ], $results);
    }

    public function testReduceReturnsAValueByApplyingACallbackToEachItem()
    {
        $collection = Collection::create([
            'item1',
            'item2',
        ]);

        $received = $collection->reduce(function ($accumulator, $item) {
            return $accumulator . $item . ':';
        }, ':');

        $this->assertEquals(':item1:item2:', $received);
    }

    public function testFilterCreatesANewCollectionWithOnlyValuesMatchedByTheCallback()
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

        $filtered = $collection->filter(function ($item) {
            return strstr($item, '2');
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

    public function testRejectCreatesANewCollectionWithValuesNotMatchedByTheCallback()
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

        $filtered = $collection->reject(function ($item) {
            return strstr($item, '2');
        });

        $this->assertInstanceOf(Collection::class, $filtered);

        $this->assertEquals([
            'item1',
            'item11',
            'item31',
        ], array_values($filtered->toArray()));
    }

    public function testMapCreatesANewCollectionWithValuesGeneratedByTheCallback()
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

    public function testCanInteractWithCollectionAsAnArray()
    {
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

    public function testRetrievingValueByArrayKeyWhenKeyDoesNotExistRaisesException()
    {
        $collection = Collection::create([]);

        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('Offset foo does not exist');

        $collection['foo'];
    }

    public function testIsEmptyReturnsTrueForEmptyCollections()
    {
        $collection = Collection::create([]);
        $this->assertTrue($collection->isEmpty());
    }

    public function testIsEmptyReturnsFalseForNonEmptyCollections()
    {
        $collection = Collection::create(['foo']);
        $this->assertFalse($collection->isEmpty());
    }

    public function testCollectionIsIterable()
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

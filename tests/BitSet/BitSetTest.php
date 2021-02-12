<?php declare(strict_types=1);
/*
 * This file is part of MosaicGame.
 *
 * (c) Shotaro Hama <qwert.izayoi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MosaicGame\Test\BitSet;

use AssertionError;
use BadMethodCallException;
use MosaicGame\BitSet\BitSet;
use PHPUnit\Framework\TestCase;
use function count;
use function iterator_to_array;

abstract class BitSetTest extends TestCase
{
    abstract protected static function bitSetFromString(int $size, string $string): BitSet;

    public function testToString()
    {
        $bitSet = static::bitSetFromString(8, '11101101');
        $this->assertSame('11101101', $bitSet->toString());
    }

    public function testAnd()
    {
        $a = static::bitSetFromString(8, '10110111');
        $b = static::bitSetFromString(7, '00011010');
        $c = $a->and($b);
        $this->assertSame('10110111', $a->toString());
        $this->assertSame('0011010', $b->toString());
        $this->assertSame('00010010', $c->toString());
    }

    public function testOr()
    {
        $a = static::bitSetFromString(8, '10110111');
        $b = static::bitSetFromString(7, '10011010');
        $c = $a->or($b);
        $this->assertSame('10110111', $a->toString());
        $this->assertSame('0011010', $b->toString());
        $this->assertSame('10111111', $c->toString());
    }

    public function testXor()
    {
        $a = static::bitSetFromString(8, '10110111');
        $b = static::bitSetFromString(7, '10011010');
        $c = $a->xor($b);
        $this->assertSame('10110111', $a->toString());
        $this->assertSame('0011010', $b->toString());
        $this->assertSame('10101101', $c->toString());
    }

    public function testFlip()
    {
        $a = static::bitSetFromString(8, '10110111');
        $b = $a->flip();
        $this->assertSame('10110111', $a->toString());
        $this->assertSame('01001000', $b->toString());
    }

    public function testSet()
    {
        $a = static::bitSetFromString(8, '00100100');
        $b = $a->set(0)->set(2)->set(7);
        $this->assertSame('00100100', $a->toString());
        $this->assertSame('10100101', $b->toString());
    }

    public function testSetWithTooMuchOffset()
    {
        $this->expectException(AssertionError::class);
        static::bitSetFromString(8, '00100100')->set(8);
    }

    public function testSetWithNegativeOffset()
    {
        $this->expectException(AssertionError::class);
        static::bitSetFromString(8, '00100100')->set(-1);
    }

    public function testSetAll()
    {
        $this->assertSame('11111111', static::bitSetFromString(8, '0')->setAll()->toString());
    }

    public function testClear()
    {
        $a = static::bitSetFromString(8, '11011011');
        $b = $a->clear(0)->clear(2)->clear(7);
        $this->assertSame('11011011', $a->toString());
        $this->assertSame('01011010', $b->toString());
    }

    public function testClearWithTooMuchOffset()
    {
        $this->expectException(AssertionError::class);
        static::bitSetFromString(8, '11011011')->clear(8);
    }

    public function testClearWithNegativeOffset()
    {
        $this->expectException(AssertionError::class);
        static::bitSetFromString(8, '11011011')->clear(-1);
    }

    public function testClearAll()
    {
        $this->assertSame('00000000', static::bitSetFromString(8, '10100110')->clearAll()->toString());
    }

    public function testShift()
    {
        $a = static::bitSetFromString(8, '10100110');
        $b = $a->lshift(0)->lshift(2);
        $this->assertSame('10100110', $a->toString());
        $this->assertSame('10011000', $b->toString());
    }

    public function testShiftWithNegativeAmount()
    {
        $this->expectException(AssertionError::class);
        static::bitSetFromString(8, '10100110')->lshift(-1);
    }

    public function testUnshift()
    {
        $a = static::bitSetFromString(8, '10100110');
        $b = $a->rshift(0)->rshift(2);
        $this->assertSame('10100110', $a->toString());
        $this->assertSame('00101001', $b->toString());
    }

    public function testUnshiftWithNegativeAmount()
    {
        $this->expectException(AssertionError::class);
        static::bitSetFromString(8, '10100110')->rshift(-1);
    }

    public function testEquals()
    {
        $a = static::bitSetFromString(8, '0')->set(0, 2, 5);
        $b = static::bitSetFromString(7, '0')->set(5, 0, 2);
        $c = static::bitSetFromString(8, '0')->set(1, 2, 5);
        $this->assertTrue($a->equalsTo($b));
        $this->assertFalse($a->equalsTo($c));
        $this->assertFalse($b->equalsTo($c));
    }

    public function testCount()
    {
        $bitSet = static::bitSetFromString(8, '01001100');
        $this->assertSame(3, $bitSet->count());
        $this->assertSame(3, count($bitSet));
    }

    public function testArrayAccessOffsetExists()
    {
        $bitSet = static::bitSetFromString(8, '01001100');
        $this->assertTrue(isset($bitSet[7]));
        $this->assertFalse(isset($bitSet['7']));
        $this->assertFalse(isset($bitSet[8]));
        $this->assertFalse(isset($bitSet[-1]));
    }

    public function testArrayAccessOffsetGet()
    {
        $bitSet = static::bitSetFromString(8, '01001100');
        $this->assertFalse($bitSet[0]);
        $this->assertTrue($bitSet[2]);
        $this->assertFalse($bitSet[7]);
    }

    public function testArrayAccessOffsetGetWithNegativeOffset()
    {
        $this->expectException(AssertionError::class);
        static::bitSetFromString(8, '01001100')[-1];
    }

    public function testArrayAccessOffsetGetWithTooMuchOffset()
    {
        $this->expectException(AssertionError::class);
        static::bitSetFromString(8, '01001100')[8];
    }

    public function testArrayAccessOffsetSet()
    {
        $this->expectException(BadMethodCallException::class);
        $bitSet = static::bitSetFromString(8, '01001100');
        $bitSet[1] = true;
    }

    public function testArrayAccessOffsetUnset()
    {
        $this->expectException(BadMethodCallException::class);
        $bitSet = static::bitSetFromString(8, '01001100');
        unset($bitSet[1]);
    }

    public function testIterator()
    {
        $bitSet = static::bitSetFromString(8, '01001100');
        $expected = [
            false,
            false,
            true,
            true,
            false,
            false,
            true,
            false
        ];
        $this->assertSame($expected, iterator_to_array($bitSet));
    }
}

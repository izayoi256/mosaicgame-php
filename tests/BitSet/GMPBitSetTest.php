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
use MosaicGame\BitSet\GMPBitSet;
use PHPUnit\Framework\TestCase;
use function assert_options;
use function count;
use function iterator_to_array;
use const ASSERT_EXCEPTION;

final class GMPBitSetTest extends TestCase
{
    private $originalAssertException;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalAssertException = assert_options(ASSERT_EXCEPTION);
        assert_options(ASSERT_EXCEPTION, 1);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        assert_options(ASSERT_EXCEPTION, $this->originalAssertException);
    }
    
    public function testNegativeSize()
    {
        $this->expectException(AssertionError::class);
        GMPBitSet::empty(-1);
    }

    public function testZeroSize()
    {
        $this->assertInstanceOf(GMPBitSet::class, GMPBitSet::empty(0));
    }

    public function testFromString()
    {
        $a = GMPBitSet::fromGMP(8, gmp_init('0b1'));
        $b = GMPBitSet::fromGMP(8, gmp_init('0b100010001'));
        $this->assertSame('00000001', $a->toString());
        $this->assertSame('00010001', $b->toString());
    }

    public function testToString()
    {
        $bitSet = GMPBitSet::fromGMP(8, gmp_init('0b11101101'));
        $this->assertSame('11101101', $bitSet->toString());
    }

    public function testEmpty()
    {
        $bitSet = GMPBitSet::empty(8);
        $this->assertSame('00000000', $bitSet->toString());
    }

    public function testFilled()
    {
        $bitSet = GMPBitSet::filled(8);
        $this->assertSame('11111111', $bitSet->toString());
    }

    public function testAnd()
    {
        $a = GMPBitSet::fromGMP(8, gmp_init('0b10110111'));
        $b = GMPBitSet::fromGMP(7, gmp_init('0b00011010'));
        $c = $a->and($b);
        $this->assertSame('10110111', $a->toString());
        $this->assertSame('0011010', $b->toString());
        $this->assertSame('00010010', $c->toString());
    }

    public function testOr()
    {
        $a = GMPBitSet::fromGMP(8, gmp_init('0b10110111'));
        $b = GMPBitSet::fromGMP(7, gmp_init('0b10011010'));
        $c = $a->or($b);
        $this->assertSame('10110111', $a->toString());
        $this->assertSame('0011010', $b->toString());
        $this->assertSame('10111111', $c->toString());
    }

    public function testXor()
    {
        $a = GMPBitSet::fromGMP(8, gmp_init('0b10110111'));
        $b = GMPBitSet::fromGMP(7, gmp_init('0b10011010'));
        $c = $a->xor($b);
        $this->assertSame('10110111', $a->toString());
        $this->assertSame('0011010', $b->toString());
        $this->assertSame('10101101', $c->toString());
    }

    public function testFlip()
    {
        $a = GMPBitSet::fromGMP(8, gmp_init('0b10110111'));
        $b = $a->flip();
        $this->assertSame('10110111', $a->toString());
        $this->assertSame('01001000', $b->toString());
    }

    public function testSet()
    {
        $a = GMPBitSet::fromGMP(8, gmp_init('0b00100100'));
        $b = $a->set(0)->set(2)->set(7);
        $this->assertSame('00100100', $a->toString());
        $this->assertSame('10100101', $b->toString());
    }

    public function testSetWithTooMuchOffset()
    {
        $this->expectException(AssertionError::class);
        GMPBitSet::fromGMP(8, gmp_init('0b00100100'))->set(8);
    }

    public function testSetWithNegativeOffset()
    {
        $this->expectException(AssertionError::class);
        GMPBitSet::fromGMP(8, gmp_init('0b00100100'))->set(-1);
    }

    public function testSetAll()
    {
        $this->assertSame('11111111', GMPBitSet::empty(8)->setAll()->toString());
    }

    public function testClear()
    {
        $a = GMPBitSet::fromGMP(8, gmp_init('0b11011011'));
        $b = $a->clear(0)->clear(2)->clear(7);
        $this->assertSame('11011011', $a->toString());
        $this->assertSame('01011010', $b->toString());
    }

    public function testClearWithTooMuchOffset()
    {
        $this->expectException(AssertionError::class);
        GMPBitSet::fromGMP(8, gmp_init('0b11011011'))->clear(8);
    }

    public function testClearWithNegativeOffset()
    {
        $this->expectException(AssertionError::class);
        GMPBitSet::fromGMP(8, gmp_init('0b11011011'))->clear(-1);
    }

    public function testClearAll()
    {
        $this->assertSame('00000000', GMPBitSet::filled(8)->clearAll()->toString());
    }

    public function testShift()
    {
        $a = GMPBitSet::fromGMP(8, gmp_init('0b10100110'));
        $b = $a->shift(0)->shift(2);
        $this->assertSame('10100110', $a->toString());
        $this->assertSame('10011000', $b->toString());
    }

    public function testShiftWithNegativeAmount()
    {
        $this->expectException(AssertionError::class);
        GMPBitSet::fromGMP(8, gmp_init('0b10100110'))->shift(-1);
    }

    public function testUnshift()
    {
        $a = GMPBitSet::fromGMP(8, gmp_init('0b10100110'));
        $b = $a->unshift(0)->unshift(2);
        $this->assertSame('10100110', $a->toString());
        $this->assertSame('00101001', $b->toString());
    }

    public function testUnshiftWithNegativeAmount()
    {
        $this->expectException(AssertionError::class);
        GMPBitSet::fromGMP(8, gmp_init('0b10100110'))->unshift(-1);
    }

    public function testEquals()
    {
        $a = GMPBitSet::empty(8)->set(0, 2, 5);
        $b = GMPBitSet::empty(7)->set(5, 0, 2);
        $c = GMPBitSet::empty(8)->set(1, 2, 5);
        $this->assertTrue($a->equalsTo($b));
        $this->assertFalse($a->equalsTo($c));
        $this->assertFalse($b->equalsTo($c));
    }

    public function testCount()
    {
        $bitSet = GMPBitSet::fromGMP(8, gmp_init('0b01001100'));
        $this->assertSame(3, $bitSet->count());
        $this->assertSame(3, count($bitSet));
    }

    public function testArrayAccessOffsetExists()
    {
        $bitSet = GMPBitSet::fromGMP(8, gmp_init('0b01001100'));
        $this->assertTrue(isset($bitSet[7]));
        $this->assertFalse(isset($bitSet['7']));
        $this->assertFalse(isset($bitSet[8]));
        $this->assertFalse(isset($bitSet[-1]));
    }

    public function testArrayAccessOffsetGet()
    {
        $bitSet = GMPBitSet::fromGMP(8, gmp_init('0b01001100'));
        $this->assertFalse($bitSet[0]);
        $this->assertTrue($bitSet[2]);
        $this->assertFalse($bitSet[7]);
    }

    public function testArrayAccessOffsetGetWithNegativeOffset()
    {
        $this->expectException(AssertionError::class);
        GMPBitSet::fromGMP(8, gmp_init('0b01001100'))[-1];
    }

    public function testArrayAccessOffsetGetWithTooMuchOffset()
    {
        $this->expectException(AssertionError::class);
        GMPBitSet::fromGMP(8, gmp_init('0b01001100'))[8];
    }

    public function testArrayAccessOffsetSet()
    {
        $this->expectException(BadMethodCallException::class);
        $bitSet = GMPBitSet::fromGMP(8, gmp_init('0b01001100'));
        $bitSet[1] = true;
    }

    public function testArrayAccessOffsetUnset()
    {
        $this->expectException(BadMethodCallException::class);
        $bitSet = GMPBitSet::fromGMP(8, gmp_init('0b01001100'));
        unset($bitSet[1]);
    }

    public function testIterator()
    {
        $bitSet = GMPBitSet::fromGMP(8, gmp_init('0b01001100'));
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

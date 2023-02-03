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
use MosaicGame\BitSet\BitSet;
use MosaicGame\BitSet\IntArrayBitSet;
use function assert_options;
use function implode;
use function str_repeat;
use const ASSERT_EXCEPTION;

final class IntArrayBitSetTest extends BitSetTest
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

    protected static function bitSetFromString(int $size, string $string): BitSet
    {
        return IntArrayBitSet::fromString($size, $string);
    }

    public function testNegativeSize()
    {
        $this->expectException(AssertionError::class);
        IntArrayBitSet::empty(-1);
    }

    public function testZeroSize()
    {
        $this->assertInstanceOf(IntArrayBitSet::class, IntArrayBitSet::empty(0));
    }

    public function testFilled()
    {
        $bitSet = IntArrayBitSet::filled(140);
        $this->assertSame(str_repeat('1', 140), $bitSet->toString());
    }

    public function testEmpty()
    {
        $bitSet = IntArrayBitSet::empty(140);
        $this->assertSame(str_repeat('0', 140), $bitSet->toString());
    }

    public function testFromArray()
    {
        $bitSet = IntArrayBitSet::fromArray(140, [
            0b100000000000000000000000000000000000000000000000000000000001010,
            0b100000000000000000000000000000000000000000000000000000000001111,
            0b100000000000000000000000000000000000000000000000000000000000101,
        ]);
        $expected = implode('', [
            '00000000000101',
            '100000000000000000000000000000000000000000000000000000000001111',
            '100000000000000000000000000000000000000000000000000000000001010',
        ]);
        $this->assertSame($expected, $bitSet->toString());
    }

    public function testFromString()
    {
        $bitSet = IntArrayBitSet::fromString(140,  implode('', [
            '100000000000000000000000000000000000000000000000000000000000101',
            '100000000000000000000000000000000000000000000000000000000001111',
            '100000000000000000000000000000000000000000000000000000000001010',
        ]));

        $expected = implode('', [
            '00000000000101',
            '100000000000000000000000000000000000000000000000000000000001111',
            '100000000000000000000000000000000000000000000000000000000001010',
        ]);
        $this->assertSame($expected, $bitSet->toString());
    }

    public function testSetWithMultipleInts()
    {
        $a = IntArrayBitSet::empty(70);
        $b = $a->set(0, 5, 63, 64, 69);
        $this->assertSame('0000000000000000000000000000000000000000000000000000000000000000000000', $a->toString());
        $this->assertSame('1000011000000000000000000000000000000000000000000000000000000000100001', $b->toString());
    }

    public function testClearWithMultipleInts()
    {
        $a = IntArrayBitSet::filled(70);
        $b = $a->clear(0, 5, 63, 64, 69);
        $this->assertSame('1111111111111111111111111111111111111111111111111111111111111111111111', $a->toString());
        $this->assertSame('0111100111111111111111111111111111111111111111111111111111111111011110', $b->toString());
    }

    public function testOrWithMultipleInts()
    {
        $a = IntArrayBitSet::fromString(70, '1000111110000000000000000000000000000000000000000000000000000000000011');
        $b = IntArrayBitSet::fromString(70, '0010011100000000010000000000000000000000000000000000000001000000000010');
        $c = $a->or($b);
        $this->assertSame('1000111110000000000000000000000000000000000000000000000000000000000011', $a->toString());
        $this->assertSame('0010011100000000010000000000000000000000000000000000000001000000000010', $b->toString());
        $this->assertSame('1010111110000000010000000000000000000000000000000000000001000000000011', $c->toString());
    }
}

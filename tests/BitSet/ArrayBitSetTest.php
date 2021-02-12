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
use MosaicGame\BitSet\ArrayBitSet;
use function assert_options;
use const ASSERT_EXCEPTION;

final class ArrayBitSetTest extends BitSetTest
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
        return ArrayBitSet::fromString($size, $string);
    }

    public function testNegativeSize()
    {
        $this->expectException(AssertionError::class);
        ArrayBitSet::empty(-1);
    }

    public function testZeroSize()
    {
        $this->assertInstanceOf(ArrayBitSet::class, ArrayBitSet::empty(0));
    }

    public function testFromString()
    {
        $a = ArrayBitSet::fromString(8, '1');
        $b = ArrayBitSet::fromString(8, '100010001');
        $this->assertSame('00000001', $a->toString());
        $this->assertSame('00010001', $b->toString());
    }

    public function testEmpty()
    {
        $bitSet = ArrayBitSet::empty(8);
        $this->assertSame('00000000', $bitSet->toString());
    }

    public function testFilled()
    {
        $bitSet = ArrayBitSet::filled(8);
        $this->assertSame('11111111', $bitSet->toString());
    }
}

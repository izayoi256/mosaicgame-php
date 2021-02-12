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
use MosaicGame\BitSet\GMPBitSet;
use function assert_options;
use const ASSERT_EXCEPTION;

final class GMPBitSetTest extends BitSetTest
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
        return GMPBitSet::fromGMP($size, gmp_init($string, 2));
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

    public function testFromGMP()
    {
        $a = GMPBitSet::fromGMP(8, gmp_init('0b1'));
        $b = GMPBitSet::fromGMP(8, gmp_init('0b100010001'));
        $this->assertSame('00000001', $a->toString());
        $this->assertSame('00010001', $b->toString());
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
}

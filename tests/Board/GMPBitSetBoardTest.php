<?php declare(strict_types=1);
/*
 * This file is part of MosaicGame.
 *
 * (c) Shotaro Hama <qwert.izayoi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MosaicGame\Test\Board;

use AssertionError;
use MosaicGame\Board\GMPBitSetBoard;
use PHPUnit\Framework\TestCase;
use function assert_options;
use const ASSERT_EXCEPTION;

final class GMPBitSetBoardTest extends TestCase
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

    public function testEmptyBoard()
    {
        $this->assertSame('0', GMPBitSetBoard::emptyBoard(1)->toString());
        $this->assertSame('00000', GMPBitSetBoard::emptyBoard(2)->toString());
        $this->assertSame('00000000000000', GMPBitSetBoard::emptyBoard(3)->toString());
    }

    public function testTooMuchSize()
    {
        GMPBitSetBoard::emptyBoard(7);
        $this->expectException(AssertionError::class);
        GMPBitSetBoard::emptyBoard(8);
    }

    public function testTooLessSize()
    {
        GMPBitSetBoard::emptyBoard(1);
        $this->expectException(AssertionError::class);
        GMPBitSetBoard::emptyBoard(0);
    }

    public function testGroundBoard()
    {
        $this->assertSame('1', GMPBitSetBoard::groundBoard(1)->toString());
        $this->assertSame('11110', GMPBitSetBoard::groundBoard(2)->toString());
        $this->assertSame('11111111100000', GMPBitSetBoard::groundBoard(3)->toString());
    }

    public function testNeutralBoard()
    {
        $this->assertSame('1', GMPBitSetBoard::neutralBoard(1)->toString());
        $this->assertSame('00000', GMPBitSetBoard::neutralBoard(2)->toString());
        $this->assertSame('00001000000000', GMPBitSetBoard::neutralBoard(3)->toString());
    }

    public function testFilledBoard()
    {
        $this->assertSame('1', GMPBitSetBoard::filledBoard(1)->toString());
        $this->assertSame('11111', GMPBitSetBoard::filledBoard(2)->toString());
        $this->assertSame('11111111111111', GMPBitSetBoard::filledBoard(3)->toString());
    }

    public function testCount()
    {
        $this->assertSame(8, GMPBitSetBoard::fromString(3, '11110010010011')->count());
    }

    public function testMirrorHorizontal()
    {
        $this->assertSame('01110000001100', GMPBitSetBoard::fromString(3, '11000100010010')->mirrorHorizontal()->toString());
        $this->assertSame('11110101010101', GMPBitSetBoard::fromString(3, '11110101001011')->mirrorHorizontal()->toString());
    }

    public function testFlipVertical()
    {
        $this->assertSame('01110000001100', GMPBitSetBoard::fromString(3, '00010001110010')->flipVertical()->toString());
        $this->assertSame('01010111110101', GMPBitSetBoard::fromString(3, '11110101010101')->flipVertical()->toString());
    }

    public function testFlipDiagonal()
    {
        $this->assertSame('00100101001100', GMPBitSetBoard::fromString(3, '01110000001100')->flipDiagonal()->toString());
        $this->assertSame('01110101100111', GMPBitSetBoard::fromString(3, '11110101010101')->flipDiagonal()->toString());
    }

    public function testRotate90()
    {
        $this->assertSame('01000100110010', GMPBitSetBoard::fromString(3, '01110000001100')->rotate90()->toString());
        $this->assertSame('01110101111001', GMPBitSetBoard::fromString(3, '11110101010101')->rotate90()->toString());
    }

    public function testRotate180()
    {
        $this->assertSame('00000111001100', GMPBitSetBoard::fromString(3, '01110000001100')->rotate180()->toString());
        $this->assertSame('01010111101011', GMPBitSetBoard::fromString(3, '11110101010101')->rotate180()->toString());
    }

    public function testRotate270()
    {
        $this->assertSame('10010001010010', GMPBitSetBoard::fromString(3, '01110000001100')->rotate270()->toString());
        $this->assertSame('11010111000111', GMPBitSetBoard::fromString(3, '11110101010101')->rotate270()->toString());
    }

    public function testFlip()
    {
        $this->assertSame('10001111110011', GMPBitSetBoard::fromString(3, '01110000001100')->flip()->toString());
    }

    public function testAnd()
    {
        $a = GMPBitSetBoard::fromString(3, '11100010101101');
        $b = GMPBitSetBoard::fromString(3, '01110100011011');
        $this->assertSame('01100000001001', $a->and($b)->toString());
    }

    public function testOr()
    {
        $a = GMPBitSetBoard::fromString(3, '11100010101101');
        $b = GMPBitSetBoard::fromString(3, '01110100011011');
        $this->assertSame('11110110111111', $a->or($b)->toString());
    }

    public function testXor()
    {
        $a = GMPBitSetBoard::fromString(3, '11100010101101');
        $b = GMPBitSetBoard::fromString(3, '01110100011011');
        $this->assertSame('10010110110110', $a->xor($b)->toString());
    }

    public function testEqualsTo()
    {
        $a = GMPBitSetBoard::fromString(3, '11100001001010');
        $b = GMPBitSetBoard::fromString(3, '10100001001000')->or(GMPBitSetBoard::fromString(3, '01000000000010'));
        $c = GMPBitSetBoard::fromString(3, '10100001001000');
        $this->assertTrue($a->equalsTo($b));
        $this->assertFalse($b->equalsTo($c));
        $this->assertFalse($a->equalsTo($c));
    }

    public function provide_testPromotions()
    {
        $data = [
            'promoteZero' => '00000000001000',
            'promoteOne' => '00000000010000',
            'promoteTwo' => '00000000000010',
            'promoteThree' => '00000000000100',
            'promoteFour' => '00000000000001',
            'promoteMajority' => '00000000000101',
            'promoteHalfOrMore' => '00000000000111',
        ];
        foreach ($data as $key => $expected) {
            yield $key => [$key, $expected];
        }
    }

    /**
     * @dataProvider provide_testPromotions
     */
    public function testPromotions(string $method, string $expected)
    {
        $this->assertSame($expected, GMPBitSetBoard::fromString(3, '00010011111111')->{$method}()->toString());
    }
}

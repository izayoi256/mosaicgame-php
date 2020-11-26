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

use InvalidArgumentException;
use MosaicGame\Board\ArrayBitSetBoard;
use PHPUnit\Framework\TestCase;

final class ArrayBitSetBoardTest extends TestCase
{
    private function zeroPad(string $string): string
    {
        return sprintf('%0140s', $string);
    }

    public function testEmptyBoard()
    {
        $expected = $this->zeroPad('0');
        $this->assertSame($expected, ArrayBitSetBoard::emptyBoard(1)->toString());
        $this->assertSame($expected, ArrayBitSetBoard::emptyBoard(2)->toString());
        $this->assertSame($expected, ArrayBitSetBoard::emptyBoard(3)->toString());
    }

    public function testTooMuchSize()
    {
        ArrayBitSetBoard::emptyBoard(7);
        $this->expectException(InvalidArgumentException::class);
        ArrayBitSetBoard::emptyBoard(8);
    }

    public function testTooLessSize()
    {
        ArrayBitSetBoard::emptyBoard(1);
        $this->expectException(InvalidArgumentException::class);
        ArrayBitSetBoard::emptyBoard(0);
    }

    public function testGroundBoard()
    {
        $this->assertSame($this->zeroPad('1'), ArrayBitSetBoard::groundBoard(1)->toString());
        $this->assertSame($this->zeroPad('11110'), ArrayBitSetBoard::groundBoard(2)->toString());
        $this->assertSame($this->zeroPad('11111111100000'), ArrayBitSetBoard::groundBoard(3)->toString());
    }

    public function testNeutralBoard()
    {
        $this->assertSame($this->zeroPad('1'), ArrayBitSetBoard::neutralBoard(1)->toString());
        $this->assertSame($this->zeroPad('00000'), ArrayBitSetBoard::neutralBoard(2)->toString());
        $this->assertSame($this->zeroPad('00001000000000'), ArrayBitSetBoard::neutralBoard(3)->toString());
    }

    public function testFilledBoard()
    {
        $this->assertSame($this->zeroPad('1'), ArrayBitSetBoard::filledBoard(1)->toString());
        $this->assertSame($this->zeroPad('11111'), ArrayBitSetBoard::filledBoard(2)->toString());
        $this->assertSame($this->zeroPad('11111111111111'), ArrayBitSetBoard::filledBoard(3)->toString());
    }

    public function testCount()
    {
        $this->assertSame(8, ArrayBitSetBoard::fromString(3, '11110010010011')->count());
    }

    public function testMirrorHorizontal()
    {
        $this->assertSame($this->zeroPad('01110000001100'), ArrayBitSetBoard::fromString(3, '11000100010010')->mirrorHorizontal()->toString());
        $this->assertSame($this->zeroPad('11110101010101'), ArrayBitSetBoard::fromString(3, '11110101001011')->mirrorHorizontal()->toString());
    }

    public function testFlipVertical()
    {
        $this->assertSame($this->zeroPad('01110000001100'), ArrayBitSetBoard::fromString(3, '00010001110010')->flipVertical()->toString());
        $this->assertSame($this->zeroPad('01010111110101'), ArrayBitSetBoard::fromString(3, '11110101010101')->flipVertical()->toString());
    }

    public function testFlipDiagonal()
    {
        $this->assertSame($this->zeroPad('00100101001100'), ArrayBitSetBoard::fromString(3, '01110000001100')->flipDiagonal()->toString());
        $this->assertSame($this->zeroPad('01110101100111'), ArrayBitSetBoard::fromString(3, '11110101010101')->flipDiagonal()->toString());
    }

    public function testRotate90()
    {
        $this->assertSame($this->zeroPad('01000100110010'), ArrayBitSetBoard::fromString(3, '01110000001100')->rotate90()->toString());
        $this->assertSame($this->zeroPad('01110101111001'), ArrayBitSetBoard::fromString(3, '11110101010101')->rotate90()->toString());
    }

    public function testRotate180()
    {
        $this->assertSame($this->zeroPad('00000111001100'), ArrayBitSetBoard::fromString(3, '01110000001100')->rotate180()->toString());
        $this->assertSame($this->zeroPad('01010111101011'), ArrayBitSetBoard::fromString(3, '11110101010101')->rotate180()->toString());
    }

    public function testRotate270()
    {
        $this->assertSame($this->zeroPad('10010001010010'), ArrayBitSetBoard::fromString(3, '01110000001100')->rotate270()->toString());
        $this->assertSame($this->zeroPad('11010111000111'), ArrayBitSetBoard::fromString(3, '11110101010101')->rotate270()->toString());
    }

    public function testFlip()
    {
        $this->assertSame($this->zeroPad('10001111110011'), ArrayBitSetBoard::fromString(3, '01110000001100')->flip()->toString());
    }

    public function testAnd()
    {
        $a = ArrayBitSetBoard::fromString(3, '11100010101101');
        $b = ArrayBitSetBoard::fromString(3, '01110100011011');
        $this->assertSame($this->zeroPad('01100000001001'), $a->and($b)->toString());
    }

    public function testOr()
    {
        $a = ArrayBitSetBoard::fromString(3, '11100010101101');
        $b = ArrayBitSetBoard::fromString(3, '01110100011011');
        $this->assertSame($this->zeroPad('11110110111111'), $a->or($b)->toString());
    }

    public function testXor()
    {
        $a = ArrayBitSetBoard::fromString(3, '11100010101101');
        $b = ArrayBitSetBoard::fromString(3, '01110100011011');
        $this->assertSame($this->zeroPad('10010110110110'), $a->xor($b)->toString());
    }

    public function testEqualsTo()
    {
        $a = ArrayBitSetBoard::fromString(3, '11100001001010');
        $b = ArrayBitSetBoard::fromString(3, '10100001001000')->or(ArrayBitSetBoard::fromString(3, '01000000000010'));
        $c = ArrayBitSetBoard::fromString(3, '10100001001000');
        $this->assertTrue($a->equalsTo($b));
        $this->assertFalse($b->equalsTo($c));
        $this->assertFalse($a->equalsTo($c));
    }

    public function provide_testPromotions()
    {
        $data = [
            'promoteZero' => $this->zeroPad('00000000001000'),
            'promoteOne' => $this->zeroPad('00000000010000'),
            'promoteTwo' => $this->zeroPad('00000000000010'),
            'promoteThree' => $this->zeroPad('00000000000100'),
            'promoteFour' => $this->zeroPad('00000000000001'),
            'promoteMajority' => $this->zeroPad('00000000000101'),
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
        $this->assertSame($expected, ArrayBitSetBoard::fromString(3, '00010011111111')->{$method}()->toString());
    }
}

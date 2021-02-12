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

use MosaicGame\Board\Board;
use PHPUnit\Framework\TestCase;

abstract class BoardTest extends TestCase
{
    abstract protected static function boardFromString(int $size, string $string): Board;

    public function testCount()
    {
        $this->assertSame(8, static::boardFromString(3, '11110010010011')->count());
    }

    public function testMirrorHorizontal()
    {
        $this->assertSame('01110000001100', static::boardFromString(3, '11000100010010')->mirrorHorizontal()->toString());
        $this->assertSame('11110101010101', static::boardFromString(3, '11110101001011')->mirrorHorizontal()->toString());
    }

    public function testFlipVertical()
    {
        $this->assertSame('01110000001100', static::boardFromString(3, '00010001110010')->flipVertical()->toString());
        $this->assertSame('01010111110101', static::boardFromString(3, '11110101010101')->flipVertical()->toString());
    }

    public function testFlipDiagonal()
    {
        $this->assertSame('00100101001100', static::boardFromString(3, '01110000001100')->flipDiagonal()->toString());
        $this->assertSame('01110101100111', static::boardFromString(3, '11110101010101')->flipDiagonal()->toString());
    }

    public function testRotate90()
    {
        $this->assertSame('01000100110010', static::boardFromString(3, '01110000001100')->rotate90()->toString());
        $this->assertSame('01110101111001', static::boardFromString(3, '11110101010101')->rotate90()->toString());
    }

    public function testRotate180()
    {
        $this->assertSame('00000111001100', static::boardFromString(3, '01110000001100')->rotate180()->toString());
        $this->assertSame('01010111101011', static::boardFromString(3, '11110101010101')->rotate180()->toString());
    }

    public function testRotate270()
    {
        $this->assertSame('10010001010010', static::boardFromString(3, '01110000001100')->rotate270()->toString());
        $this->assertSame('11010111000111', static::boardFromString(3, '11110101010101')->rotate270()->toString());
    }

    public function testFlip()
    {
        $this->assertSame('10001111110011', static::boardFromString(3, '01110000001100')->flip()->toString());
    }

    public function testAnd()
    {
        $a = static::boardFromString(3, '11100010101101');
        $b = static::boardFromString(3, '01110100011011');
        $this->assertSame('01100000001001', $a->and($b)->toString());
    }

    public function testOr()
    {
        $a = static::boardFromString(3, '11100010101101');
        $b = static::boardFromString(3, '01110100011011');
        $this->assertSame('11110110111111', $a->or($b)->toString());
    }

    public function testXor()
    {
        $a = static::boardFromString(3, '11100010101101');
        $b = static::boardFromString(3, '01110100011011');
        $this->assertSame('10010110110110', $a->xor($b)->toString());
    }

    public function testEqualsTo()
    {
        $a = static::boardFromString(3, '11100001001010');
        $b = static::boardFromString(3, '10100001001000')->or(static::boardFromString(3, '01000000000010'));
        $c = static::boardFromString(3, '10100001001000');
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
        $this->assertSame($expected, static::boardFromString(3, '00010011111111')->{$method}()->toString());
    }
}

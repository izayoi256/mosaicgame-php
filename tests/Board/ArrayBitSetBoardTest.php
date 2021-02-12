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
use MosaicGame\Board\ArrayBitSetBoard;
use MosaicGame\Board\Board;
use function assert_options;
use const ASSERT_EXCEPTION;

final class ArrayBitSetBoardTest extends BoardTest
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

    protected static function boardFromString(int $size, string $string): Board
    {
        return ArrayBitSetBoard::fromString($size, $string);
    }

    public function testEmptyBoard()
    {
        $this->assertSame('0', ArrayBitSetBoard::emptyBoard(1)->toString());
        $this->assertSame('00000', ArrayBitSetBoard::emptyBoard(2)->toString());
        $this->assertSame('00000000000000', ArrayBitSetBoard::emptyBoard(3)->toString());
    }

    public function testTooMuchSize()
    {
        ArrayBitSetBoard::emptyBoard(7);
        $this->expectException(AssertionError::class);
        ArrayBitSetBoard::emptyBoard(8);
    }

    public function testTooLessSize()
    {
        ArrayBitSetBoard::emptyBoard(1);
        $this->expectException(AssertionError::class);
        ArrayBitSetBoard::emptyBoard(0);
    }

    public function testGroundBoard()
    {
        $this->assertSame('1', ArrayBitSetBoard::groundBoard(1)->toString());
        $this->assertSame('11110', ArrayBitSetBoard::groundBoard(2)->toString());
        $this->assertSame('11111111100000', ArrayBitSetBoard::groundBoard(3)->toString());
    }

    public function testNeutralBoard()
    {
        $this->assertSame('1', ArrayBitSetBoard::neutralBoard(1)->toString());
        $this->assertSame('00000', ArrayBitSetBoard::neutralBoard(2)->toString());
        $this->assertSame('00001000000000', ArrayBitSetBoard::neutralBoard(3)->toString());
    }

    public function testFilledBoard()
    {
        $this->assertSame('1', ArrayBitSetBoard::filledBoard(1)->toString());
        $this->assertSame('11111', ArrayBitSetBoard::filledBoard(2)->toString());
        $this->assertSame('11111111111111', ArrayBitSetBoard::filledBoard(3)->toString());
    }
}

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
use MosaicGame\Board\Board;
use MosaicGame\Board\IntegerBoard;
use function assert_options;
use const ASSERT_EXCEPTION;

final class IntegerBoardTest extends BoardTest
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
        return IntegerBoard::fromString($size, $string);
    }

    public function testEmptyBoard()
    {
        $this->assertSame('0', IntegerBoard::emptyBoard(1)->toString());
        $this->assertSame('00000', IntegerBoard::emptyBoard(2)->toString());
        $this->assertSame('00000000000000', IntegerBoard::emptyBoard(3)->toString());
    }

    public function testTooMuchSize()
    {
        IntegerBoard::emptyBoard(5);
        $this->expectException(AssertionError::class);
        IntegerBoard::emptyBoard(6);
    }

    public function testTooLessSize()
    {
        IntegerBoard::emptyBoard(1);
        $this->expectException(AssertionError::class);
        IntegerBoard::emptyBoard(0);
    }

    public function testGroundBoard()
    {
        $this->assertSame('1', IntegerBoard::groundBoard(1)->toString());
        $this->assertSame('11110', IntegerBoard::groundBoard(2)->toString());
        $this->assertSame('11111111100000', IntegerBoard::groundBoard(3)->toString());
    }

    public function testNeutralBoard()
    {
        $this->assertSame('1', IntegerBoard::neutralBoard(1)->toString());
        $this->assertSame('00000', IntegerBoard::neutralBoard(2)->toString());
        $this->assertSame('00001000000000', IntegerBoard::neutralBoard(3)->toString());
    }

    public function testFilledBoard()
    {
        $this->assertSame('1', IntegerBoard::filledBoard(1)->toString());
        $this->assertSame('11111', IntegerBoard::filledBoard(2)->toString());
        $this->assertSame('11111111111111', IntegerBoard::filledBoard(3)->toString());
    }
}

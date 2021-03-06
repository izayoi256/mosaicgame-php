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
use MosaicGame\Board\GMPBoard;
use function assert_options;
use const ASSERT_EXCEPTION;

final class GMPBoardTest extends BoardTest
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
        return GMPBoard::fromString($size, $string);
    }

    public function testEmptyBoard()
    {
        $this->assertSame('0', GMPBoard::emptyBoard(1)->toString());
        $this->assertSame('00000', GMPBoard::emptyBoard(2)->toString());
        $this->assertSame('00000000000000', GMPBoard::emptyBoard(3)->toString());
    }

    public function testTooMuchSize()
    {
        GMPBoard::emptyBoard(7);
        $this->expectException(AssertionError::class);
        GMPBoard::emptyBoard(8);
    }

    public function testTooLessSize()
    {
        GMPBoard::emptyBoard(1);
        $this->expectException(AssertionError::class);
        GMPBoard::emptyBoard(0);
    }

    public function testGroundBoard()
    {
        $this->assertSame('1', GMPBoard::groundBoard(1)->toString());
        $this->assertSame('11110', GMPBoard::groundBoard(2)->toString());
        $this->assertSame('11111111100000', GMPBoard::groundBoard(3)->toString());
    }

    public function testNeutralBoard()
    {
        $this->assertSame('1', GMPBoard::neutralBoard(1)->toString());
        $this->assertSame('00000', GMPBoard::neutralBoard(2)->toString());
        $this->assertSame('00001000000000', GMPBoard::neutralBoard(3)->toString());
    }

    public function testFilledBoard()
    {
        $this->assertSame('1', GMPBoard::filledBoard(1)->toString());
        $this->assertSame('11111', GMPBoard::filledBoard(2)->toString());
        $this->assertSame('11111111111111', GMPBoard::filledBoard(3)->toString());
    }
}

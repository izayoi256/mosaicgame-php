<?php declare(strict_types=1);
/*
 * This file is part of MosaicGame.
 *
 * (c) Shotaro Hama <qwert.izayoi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MosaicGame\Test\Game;

use MosaicGame\Exceptions;
use MosaicGame\Game\Game;
use MosaicGame\Game\Move\Move;
use MosaicGame\Game\OneToOneGame;
use PHPUnit\Framework\TestCase;
use function array_map;

abstract class OneToOneGameTest extends TestCase
{
    abstract protected static function createGame(int $size): OneToOneGame;
    
    abstract protected static function moveFromOffset(int $offset): Move;

    protected static function makeMove(Game $game, int ...$moveOffsets): void
    {
        foreach ($moveOffsets as $moveOffset) {
            $game->makeMove(static::moveFromOffset($moveOffset));
        }
    }

    protected static function movesToOffsets(array $moves): array
    {
        return array_map(static function (Move $move) {
            return $move->toOffset();
        }, $moves);
    }

    public function testCreate()
    {
        $game = static::createGame(3);
        $this->assertSame('00000000000000', $game->firstBoard()->toString());
        $this->assertSame('00000000000000', $game->secondBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());
    }

    public function testMakeMove()
    {
        $game = static::createGame(3);
        $this->assertSame('00000000000000', $game->firstBoard()->toString());
        $this->assertSame('00000000000000', $game->secondBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());

        static::makeMove($game, 13);
        $this->assertSame('10000000000000', $game->firstBoard()->toString());
        $this->assertSame('00000000000000', $game->secondBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());

        static::makeMove($game, 12);
        $this->assertSame('10000000000000', $game->firstBoard()->toString());
        $this->assertSame('01000000000000', $game->secondBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());

        static::makeMove($game, 8);
        $this->assertSame('10000100000000', $game->firstBoard()->toString());
        $this->assertSame('01000000000000', $game->secondBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());
    }

    public function testMakeMoveWithIllegalMove()
    {
        $game = static::createGame(3);
        static::makeMove($game, 13, 12, 8);
        $this->assertFalse($game->isLegalMove(static::moveFromOffset(9)));
        $this->expectException(Exceptions\CouldNotMakeMoveException::class);
        static::makeMove($game, 9);
    }

    public function testMakeMoveThenChain()
    {
        $game = static::createGame(3);
        static::makeMove($game, 13, 5, 12, 7, 10);
        $this->assertSame('11010000010000', $game->firstBoard()->toString());
        $this->assertSame('00000010100000', $game->secondBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());
    }

    public function testLegalMoves()
    {
        $game = static::createGame(3);

        $this->assertSame([
            5,
            6,
            7,
            8,
            10,
            11,
            12,
            13,
        ], $this->movesToOffsets($game->legalMoves()));
    }

    public function testUndo()
    {
        $game = static::createGame(3);
        static::makeMove($game, 13, 5, 12, 7, 10);
        $game->undo();
        $this->assertSame('11000000000000', $game->firstBoard()->toString());
        $this->assertSame('00000010100000', $game->secondBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());

        $this->assertSame([13, 5, 12, 7], $this->movesToOffsets($game->moves()));
        $this->assertSame(4, $game->movesMade());
    }

    public function testNotUndoable()
    {
        $game = static::createGame(3);
        static::makeMove($game, 13, 5, 12, 7, 10);
        $game->undo();
        $game->undo();
        $game->undo();
        $game->undo();
        $game->undo();
        $this->expectException(Exceptions\CouldNotUndoException::class);
        $game->undo();
    }

    public function testRedo()
    {
        $game = static::createGame(3);
        static::makeMove($game, 13, 5, 12, 7, 10);
        $game->undo();
        $game->undo();
        $this->assertSame('11000000000000', $game->firstBoard()->toString());
        $this->assertSame('00000000100000', $game->secondBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());
        $game->redo();
        $this->assertSame('11000000000000', $game->firstBoard()->toString());
        $this->assertSame('00000010100000', $game->secondBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());

        $this->assertSame([13, 5, 12, 7], $this->movesToOffsets($game->moves()));
        $this->assertSame(4, $game->movesMade());
    }

    public function testNotRedoable()
    {
        $game = static::createGame(3);
        static::makeMove($game, 13, 5, 12, 7, 10);
        $game->undo();
        $game->redo();
        $this->expectException(Exceptions\CouldNotRedoException::class);
        $game->redo();
    }
}

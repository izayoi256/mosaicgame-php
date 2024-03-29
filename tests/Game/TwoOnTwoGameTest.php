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
use MosaicGame\Game\TwoOnTwoGame;
use PHPUnit\Framework\TestCase;

abstract class TwoOnTwoGameTest extends TestCase
{
    abstract protected static function createGame(int $size): TwoOnTwoGame;
    
    abstract protected static function moveFromOffset(int $offset): Move;

    protected static function makeMove(Game $game, int ...$moveOffsets): void
    {
        foreach ($moveOffsets as $moveOffset) {
            $game->makeMove(static::moveFromOffset($moveOffset));
        }
    }

    protected static function movesToOffsets(array $moves): array
    {
        return \array_map(static function (Move $move) {
            return $move->toOffset();
        }, $moves);
    }

    public function testPiecesPerPlayer()
    {
        $this->assertSame(3, static::createGame(3)->piecesPerPlayer());
        $this->assertSame(8, static::createGame(4)->piecesPerPlayer());
        $this->assertSame(14, static::createGame(5)->piecesPerPlayer());
        $this->assertSame(23, static::createGame(6)->piecesPerPlayer());
        $this->assertSame(35, static::createGame(7)->piecesPerPlayer());
    }

    public function testPieces()
    {
        $game = static::createGame(4);
        static::makeMove($game, 28, 25, 29, 14, 24, 21, 20, 23, 27, 15, 16);
        $this->assertSame(5, $game->firstPlacedPieces());
        $this->assertSame(4, $game->secondPlacedPieces());
        $this->assertSame(3, $game->thirdPlacedPieces());
        $this->assertSame(2, $game->fourthPlacedPieces());
        $this->assertSame(3, $game->firstRemainingPieces());
        $this->assertSame(4, $game->secondRemainingPieces());
        $this->assertSame(5, $game->thirdRemainingPieces());
        $this->assertSame(6, $game->fourthRemainingPieces());
    }

    public function testCreate()
    {
        $game = static::createGame(3);
        $this->assertSame('00000000000000', $game->firstBoard()->toString());
        $this->assertSame('00000000000000', $game->secondBoard()->toString());
        $this->assertSame('00000000000000', $game->thirdBoard()->toString());
        $this->assertSame('00000000000000', $game->fourthBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());
    }

    public function testMakeMove()
    {
        $game = static::createGame(3);
        $this->assertSame('00000000000000', $game->firstBoard()->toString());
        $this->assertSame('00000000000000', $game->secondBoard()->toString());
        $this->assertSame('00000000000000', $game->thirdBoard()->toString());
        $this->assertSame('00000000000000', $game->fourthBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());

        static::makeMove($game, 13);
        $this->assertSame('10000000000000', $game->firstBoard()->toString());
        $this->assertSame('00000000000000', $game->secondBoard()->toString());
        $this->assertSame('00000000000000', $game->thirdBoard()->toString());
        $this->assertSame('00000000000000', $game->fourthBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());

        static::makeMove($game, 12);
        $this->assertSame('10000000000000', $game->firstBoard()->toString());
        $this->assertSame('01000000000000', $game->secondBoard()->toString());
        $this->assertSame('00000000000000', $game->thirdBoard()->toString());
        $this->assertSame('00000000000000', $game->fourthBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());

        static::makeMove($game, 8);
        $this->assertSame('10000000000000', $game->firstBoard()->toString());
        $this->assertSame('01000000000000', $game->secondBoard()->toString());
        $this->assertSame('00000100000000', $game->thirdBoard()->toString());
        $this->assertSame('00000000000000', $game->fourthBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());

        static::makeMove($game, 10);
        $this->assertSame('10000000000000', $game->firstBoard()->toString());
        $this->assertSame('01000000000000', $game->secondBoard()->toString());
        $this->assertSame('00000100000000', $game->thirdBoard()->toString());
        $this->assertSame('00010000000000', $game->fourthBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());

        static::makeMove($game, 11);
        $this->assertSame('10100000000000', $game->firstBoard()->toString());
        $this->assertSame('01000000000000', $game->secondBoard()->toString());
        $this->assertSame('00000100000000', $game->thirdBoard()->toString());
        $this->assertSame('00010000000000', $game->fourthBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());
    }

    public function testMakeMoveWithIllegalMove()
    {
        $game = static::createGame(3);
        static::makeMove($game, 13, 12, 8, 10, 11);
        $this->assertFalse($game->isLegalMove(static::moveFromOffset(9)));
        $this->expectException(Exceptions\CouldNotMakeMoveException::class);
        static::makeMove($game, 9);
    }

    public function testMakeMoveThenChain()
    {
        $game = static::createGame(3);
        static::makeMove($game, 13, 5, 6, 10, 12);
        $this->assertSame('11000000010000', $game->firstBoard()->toString());
        $this->assertSame('00000000100000', $game->secondBoard()->toString());
        $this->assertSame('00000001000000', $game->thirdBoard()->toString());
        $this->assertSame('00010000000000', $game->fourthBoard()->toString());
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
        static::makeMove($game, 13, 5, 6, 10, 12);
        $game->undo();
        $this->assertSame('10000000000000', $game->firstBoard()->toString());
        $this->assertSame('00000000100000', $game->secondBoard()->toString());
        $this->assertSame('00000001000000', $game->thirdBoard()->toString());
        $this->assertSame('00010000000000', $game->fourthBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());

        $this->assertSame([13, 5, 6, 10], $this->movesToOffsets($game->moves()));
        $this->assertSame(4, $game->movesMade());
    }

    public function testNotUndoable()
    {
        $game = static::createGame(3);
        static::makeMove($game, 13, 5, 6, 10, 12);
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
        static::makeMove($game, 13, 5, 6, 10, 12);
        $game->undo();
        $game->undo();
        $this->assertSame('10000000000000', $game->firstBoard()->toString());
        $this->assertSame('00000000100000', $game->secondBoard()->toString());
        $this->assertSame('00000001000000', $game->thirdBoard()->toString());
        $this->assertSame('00000000000000', $game->fourthBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());
        $game->redo();
        $this->assertSame('10000000000000', $game->firstBoard()->toString());
        $this->assertSame('00000000100000', $game->secondBoard()->toString());
        $this->assertSame('00000001000000', $game->thirdBoard()->toString());
        $this->assertSame('00010000000000', $game->fourthBoard()->toString());
        $this->assertSame('00001000000000', $game->neutralBoard()->toString());

        $this->assertSame([13, 5, 6, 10], $this->movesToOffsets($game->moves()));
        $this->assertSame(4, $game->movesMade());
    }

    public function testNotRedoable()
    {
        $game = static::createGame(3);
        static::makeMove($game, 13, 5, 6, 10, 12);
        $game->undo();
        $game->redo();
        $this->expectException(Exceptions\CouldNotRedoException::class);
        $game->redo();
    }
}

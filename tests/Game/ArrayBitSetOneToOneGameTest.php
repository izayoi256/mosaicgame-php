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
use MosaicGame\Game\ArrayBitSetOneToOneGame;
use MosaicGame\Game\Game;
use MosaicGame\Game\Move\ArrayBitSetMove;
use MosaicGame\Game\Move\Move;
use PHPUnit\Framework\TestCase;
use function array_map;

final class ArrayBitSetOneToOneGameTest extends TestCase
{
    private function zeroPad(string $string): string
    {
        return sprintf('%0140s', $string);
    }

    private function makeMove(Game $game, int ...$moveOffsets): void
    {
        foreach ($moveOffsets as $moveOffset) {
            $game->makeMove(ArrayBitSetMove::fromOffset($moveOffset));
        }
    }

    private function movesToOffsets(array $moves): array
    {
        return array_map(static function (Move $move) {
            return $move->toOffset();
        }, $moves);
    }

    public function testCreate()
    {
        $game = ArrayBitSetOneToOneGame::create(3);
        $this->assertSame($this->zeroPad('00000000000000'), $game->firstBoard()->toString());
        $this->assertSame($this->zeroPad('00000000000000'), $game->secondBoard()->toString());
        $this->assertSame($this->zeroPad('00001000000000'), $game->neutralBoard()->toString());
    }

    public function testMakeMove()
    {
        $game = ArrayBitSetOneToOneGame::create(3);
        $this->assertSame($this->zeroPad('00000000000000'), $game->firstBoard()->toString());
        $this->assertSame($this->zeroPad('00000000000000'), $game->secondBoard()->toString());
        $this->assertSame($this->zeroPad('00001000000000'), $game->neutralBoard()->toString());

        $this->makeMove($game, 13);
        $this->assertSame($this->zeroPad('10000000000000'), $game->firstBoard()->toString());
        $this->assertSame($this->zeroPad('00000000000000'), $game->secondBoard()->toString());
        $this->assertSame($this->zeroPad('00001000000000'), $game->neutralBoard()->toString());

        $this->makeMove($game, 12);
        $this->assertSame($this->zeroPad('10000000000000'), $game->firstBoard()->toString());
        $this->assertSame($this->zeroPad('01000000000000'), $game->secondBoard()->toString());
        $this->assertSame($this->zeroPad('00001000000000'), $game->neutralBoard()->toString());

        $this->makeMove($game, 8);
        $this->assertSame($this->zeroPad('10000100000000'), $game->firstBoard()->toString());
        $this->assertSame($this->zeroPad('01000000000000'), $game->secondBoard()->toString());
        $this->assertSame($this->zeroPad('00001000000000'), $game->neutralBoard()->toString());
    }

    public function testMakeMoveWithIllegalMove()
    {
        $game = ArrayBitSetOneToOneGame::create(3);
        $this->makeMove($game, 13, 12, 8);
        $this->assertFalse($game->isLegalMove(ArrayBitSetMove::fromOffset(9)));
        $this->expectException(Exceptions\CouldNotMakeMoveException::class);
        $this->makeMove($game, 9);
    }

    public function testMakeMoveThenChain()
    {
        $game = ArrayBitSetOneToOneGame::create(3);
        $this->makeMove($game, 13, 5, 12, 7, 10);
        $this->assertSame($this->zeroPad('11010000010000'), $game->firstBoard()->toString());
        $this->assertSame($this->zeroPad('00000010100000'), $game->secondBoard()->toString());
        $this->assertSame($this->zeroPad('00001000000000'), $game->neutralBoard()->toString());
    }

    public function testLegalMoves()
    {
        $game = ArrayBitSetOneToOneGame::create(3);

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
        $game = ArrayBitSetOneToOneGame::create(3);
        $this->makeMove($game, 13, 5, 12, 7, 10);
        $game->undo();
        $this->assertSame($this->zeroPad('11000000000000'), $game->firstBoard()->toString());
        $this->assertSame($this->zeroPad('00000010100000'), $game->secondBoard()->toString());
        $this->assertSame($this->zeroPad('00001000000000'), $game->neutralBoard()->toString());

        $this->assertSame([13, 5, 12, 7], $this->movesToOffsets($game->moves()));
        $this->assertSame(4, $game->movesMade());
    }

    public function testNotUndoable()
    {
        $game = ArrayBitSetOneToOneGame::create(3);
        $this->makeMove($game, 13, 5, 12, 7, 10);
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
        $game = ArrayBitSetOneToOneGame::create(3);
        $this->makeMove($game, 13, 5, 12, 7, 10);
        $game->undo();
        $game->undo();
        $this->assertSame($this->zeroPad('11000000000000'), $game->firstBoard()->toString());
        $this->assertSame($this->zeroPad('00000000100000'), $game->secondBoard()->toString());
        $this->assertSame($this->zeroPad('00001000000000'), $game->neutralBoard()->toString());
        $game->redo();
        $this->assertSame($this->zeroPad('11000000000000'), $game->firstBoard()->toString());
        $this->assertSame($this->zeroPad('00000010100000'), $game->secondBoard()->toString());
        $this->assertSame($this->zeroPad('00001000000000'), $game->neutralBoard()->toString());

        $this->assertSame([13, 5, 12, 7], $this->movesToOffsets($game->moves()));
        $this->assertSame(4, $game->movesMade());
    }

    public function testNotRedoable()
    {
        $game = ArrayBitSetOneToOneGame::create(3);
        $this->makeMove($game, 13, 5, 12, 7, 10);
        $game->undo();
        $game->redo();
        $this->expectException(Exceptions\CouldNotRedoException::class);
        $game->redo();
    }
}

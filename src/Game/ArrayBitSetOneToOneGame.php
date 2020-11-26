<?php declare(strict_types=1);
/*
 * This file is part of MosaicGame.
 *
 * (c) Shotaro Hama <qwert.izayoi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MosaicGame\Game;

use MosaicGame\Board\ArrayBitSetBoard;
use MosaicGame\Board\Board;
use MosaicGame\Exceptions;
use MosaicGame\Exceptions\CouldNotMakeMoveException;
use MosaicGame\Game\Move\ArrayBitSetMove;
use MosaicGame\Game\Move\Move;
use function array_shift;
use function array_slice;
use function count;
use function intdiv;

final class ArrayBitSetOneToOneGame implements OneToOneGame
{
    use GameTrait;

    /** @var int */
    private $size;

    /** @var Board */
    private $firstBoard;

    /** @var Board */
    private $secondBoard;

    /** @var Board */
    private $neutralBoard;

    /** @var Board */
    private $groundBoard;

    /** @var Move[] */
    private $moves;

    /** @var int */
    private $undoCount;

    /** @var int */
    private $piecesPerPlayer;

    private function __construct(int $size) {
        $this->size = $size;
        $this->piecesPerPlayer = intdiv(ArrayBitSetBoard::filledBoard($size)->count(), 2);
        $this->moves = [];
        $this->undoCount = 0;
        $this->resetBoard();
    }

    private function resetBoard(): void
    {
        $this->firstBoard = ArrayBitSetBoard::emptyBoard($this->size);
        $this->secondBoard = ArrayBitSetBoard::emptyBoard($this->size);
        $this->neutralBoard = ArrayBitSetBoard::neutralBoard($this->size);
        $this->groundBoard = ArrayBitSetBoard::groundBoard($this->size);
    }

    private function replay(): void
    {
        $this->resetBoard();
        $movesMade = 0;
        foreach ($this->moves() as $move) {
            $this->handleMove($move, $movesMade++);
        }
    }

    public static function create(int $size): OneToOneGame
    {
        return new self($size);
    }

    public function size(): int
    {
        return $this->size;
    }

    public function firstBoard(): Board
    {
        return $this->firstBoard;
    }

    public function secondBoard(): Board
    {
        return $this->secondBoard;
    }

    public function neutralBoard(): Board
    {
        return $this->neutralBoard;
    }

    protected function groundBoard(): Board
    {
        return $this->groundBoard;
    }

    protected function occupiedBoard(): Board
    {
        return $this->firstBoard->or($this->secondBoard)->or($this->neutralBoard);
    }

    public function legalMoves(): array
    {
        return ArrayBitSetMove::fromBoard($this->legalMovesBoard());
    }

    public function makeMove(Move $move): void
    {
        if ($this->isOver()) {
            throw CouldNotMakeMoveException::gameIsAlreadyOver();
        }

        if (!$this->isLegalMove($move)) {
            throw CouldNotMakeMoveException::invalidMove($move);
        }

        $this->handleMove($move, $this->movesMade());

        $this->moves = $this->moves();
        $this->moves[] = $move;
        $this->undoCount = 0;
    }

    private function handleMove(Move $move, int $movesMade): void
    {
        if ($movesMade % 2 === 0) {
            $this->firstBoard = $this->firstBoard->or($move->toBoard($this->size));
        } else {
            $this->secondBoard = $this->secondBoard->or($move->toBoard($this->size));
        }

        $occupiedBoard = $this->occupiedBoard();

        while (true) {
            if ($this->isOver()) {
                break;
            }

            $firstChain = $this->vacantBoard()->and($this->scaffoldedBoard())->and($this->firstBoard->promoteMajority());
            $firstVacancy = $this->piecesPerPlayer - $this->firstBoard->count();
            if ($firstChain->count() <= $firstVacancy) {
                $this->firstBoard = $this->firstBoard->or($firstChain);
            } else {
                $chainMoves = ArrayBitSetMove::fromBoard($firstChain);
                for ($i = 0; $i < $firstVacancy; $i++) {
                    /** @var Move $chainMove */
                    $chainMove = array_shift($chainMoves);
                    $this->firstBoard = $this->firstBoard->or($chainMove->toBoard($this->size));
                }
            }

            $secondChain = $this->vacantBoard()->and($this->scaffoldedBoard())->and($this->secondBoard->promoteMajority());
            $secondVacancy = $this->piecesPerPlayer - $this->secondBoard->count();
            if ($secondChain->count() <= $secondVacancy) {
                $this->secondBoard = $this->secondBoard->or($secondChain);
            } else {
                $chainMoves = ArrayBitSetMove::fromBoard($secondChain);
                for ($i = 0; $i < $secondVacancy; $i++) {
                    /** @var Move $chainMove */
                    $chainMove = array_shift($chainMoves);
                    $this->secondBoard = $this->secondBoard->or($chainMove->toBoard($this->size));
                }
            }

            $newOccupiedBoard = $this->occupiedBoard();
            if ($occupiedBoard->equalsTo($newOccupiedBoard)) {
                break;
            }

            $occupiedBoard = $newOccupiedBoard;
        }
    }

    public function isOver(): bool
    {
        return $this->firstWins() || $this->secondWins();
    }

    public function firstWins(): bool
    {
        return $this->piecesPerPlayer <= $this->firstBoard->count();
    }

    public function secondWins(): bool
    {
        return $this->piecesPerPlayer <= $this->secondBoard->count();
    }

    public function isFirstTurn(): bool
    {
        return ($this->movesMade() % 2 === 0);
    }

    public function isSecondTurn(): bool
    {
        return !$this->isFirstTurn();
    }

    public function moves(): array
    {
        return array_slice($this->moves, 0, count($this->moves) - $this->undoCount);
    }

    public function movesMade(): int
    {
        return count($this->moves());
    }

    public function undo(): void
    {
        if (!$this->isUndoable()) {
            throw Exceptions\CouldNotUndoException::noMoreUndoableMoves();
        }

        $this->undoCount++;
        $this->replay();
    }

    public function redo(): void
    {
        if (!$this->isRedoable()) {
            throw Exceptions\CouldNotRedoException::noMoreRedoableMoves();
        }

        $this->undoCount--;
        $this->replay();
    }

    public function isUndoable(): bool
    {
        return $this->undoCount < count($this->moves);
    }

    public function isRedoable(): bool
    {
        return $this->undoCount > 0;
    }
}

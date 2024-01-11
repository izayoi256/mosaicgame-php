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

use MosaicGame\Board\Board;
use MosaicGame\Exceptions;
use MosaicGame\Exceptions\CouldNotMakeMoveException;
use MosaicGame\Game\Move\Move;
use function array_shift;
use function array_slice;
use function count;
use function intdiv;

abstract class AbstractOneOnOneGame implements OneOnOneGame
{
    use GameTrait;

    /** @var int */
    private $size;

    /** @var Board[] */
    private $firstBoards;

    /** @var Board[] */
    private $secondBoards;

    /** @var Board */
    private $neutralBoard;

    /** @var Move[] */
    private $moves;

    /** @var int */
    private $undoCount;

    /** @var int */
    private $piecesPerPlayer;

    private function __construct(int $size, int $piecesPerPlayer, array $moves, int $undoCount, array $firstBoards, array $secondBoards, Board $neutralBoard)
    {
        $this->size = $size;
        $this->piecesPerPlayer = $piecesPerPlayer;
        $this->moves = $moves;
        $this->undoCount = $undoCount;
        $this->firstBoards = $firstBoards;
        $this->secondBoards = $secondBoards;
        $this->neutralBoard = $neutralBoard;
    }

    abstract protected static function createEmptyBoard(int $size): Board;

    abstract protected static function createNeutralBoard(int $size): Board;

    abstract protected static function createFilledBoard(int $size): Board;

    abstract protected static function createMovesFromBoard(Board $board): array;

    private static function fromSize(int $size): self
    {
        return new static(
            $size,
            intdiv(static::createFilledBoard($size)->count(), 2),
            [],
            0,
            [static::createEmptyBoard($size)],
            [static::createEmptyBoard($size)],
            static::createNeutralBoard($size),
        );
    }

    public static function create(int $size): self
    {
        return self::fromSize($size);
    }

    public static function fromSnapshot(int $size, array $moves, array $firstBoards, array $secondBoards, Board $neutralBoard)
    {
        return new static(
            $size,
            intdiv(static::createFilledBoard($size)->count(), 2),
            $moves,
            0,
            $firstBoards,
            $secondBoards,
            $neutralBoard,
        );
    }

    public function size(): int
    {
        return $this->size;
    }

    public function piecesPerPlayer(): int
    {
        return $this->piecesPerPlayer;
    }

    public function firstBoard(): Board
    {
        return $this->firstBoards[$this->movesMade()];
    }

    private function firstBoards(): array
    {
        return array_slice($this->firstBoards, 0, count($this->firstBoards) - $this->undoCount);
    }

    public function secondBoard(): Board
    {
        return $this->secondBoards[$this->movesMade()];
    }

    private function secondBoards(): array
    {
        return array_slice($this->secondBoards, 0, count($this->secondBoards) - $this->undoCount);
    }

    public function neutralBoard(): Board
    {
        return $this->neutralBoard;
    }

    protected function occupiedBoard(): Board
    {
        return $this->firstBoard()->or($this->secondBoard())->or($this->neutralBoard);
    }

    public function legalMoves(): array
    {
        return static::createMovesFromBoard($this->legalBoard());
    }

    public function makeMove(Move $move): void
    {
        if ($this->isOver()) {
            throw CouldNotMakeMoveException::gameIsAlreadyOver();
        }

        if (!$this->isLegalMove($move)) {
            throw CouldNotMakeMoveException::illegalMove($move);
        }

        $this->firstBoards = $this->firstBoards();
        $this->secondBoards = $this->secondBoards();
        $this->moves = $this->moves();
        $this->undoCount = 0;

        $this->handleMove($move);
    }

    private function handleMove(Move $move): void
    {
        $movesMade = $this->movesMade();
        $this->firstBoards[$movesMade + 1] = $this->firstBoards[$movesMade];
        $this->secondBoards[$movesMade + 1] = $this->secondBoards[$movesMade];

        /** @var Board[] $boards */
        $boards = [
            &$this->firstBoards[$movesMade + 1],
            &$this->secondBoards[$movesMade + 1],
        ];
        $majorityBoards = [];

        $playerBoard =& $boards[$movesMade % 2];
        $playerBoard = $playerBoard->or($move->toBoard($this->size));
        $this->moves[] = $move;

        $vacantBoard = $this->vacantBoard();
        $scaffoldedBoard = $this->scaffoldedBoard();

        while (!$this->isOver()) {

            $chained = false;

            foreach ($boards as $key => &$chainingBoard) {
                $majorityBoard = $majorityBoards[$key] ?? ($majorityBoards[$key] = $chainingBoard->promoteMajority());
                $chain = $vacantBoard->and($scaffoldedBoard)->and($majorityBoard);
                $chainCount = $chain->count();
                if (!$chainCount) {
                    continue;
                }
                $vacancy = $this->piecesPerPlayer - $chainingBoard->count();
                if ($chainCount <= $vacancy) {
                    $chainingBoard = $chainingBoard->or($chain);
                } else {
                    $chainMoves = static::createMovesFromBoard($chain);
                    for ($i = 0; $i < $vacancy; $i++) {
                        /** @var Move $chainMove */
                        $chainMove = array_shift($chainMoves);
                        $chainingBoard = $chainingBoard->or($chainMove->toBoard($this->size));
                    }
                }

                $chained = true;
                $scaffoldedBoard = $this->scaffoldedBoard();
                $vacantBoard = $this->vacantBoard();
                unset($majorityBoards[$key]);
            }

            if (!$chained) {
                break;
            }
        }
    }

    public function isOver(): bool
    {
        return $this->firstWins() || $this->secondWins();
    }

    public function firstWins(): bool
    {
        return $this->piecesPerPlayer <= $this->firstBoard()->count();
    }

    public function secondWins(): bool
    {
        return $this->piecesPerPlayer <= $this->secondBoard()->count();
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
    }

    public function redo(): void
    {
        if (!$this->isRedoable()) {
            throw Exceptions\CouldNotRedoException::noMoreRedoableMoves();
        }

        $this->undoCount--;
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

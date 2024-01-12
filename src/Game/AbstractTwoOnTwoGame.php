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
use MosaicGame\Game\Move\Move;

abstract class AbstractTwoOnTwoGame implements TwoOnTwoGame
{
    use GameTrait;

    /** @var int */
    private $size;

    /** @var Board[] */
    private $firstBoards;

    /** @var Board[] */
    private $secondBoards;

    /** @var Board[] */
    private $thirdBoards;

    /** @var Board[] */
    private $fourthBoards;

    /** @var Board */
    private $neutralBoard;

    /** @var Move[] */
    private $moves;

    /** @var int[] */
    private $playerIndexes;

    /** @var int */
    private $undoCount;

    /** @var int */
    private $piecesPerPlayer;

    private function __construct(
        int $size,
        int $piecesPerPlayer,
        array $moves,
        int $undoCount,
        array $playerIndexes,
        array $firstBoards,
        array $secondBoards,
        array $thirdBoards,
        array $fourthBoards,
        Board $neutralBoard
    ) {
        $this->size = $size;
        $this->piecesPerPlayer = $piecesPerPlayer;
        $this->moves = $moves;
        $this->undoCount = $undoCount;
        $this->playerIndexes = $playerIndexes;
        $this->firstBoards = $firstBoards;
        $this->secondBoards = $secondBoards;
        $this->thirdBoards = $thirdBoards;
        $this->fourthBoards = $fourthBoards;
        $this->neutralBoard = $neutralBoard;
    }

    abstract protected static function createEmptyBoard(int $size): Board;

    abstract protected static function createNeutralBoard(int $size): Board;

    abstract protected static function createFilledBoard(int $size): Board;

    abstract protected static function createMovesFromBoard(Board $board): array;

    private static function fromSize(int $size): self
    {
        $neutralBoard = static::createNeutralBoard($size);
        $piecesPerPlayer = (int)\round($neutralBoard->flip()->count() / 4);
        return new static(
            $size,
            $piecesPerPlayer,
            [],
            0,
            [0],
            [static::createEmptyBoard($size)],
            [static::createEmptyBoard($size)],
            [static::createEmptyBoard($size)],
            [static::createEmptyBoard($size)],
            static::createNeutralBoard($size),
        );
    }

    public static function create(int $size): self
    {
        return self::fromSize($size);
    }

    public static function fromSnapshot(
        int $size,
        array $moves,
        array $playerIndexes,
        array $firstBoards,
        array $secondBoards,
        array $thirdBoards,
        array $fourthBoards,
        Board $neutralBoard
    ) {
        $piecesPerPlayer = (int)\round($neutralBoard->flip()->count() / 4);
        return new static(
            $size,
            $piecesPerPlayer,
            $moves,
            0,
            $playerIndexes,
            $firstBoards,
            $secondBoards,
            $thirdBoards,
            $fourthBoards,
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

    public function firstRemainingPieces(): int
    {
        return $this->piecesPerPlayer - $this->firstPlacedPieces();
    }

    public function secondRemainingPieces(): int
    {
        return $this->piecesPerPlayer - $this->secondPlacedPieces();
    }

    public function thirdRemainingPieces(): int
    {
        return $this->piecesPerPlayer - $this->thirdPlacedPieces();
    }

    public function fourthRemainingPieces(): int
    {
        return $this->piecesPerPlayer - $this->fourthPlacedPieces();
    }

    public function firstPlacedPieces(): int
    {
        return $this->firstBoard()->count();
    }

    public function secondPlacedPieces(): int
    {
        return $this->secondBoard()->count();
    }

    public function thirdPlacedPieces(): int
    {
        return $this->thirdBoard()->count();
    }

    public function fourthPlacedPieces(): int
    {
        return $this->fourthBoard()->count();
    }

    public function firstBoard(): Board
    {
        return $this->firstBoards[$this->movesMade()];
    }

    private function firstBoards(): array
    {
        return \array_slice($this->firstBoards, 0, \count($this->firstBoards) - $this->undoCount);
    }

    public function secondBoard(): Board
    {
        return $this->secondBoards[$this->movesMade()];
    }

    private function secondBoards(): array
    {
        return \array_slice($this->secondBoards, 0, \count($this->secondBoards) - $this->undoCount);
    }

    public function thirdBoard(): Board
    {
        return $this->thirdBoards[$this->movesMade()];
    }

    private function thirdBoards(): array
    {
        return \array_slice($this->thirdBoards, 0, \count($this->thirdBoards) - $this->undoCount);
    }

    public function fourthBoard(): Board
    {
        return $this->fourthBoards[$this->movesMade()];
    }

    private function fourthBoards(): array
    {
        return \array_slice($this->fourthBoards, 0, \count($this->fourthBoards) - $this->undoCount);
    }

    public function neutralBoard(): Board
    {
        return $this->neutralBoard;
    }

    protected function occupiedBoard(): Board
    {
        return $this->neutralBoard
            ->or($this->firstBoard())
            ->or($this->secondBoard())
            ->or($this->thirdBoard())
            ->or($this->fourthBoard());
    }

    public function legalMoves(): array
    {
        return static::createMovesFromBoard($this->legalBoard());
    }

    public function makeMove(Move $move): void
    {
        if ($this->isOver()) {
            throw Exceptions\CouldNotMakeMoveException::gameIsAlreadyOver();
        }

        if (!$this->isLegalMove($move)) {
            throw Exceptions\CouldNotMakeMoveException::illegalMove($move);
        }

        $this->firstBoards = $this->firstBoards();
        $this->secondBoards = $this->secondBoards();
        $this->thirdBoards = $this->thirdBoards();
        $this->fourthBoards = $this->fourthBoards();
        $this->moves = $this->moves();
        $this->playerIndexes = $this->playerIndexes();
        $this->undoCount = 0;

        $this->handleMove($move);
    }

    private function handleMove(Move $move): void
    {
        $movesMade = $this->movesMade();
        $this->firstBoards[$movesMade + 1] = $this->firstBoards[$movesMade];
        $this->secondBoards[$movesMade + 1] = $this->secondBoards[$movesMade];
        $this->thirdBoards[$movesMade + 1] = $this->thirdBoards[$movesMade];
        $this->fourthBoards[$movesMade + 1] = $this->fourthBoards[$movesMade];

        /** @var Board[] $boards */
        $boards = [
            &$this->firstBoards[$movesMade + 1],
            &$this->secondBoards[$movesMade + 1],
            &$this->thirdBoards[$movesMade + 1],
            &$this->fourthBoards[$movesMade + 1],
        ];
        $majorityBoards = [];

        $playerIndex = $this->playerIndex();
        $playerBoard =& $boards[$playerIndex];
        $playerBoard = $playerBoard->or($move->toBoard($this->size));
        $this->moves[] = $move;

        $vacantBoard = $this->vacantBoard();
        $scaffoldedBoard = $this->scaffoldedBoard();

        while (!$this->isOver()) {

            $chained = false;

            foreach ($boards as $key => &$chainingBoard) {
                $majorityBoard = $majorityBoards[$key] ?? ($majorityBoards[$key] = $chainingBoard->promoteHalfOrMore());
                $chain = $vacantBoard->and($scaffoldedBoard)->and($majorityBoard);
                $chainCount = $chain->count();
                if (!$chainCount) {
                    continue;
                }
                $vacancy = $this->piecesPerPlayer - $chainingBoard->count();
                if ($vacancy === 0) {
                    continue;
                }
                if ($chainCount <= $vacancy) {
                    $chainingBoard = $chainingBoard->or($chain);
                } else {
                    $chainMoves = static::createMovesFromBoard($chain);
                    for ($i = 0; $i < $vacancy; $i++) {
                        /** @var Move $chainMove */
                        $chainMove = \array_shift($chainMoves);
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

        $nextPlayerIndex = $playerIndex;

        do {
            $nextPlayerIndex = ($nextPlayerIndex + 1) % 4;
            $remainingPieces = $this->piecesPerPlayer - $boards[$nextPlayerIndex]->count();
        } while ($remainingPieces === 0 && $playerIndex !== $nextPlayerIndex);

        $this->playerIndexes[] = $nextPlayerIndex;
    }

    public function isOver(): bool
    {
        return $this->firstAndThirdWins() || $this->secondAndFourthWins();
    }

    public function firstAndThirdWins(): bool
    {
        return $this->firstRemainingPieces() === 0 && $this->thirdRemainingPieces() === 0;
    }

    public function secondAndFourthWins(): bool
    {
        return $this->secondRemainingPieces() === 0 && $this->fourthRemainingPieces() === 0;
    }

    private function playerIndexes(): array
    {
        return \array_slice($this->playerIndexes, 0, \count($this->playerIndexes) - $this->undoCount);
    }

    private function playerIndex(): int
    {
        return $this->playerIndexes[$this->movesMade()];
    }

    public function isFirstTurn(): bool
    {
        return $this->playerIndex() === 0;
    }

    public function isSecondTurn(): bool
    {
        return $this->playerIndex() === 1;
    }

    public function isThirdTurn(): bool
    {
        return $this->playerIndex() === 2;
    }

    public function isFourthTurn(): bool
    {
        return $this->playerIndex() === 3;
    }

    public function moves(): array
    {
        return \array_slice($this->moves, 0, \count($this->moves) - $this->undoCount);
    }

    public function movesMade(): int
    {
        return \count($this->moves());
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
        return $this->undoCount < \count($this->moves);
    }

    public function isRedoable(): bool
    {
        return $this->undoCount > 0;
    }
}

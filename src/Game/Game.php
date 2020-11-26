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

interface Game
{
    public function size(): int;

    /**
     * @return Move[]
     */
    public function moves(): array;

    public function movesMade(): int;

    public function isOver(): bool;

    /**
     * @return Board
     */
    public function legalMovesBoard(): Board;

    /**
     * @return Move[]
     */
    public function legalMoves(): array;

    public function isLegalMove(Move $move): bool;

    /**
     * @param Move $move
     * @throws Exceptions\CouldNotMakeMoveException
     */
    public function makeMove(Move $move): void;

    /**
     * @throws Exceptions\CouldNotUndoException
     */
    public function undo(): void;

    /**
     * @throws Exceptions\CouldNotUndoException
     */
    public function redo(): void;

    public function isUndoable(): bool;

    public function isRedoable(): bool;
}

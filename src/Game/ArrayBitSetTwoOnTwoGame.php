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
use MosaicGame\Game\Move\ArrayBitSetMove;

final class ArrayBitSetTwoOnTwoGame extends AbstractTwoOnTwoGame
{
    protected static function createEmptyBoard(int $size): Board
    {
        return ArrayBitSetBoard::emptyBoard($size);
    }

    protected static function createNeutralBoard(int $size): Board
    {
        return ArrayBitSetBoard::neutralBoard($size);
    }

    protected static function createFilledBoard(int $size): Board
    {
        return ArrayBitSetBoard::filledBoard($size);
    }

    protected static function createMovesFromBoard(Board $board): array
    {
        return ArrayBitSetMove::fromBoard($board);
    }

    protected function groundBoard(): Board
    {
        static $groundBoards = [];
        $size = $this->size();
        $groundBoard = $groundBoards[$size] ?? ($groundBoards[$size] = ArrayBitSetBoard::groundBoard($size));
        return $groundBoard;
    }
}

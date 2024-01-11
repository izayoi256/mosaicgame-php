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
use MosaicGame\Board\GMPBitSetBoard;
use MosaicGame\Game\Move\GMPBitSetMove;

final class GMPBitSetOneOnOneGame extends AbstractOneOnOneGame
{
    protected static function createEmptyBoard(int $size): Board
    {
        return GMPBitSetBoard::emptyBoard($size);
    }

    protected static function createNeutralBoard(int $size): Board
    {
        return GMPBitSetBoard::neutralBoard($size);
    }

    protected static function createFilledBoard(int $size): Board
    {
        return GMPBitSetBoard::filledBoard($size);
    }

    protected static function createMovesFromBoard(Board $board): array
    {
        return GMPBitSetMove::fromBoard($board);
    }

    protected function groundBoard(): Board
    {
        static $groundBoards = [];
        $size = $this->size();
        return $groundBoards[$size] ?? ($groundBoards[$size] = GMPBitSetBoard::groundBoard($size));
    }
}

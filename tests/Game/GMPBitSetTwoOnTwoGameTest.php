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

use MosaicGame\Game\GMPBitSetTwoOnTwoGame;
use MosaicGame\Game\Move\ArrayBitSetMove;
use MosaicGame\Game\Move\Move;
use MosaicGame\Game\TwoOnTwoGame;

final class GMPBitSetTwoOnTwoGameTest extends TwoOnTwoGameTest
{
    protected static function createGame(int $size): TwoOnTwoGame
    {
        return GMPBitSetTwoOnTwoGame::create($size);
    }

    protected static function moveFromOffset(int $offset): Move
    {
        return ArrayBitSetMove::fromOffset($offset);
    }
}

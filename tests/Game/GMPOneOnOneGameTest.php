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

use MosaicGame\Game\GMPOneOnOneGame;
use MosaicGame\Game\Move\GMPMove;
use MosaicGame\Game\Move\Move;
use MosaicGame\Game\OneOnOneGame;

final class GMPOneOnOneGameTest extends OneOnOneGameTest
{
    protected static function createGame(int $size): OneOnOneGame
    {
        return GMPOneOnOneGame::create($size);
    }

    protected static function moveFromOffset(int $offset): Move
    {
        return GMPMove::fromOffset($offset);
    }
}

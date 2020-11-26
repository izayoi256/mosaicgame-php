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

interface TwoToTwoGame extends Game
{
    public function firstBoard(): Board;

    public function secondBoard(): Board;

    public function thirdBoard(): Board;

    public function fourthBoard(): Board;

    public function neutralBoard(): Board;
}

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

interface OneToOneGame extends Game
{
    public function size(): int;

    public function firstBoard(): Board;

    public function secondBoard(): Board;

    public function neutralBoard(): Board;

    public function firstWins(): bool;

    public function secondWins(): bool;

    public function isFirstTurn(): bool;

    public function isSecondTurn(): bool;
}

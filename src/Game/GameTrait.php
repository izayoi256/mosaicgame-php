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
use MosaicGame\Game\Move\Move;

trait GameTrait
{
    abstract public function size(): int;

    abstract protected function groundBoard(): Board;

    protected function scaffoldedBoard(): Board
    {
        return $this->groundBoard()->or($this->occupiedBoard()->promoteFour());
    }

    abstract protected function occupiedBoard(): Board;

    protected function vacantBoard(): Board
    {
        return $this->occupiedBoard()->flip();
    }

    public function legalBoard(): Board
    {
        return $this->vacantBoard()->and($this->scaffoldedBoard());
    }

    public function isLegalMove(Move $move): bool
    {
        return $this->legalBoard()->and($move->toBoard($this->size()))->count() > 0;
    }
}

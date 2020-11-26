<?php declare(strict_types=1);
/*
 * This file is part of MosaicGame.
 *
 * (c) Shotaro Hama <qwert.izayoi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MosaicGame\Game\Move;

use MosaicGame\Board\Board;

interface Move
{
    public function toOffset(): int;

    public function toBoard(int $size): Board;

    /**
     * @param Board $board
     * @return self[]
     */
    public static function fromBoard(Board $board): array;
}

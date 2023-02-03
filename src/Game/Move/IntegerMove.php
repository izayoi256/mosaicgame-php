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
use MosaicGame\Board\IntegerBoard;
use function array_filter;
use function array_map;

final class IntegerMove implements Move
{
    /** @var int */
    private $offset;

    private function __construct(int $index)
    {
        $this->offset = $index;
    }

    public static function fromOffset(int $offset)
    {
        return new self($offset);
    }

    public static function fromBoard(Board $board): array
    {
        return array_map(static function (int $offset) {
            return static::fromOffset($offset);
        }, array_keys(array_filter($board->toArray())));
    }

    public function toOffset(): int
    {
        return $this->offset;
    }

    public function toBoard(int $size): Board
    {
        return IntegerBoard::fromInt($size, 1 << $this->offset);
    }
}
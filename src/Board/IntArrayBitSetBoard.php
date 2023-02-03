<?php declare(strict_types=1);
/*
 * This file is part of MosaicGame.
 *
 * (c) Shotaro Hama <qwert.izayoi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MosaicGame\Board;

use MosaicGame\BitSet\IntArrayBitSet;
use MosaicGame\BitSet\BitSet;

final class IntArrayBitSetBoard extends BitSetBoard
{
    protected static function stringToBitSet(int $bitSetSize, string $string): BitSet
    {
        return IntArrayBitSet::fromString($bitSetSize, $string);
    }
}

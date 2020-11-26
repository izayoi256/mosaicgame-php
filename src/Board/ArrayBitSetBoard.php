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

use MosaicGame\BitSet\ArrayBitSet;
use MosaicGame\BitSet\BitSet;

final class ArrayBitSetBoard extends BitSetBoard
{
    protected static function stringToBitSet(string $string): BitSet
    {
        return ArrayBitSet::fromString(static::BIT_SET_SIZE, $string);
    }
}

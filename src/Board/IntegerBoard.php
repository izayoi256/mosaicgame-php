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

use function abs;
use function array_map;
use function array_sum;
use function assert;
use function intdiv;
use function range;
use function sprintf;
use function str_pad;
use function str_split;
use function strrev;
use function substr_count;
use const STR_PAD_LEFT;

final class IntegerBoard implements Board
{
    public const MAX_SIZE = 5;
    protected const PROMOTE_ZERO = 0b0000001;
    protected const PROMOTE_ONE = 0b0000010;
    protected const PROMOTE_TWO = 0b0000100;
    protected const PROMOTE_THREE = 0b0001000;
    protected const PROMOTE_FOUR = 0b0010000;
    protected const PROMOTE_MAJORITY = 0b0100000;
    protected const PROMOTE_HALF_OR_MORE = 0b1000000;

    /** @var int */
    private $size;

    /** @var int */
    private $bitSize;

    /** @var int */
    private $num;

    protected function __construct(int $size, int $num)
    {
        assert(0 < $size && $size <= self::MAX_SIZE, sprintf('Board size must be between 1 and %d.', self::MAX_SIZE));
        $this->size = $size;
        $this->bitSize = self::sizeToBitSize($size);
        $this->num = $num;
        $this->applySizeMask();
    }

    private function applySizeMask(): void
    {
        $this->num &= self::boardMask($this->size);
    }

    public static function fromInt(int $size, int $num): self
    {
        return new self($size, $num);
    }

    public static function fromString(int $size, string $bitString): self
    {
        return new self($size, intval($bitString, 2));
    }

    public static function emptyBoard(int $size): Board
    {
        static $cache = [];
        return $cache[$size]
            ?? ($cache[$size] = new static($size, 0));
    }

    public static function filledBoard(int $size): Board
    {
        static $cache = [];
        return $cache[$size]
            ?? ($cache[$size] = new static($size, self::boardMask($size)));
    }

    public static function groundBoard(int $size): Board
    {
        static $cache = [];
        return $cache[$size]
            ?? ($cache[$size] = self::emptyBoard($size)->or(new static($size, self::layerMask($size))));
    }

    public static function neutralBoard(int $size): Board
    {
        static $cache = [];

        if (!isset($cache[$size])) {
            if ($size % 2 === 1) {
                $num = 1;
                for ($i = 1; $i < $size; $i++) {
                    $num = $num << ($i ** 2);
                }
                $num = $num << intdiv($i ** 2, 2);
                $cache[$size] = new static($size, $num);
            } else {
                $cache[$size] = self::emptyBoard($size);
            }
        }

        return $cache[$size];
    }

    public function __toString()
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return str_pad(sprintf('%b', $this->num), $this->bitSize, '0', STR_PAD_LEFT);
    }

    public function getIterator()
    {
        yield from $this->toArray();
    }

    public function toArray()
    {
        return array_map(
            'boolval',
            str_split(strrev($this->toString())),
        );
    }

    public function count(): int
    {
        return substr_count($this->toString(), '1');
    }

    public function size(): int
    {
        return $this->size;
    }

    public function mirrorHorizontal(): Board
    {
        /** @var int[] $masks */
        static $masks;
        if (!isset($masks)) {
            $masks = [
                0 => intval('0010000100001000010000100000000000000000001001001000001', 2),
                1 => intval('0000000000000000000000000010001000100010000000000010100', 2),
                -1 => intval('0000000000000000000000000001000100010001000000000001010', 2),
                2 => intval('0100001000010000100001000000000000000000010010010000000', 2),
                -2 => intval('0001000010000100001000010000000000000000000100100100000', 2),
                3 => intval('0000000000000000000000000100010001000100000000000000000', 2),
                -3 => intval('0000000000000000000000000000100010001000100000000000000', 2),
                4 => intval('1000010000100001000010000000000000000000000000000000000', 2),
                -4 => intval('0000100001000010000100001000000000000000000000000000000', 2),
            ];
        }

        $num = 0;
        foreach ($masks as $amount => $mask) {
            if ($amount < 0) {
                $num |= ($mask & ($this->num >> abs($amount)));
            } else {
                $num |= ($mask & ($this->num << $amount));
            }
        }

        $clone = clone $this;
        $clone->num = $num;
        $clone->applySizeMask();
        return $clone;
    }

    public function flipVertical(): Board
    {
        /** @var int[] $masks */
        static $masks;
        if (!isset($masks)) {
            $masks = [
                0 => intval('111110000000000000000000000000000011100000001', 2),
                -2 => intval('110', 2),
                2 => intval('11000', 2),
                -6 => intval('11100000', 2),
                6 => intval('11100000000000', 2),
                -12 => intval('111100000000000000', 2),
                -4 => intval('1111000000000000000000', 2),
                4 => intval('11110000000000000000000000', 2),
                12 => intval('111100000000000000000000000000', 2),
                -20 => intval('11111000000000000000000000000000000', 2),
                -10 => intval('1111100000000000000000000000000000000000', 2),
                10 => intval('11111000000000000000000000000000000000000000000000', 2),
                20 => intval('1111100000000000000000000000000000000000000000000000000', 2),
            ];
        }

        $num = 0;
        foreach ($masks as $amount => $mask) {
            if ($amount < 0) {
                $num |= ($mask & ($this->num >> abs($amount)));
            } else {
                $num |= ($mask & ($this->num << $amount));
            }
        }

        $clone = clone $this;
        $clone->num = $num;
        $clone->applySizeMask();
        return $clone;
    }

    public function flipDiagonal(): Board
    {
        /** @var int[] $masks */
        static $masks;
        if (!isset($masks)) {
            $masks = [
                0 => intval('0000100010001000100010000000100100100100000101010001101', 2),
                3 => intval('10000', 2),
                -3 => intval('10', 2),
                4 => intval('1010000000000', 2),
                -4 => intval('101000000', 2),
                8 => intval('10000000000000', 2),
                -8 => intval('100000', 2),
                5 => intval('1001001000000000000000000000', 2),
                -5 => intval('10010010000000000000000', 2),
                10 => intval('10010000000000000000000000000', 2),
                -10 => intval('1001000000000000000', 2),
                15 => intval('100000000000000000000000000000', 2),
                -15 => intval('100000000000000', 2),
                6 => intval('1000100010001000000000000000000000000000000000000000', 2),
                -6 => intval('1000100010001000000000000000000000000000000000', 2),
                12 => intval('10001000100000000000000000000000000000000000000000000', 2),
                -12 => intval('10001000100000000000000000000000000000000', 2),
                18 => intval('100010000000000000000000000000000000000000000000000000', 2),
                -18 => intval('100010000000000000000000000000000000', 2),
                24 => intval('1000000000000000000000000000000000000000000000000000000', 2),
                -24 => intval('1000000000000000000000000000000', 2),
            ];
        }

        $num = 0;
        foreach ($masks as $amount => $mask) {
            if ($amount < 0) {
                $num |= ($mask & ($this->num >> abs($amount)));
            } else {
                $num |= ($mask & ($this->num << $amount));
            }
        }

        $clone = clone $this;
        $clone->num = $num;
        $clone->applySizeMask();
        return $clone;
    }

    public function rotate90(): Board
    {
        return $this->flipDiagonal()->flipVertical();
    }

    public function rotate180(): Board
    {
        return $this->mirrorHorizontal()->flipVertical();
    }

    public function rotate270(): Board
    {
        return $this->flipVertical()->flipDiagonal();
    }

    public function flip(): Board
    {
        $clone = clone $this;
        $clone->num = ~$this->num;
        $clone->applySizeMask();
        return $clone;
    }

    public function and(Board $other): Board
    {
        $clone = clone $this;
        $clone->num &= (($other instanceof self)
            ? $other->num
            : intval($other->toString(), 2));
        $clone->applySizeMask();
        return $clone;
    }

    public function or(Board $other): Board
    {
        $clone = clone $this;
        $clone->num |= (($other instanceof self)
            ? $other->num
            : intval($other->toString(), 2));
        $clone->applySizeMask();
        return $clone;
    }

    public function xor(Board $other): Board
    {
        $clone = clone $this;
        $clone->num ^= (($other instanceof self)
            ? $other->num
            : intval($other->toString(), 2));
        $clone->applySizeMask();
        return $clone;
    }

    public function promoteZero(): Board
    {
        return $this->promote(self::PROMOTE_ZERO);
    }

    public function promoteOne(): Board
    {
        return $this->promote(self::PROMOTE_ONE);
    }

    public function promoteTwo(): Board
    {
        return $this->promote(self::PROMOTE_TWO);
    }

    public function promoteThree(): Board
    {
        return $this->promote(self::PROMOTE_THREE);
    }

    public function promoteFour(): Board
    {
        return $this->promote(self::PROMOTE_FOUR);
    }

    public function promoteHalfOrMore(): Board
    {
        return $this->promote(self::PROMOTE_HALF_OR_MORE);
    }

    public function promoteMajority(): Board
    {
        return $this->promote(self::PROMOTE_MAJORITY);
    }

    private function promote(int $type): self
    {
        $resultNum = 0;

        for ($srcLayerSize = $this->size; $srcLayerSize > 1; $srcLayerSize--) {
            $dstLayerSize = $srcLayerSize - 1;
            $srcLayerMask = self::layerMask($srcLayerSize);
            $srcLayer = $this->num & $srcLayerMask;
            $promotionLayer = 0;

            if ($type & (self::PROMOTE_ZERO | self::PROMOTE_ONE)) {
                $srcLayer = ~$this->num &  $srcLayerMask;
            }

            if ($srcLayer == 0) {
                continue;
            }

            if ($type & (self::PROMOTE_ZERO | self::PROMOTE_FOUR)) {
                $p = $srcLayer & ($srcLayer >> 1);
                $p &= ($p >> $srcLayerSize);
                $promotionLayer |= $p;
            }

            if ($type & (self::PROMOTE_ONE | self::PROMOTE_THREE)) {
                $p1 = $srcLayer & ($srcLayer >> 1);
                $p1 ^= ($p1 >> $srcLayerSize);
                $p2 = $srcLayer ^ ($srcLayer >> 1);
                $p2 ^= ($p2 >> $srcLayerSize);
                $promotionLayer |= ($p1 & $p2);
            }

            if ($type & self::PROMOTE_TWO) {
                $p1 = $srcLayer ^ ($srcLayer >> 1);
                $p1 &= ($p1 >> $srcLayerSize);
                $p2 = $srcLayer ^ ($srcLayer >> $srcLayerSize);
                $p2 &= ($p2 >> 1);
                $promotionLayer |= ($p1 | $p2);
            }

            if ($type & self::PROMOTE_MAJORITY) {
                $p1 = $srcLayer & ($srcLayer >> 1);
                $p1 |= ($p1 >> $srcLayerSize);
                $p2 = $srcLayer & ($srcLayer >> $srcLayerSize);
                $p2 |= ($p2 >> 1);
                $promotionLayer |= ($p1 & $p2);
            }

            if ($type & self::PROMOTE_HALF_OR_MORE) {
                $p1 = $srcLayer | ($srcLayer >> 1);
                $p1 &= ($p1 >> $srcLayerSize);
                $p2 = $srcLayer | ($srcLayer >> $srcLayerSize);
                $p2 &= ($p2 >> 1);
                $promotionLayer |= ($p1 | $p2);
            }

            if ($promotionLayer == 0) {
                continue;
            }

            for ($i = 0; $i < $dstLayerSize; $i++) {

                static $rowMasks = [];
                if (!isset($rowMasks[$dstLayerSize][$i])) {
                    $rowMask = 0;
                    foreach (range(0, $dstLayerSize - 1) as $index) {
                        $rowMask |= (1 << $index);
                    }
                    $rowMask = $rowMask << self::layerShift($srcLayerSize);
                    $rowMask = $rowMask << ($srcLayerSize * $i);
                    $rowMasks[$dstLayerSize][$i] = $rowMask;
                }
                $rowMask = $rowMasks[$dstLayerSize][$i];

                $promotionRow = $promotionLayer & $rowMask;

                if ($promotionRow == 0) {
                    continue;
                }

                $promotionRow = $promotionRow >> ($dstLayerSize ** 2 + $i);
                $resultNum |= $promotionRow;
            }
        }

        $clone = clone $this;
        $clone->num = $resultNum;
        $clone->applySizeMask();
        return $clone;
    }

    public function equalsTo(Board $other): bool
    {
        return ($other instanceof self)
            ? $this->num == $other->num
            : $this->toString() === $other->toString();
    }

    private static function layerShift(int $layerSize): int
    {
        static $layerShifts = [];

        if (!isset($layerShifts[$layerSize])) {
            $layerShift = 0;
            for ($i = 0; $i < $layerSize; $i++) {
                $layerShift += $i ** 2;
            }
            $layerShifts[$layerSize] = $layerShift;
        }

        return $layerShifts[$layerSize];
    }

    private static function layerMask(int $layerSize): int
    {
        static $layerMasks = [];

        if (!isset($layerMasks[$layerSize])) {
            $layerMask = 0;
            $j = $layerSize ** 2;
            for ($i = 0; $i < $j; $i++) {
                $layerMask |= (1 << $i);
            }

            $layerMask = $layerMask << self::layerShift($layerSize);
            $layerMasks[$layerSize] = $layerMask;
        }

        return $layerMasks[$layerSize];
    }

    private static function boardMask(int $size): int
    {
        static $boardMasks = [];

        if (!isset($boardMasks[$size])) {
            $boardMask = 0;
            for ($i = 1; $i <= $size; $i++) {
                $boardMask |= self::layerMask($i);
            }
            $boardMasks[$size] = $boardMask;
        }

        return $boardMasks[$size];
    }

    private static function sizeToBitSize(int $size): int
    {
        static $cache = [];

        if (!isset($cache[$size])) {
            $cache[$size] = array_sum(array_map(static function (int $layerSize) {
                return $layerSize ** 2;
            }, range(1, $size)));
        }

        return $cache[$size];
    }
}

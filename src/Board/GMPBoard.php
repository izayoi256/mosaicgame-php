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

use GMP;
use function abs;
use function array_map;
use function array_sum;
use function assert;
use function gmp_init;
use function gmp_popcount;
use function gmp_scan1;
use function gmp_setbit;
use function gmp_strval;
use function intdiv;
use function range;
use function sprintf;
use function str_pad;
use function str_split;
use function strrev;
use const STR_PAD_LEFT;

final class GMPBoard implements Board
{
    public const MAX_SIZE = 7;
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

    /** @var GMP */
    private $gmp;

    protected function __construct(int $size, GMP $gmp)
    {
        assert(0 < $size && $size <= self::MAX_SIZE, sprintf('Board size must be between 1 and %d.', self::MAX_SIZE));
        $this->size = $size;
        $this->bitSize = self::sizeToBitSize($size);
        $this->gmp = $gmp;
        $this->applySizeMask();
    }

    private function applySizeMask(): void
    {
        if (gmp_scan1($this->gmp, $this->bitSize) !== -1) {
            $this->gmp &= self::boardMask($this->size);
        }
    }

    public static function fromString(int $size, string $bitString): self
    {
        return new self($size, gmp_init($bitString, 2));
    }

    public static function emptyBoard(int $size): Board
    {
        static $cache = [];
        return $cache[$size]
            ?? ($cache[$size] = new static($size, gmp_init(0)));
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
                $gmp = gmp_init(1);
                for ($i = 1; $i < $size; $i++) {
                    $gmp = $gmp << ($i ** 2);
                }
                $gmp = $gmp << intdiv($i ** 2, 2);
                $cache[$size] = new static($size, $gmp);
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
        return str_pad(gmp_strval($this->gmp, 2), $this->bitSize, '0', STR_PAD_LEFT);
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
        return gmp_popcount($this->gmp);
    }

    public function size(): int
    {
        return $this->size;
    }

    public function mirrorHorizontal(): Board
    {
        /** @var GMP[] $masks */
        static $masks;
        if (!isset($masks)) {
            $masks = [
                0 => gmp_init('10000001000000100000010000001000000100000010000000000000000000000000000000000000000010000100001000010000100000000000000000001001001000001', 2),
                1 => gmp_init('10000010000010000010000010000010000000000000000000000000000010001000100010000000000010100', 2),
                -1 => gmp_init('1000001000001000001000001000001000000000000000000000000000001000100010001000000000001010', 2),
                2 => gmp_init('100000010000001000000100000010000001000000100000000000000000000000000000000000000000100001000010000100001000000000000000000010010010000000', 2),
                -2 => gmp_init('1000000100000010000001000000100000010000001000000000000000000000000000000000000000001000010000100001000010000000000000000000100100100000', 2),
                3 => gmp_init('100000100000100000100000100000100000000000000000000000000000100010001000100000000000000000', 2),
                -3 => gmp_init('100000100000100000100000100000100000000000000000000000000000100010001000100000000000000', 2),
                4 => gmp_init('1000000100000010000001000000100000010000001000000000000000000000000000000000000000001000010000100001000010000000000000000000000000000000000', 2),
                -4 => gmp_init('100000010000001000000100000010000001000000100000000000000000000000000000000000000000100001000010000100001000000000000000000000000000000', 2),
                5 => gmp_init('1000001000001000001000001000001000000000000000000000000000000000000000000000000000000000000', 2),
                -5 => gmp_init('10000010000010000010000010000010000000000000000000000000000000000000000000000000000000', 2),
                6 => gmp_init('10000001000000100000010000001000000100000010000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                -6 => gmp_init('10000001000000100000010000001000000100000010000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
            ];
        }

        $gmp = gmp_init(0);
        foreach ($masks as $amount => $mask) {
            if ($amount < 0) {
                $gmp |= ($mask & ($this->gmp >> abs($amount)));
            } else {
                $gmp |= ($mask & ($this->gmp << $amount));
            }
        }

        $clone = clone $this;
        $clone->gmp = $gmp;
        $clone->applySizeMask();
        return $clone;
    }

    public function flipVertical(): Board
    {
        /** @var GMP[] $masks */
        static $masks;
        if (!isset($masks)) {
            $masks = [
                0 => gmp_init('11111110000000000000000000000000000000000000000000000000000000000000000000111110000000000000000000000000000011100000001', 2),
                -2 => gmp_init('110', 2),
                2 => gmp_init('11000', 2),
                -6 => gmp_init('1111110000000000000000000000000000000000000000000000000000000000011100000', 2),
                6 => gmp_init('1111110000000000000000000000000000000000000000000000000000000000011100000000000', 2),
                -12 => gmp_init('111100000000000000', 2),
                -4 => gmp_init('1111000000000000000000', 2),
                4 => gmp_init('11110000000000000000000000', 2),
                12 => gmp_init('111100000000000000000000000000', 2),
                -20 => gmp_init('11111000000000000000000000000000000', 2),
                -10 => gmp_init('1111100000000000000000000000000000000000', 2),
                10 => gmp_init('11111000000000000000000000000000000000000000000000', 2),
                20 => gmp_init('1111100000000000000000000000000000000000000000000000000', 2),
                -30 => gmp_init('1111110000000000000000000000000000000000000000000000000000000', 2),
                -18 => gmp_init('1111110000000000000000000000000000000000000000000000000000000000000', 2),
                18 => gmp_init('1111110000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                30 => gmp_init('1111110000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                -42 => gmp_init('11111110000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                -28 => gmp_init('111111100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                -14 => gmp_init('1111111000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                14 => gmp_init('111111100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                28 => gmp_init('1111111000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                42 => gmp_init('11111110000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
            ];
        }

        $gmp = gmp_init(0);
        foreach ($masks as $amount => $mask) {
            if ($amount < 0) {
                $gmp |= ($mask & ($this->gmp >> abs($amount)));
            } else {
                $gmp |= ($mask & ($this->gmp << $amount));
            }
        }

        $clone = clone $this;
        $clone->gmp = $gmp;
        $clone->applySizeMask();
        return $clone;
    }

    public function flipDiagonal(): Board
    {
        /** @var GMP[] $masks */
        static $masks;
        if (!isset($masks)) {
            $masks = [
                0 => gmp_init('10000010000010000010000010000010000010000000000010000100001000010000100001000000000100010001000100010000000100100100100000101010001101', 2),
                3 => gmp_init('10000', 2),
                -3 => gmp_init('10', 2),
                4 => gmp_init('1010000000000', 2),
                -4 => gmp_init('101000000', 2),
                8 => gmp_init('100000100000100000100000100000100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000010000000000000', 2),
                -8 => gmp_init('1000001000001000001000001000001000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000100000', 2),
                5 => gmp_init('1001001000000000000000000000', 2),
                -5 => gmp_init('10010010000000000000000', 2),
                10 => gmp_init('10010000000000000000000000000', 2),
                -10 => gmp_init('1001000000000000000', 2),
                15 => gmp_init('100000000000000000000000000000', 2),
                -15 => gmp_init('100000000000000', 2),
                6 => gmp_init('1000100010001000000000000000000000000000000000000000', 2),
                -6 => gmp_init('1000100010001000000000000000000000000000000000', 2),
                12 => gmp_init('10001000100000000000000000000000000000000000000000000', 2),
                -12 => gmp_init('10001000100000000000000000000000000000000', 2),
                18 => gmp_init('100010000000000000000000000000000000000000000000000000', 2),
                -18 => gmp_init('100010000000000000000000000000000000', 2),
                24 => gmp_init('10000010000010000010000000000000000000000000000000000000000000000000000000000000001000000000000000000000000000000000000000000000000000000', 2),
                -24 => gmp_init('10000010000010000010000000000000000000000000000000000000000000000000000000000000001000000000000000000000000000000', 2),
                7 => gmp_init('100001000010000100001000000000000000000000000000000000000000000000000000000000000000000', 2),
                -7 => gmp_init('10000100001000010000100000000000000000000000000000000000000000000000000000000000', 2),
                14 => gmp_init('1000010000100001000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                -14 => gmp_init('10000100001000010000000000000000000000000000000000000000000000000000000000', 2),
                21 => gmp_init('10000100001000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                -21 => gmp_init('10000100001000000000000000000000000000000000000000000000000000000000', 2),
                28 => gmp_init('100001000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                -28 => gmp_init('10000100000000000000000000000000000000000000000000000000000000', 2),
                35 => gmp_init('1000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                -35 => gmp_init('10000000000000000000000000000000000000000000000000000000', 2),
                16 => gmp_init('1000001000001000001000001000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                -16 => gmp_init('100000100000100000100000100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                32 => gmp_init('100000100000100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                -32 => gmp_init('1000001000001000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                40 => gmp_init('1000001000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                -40 => gmp_init('100000100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                48 => gmp_init('10000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
                -48 => gmp_init('10000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 2),
            ];
        }

        $gmp = gmp_init(0);
        foreach ($masks as $amount => $mask) {
            if ($amount < 0) {
                $gmp |= ($mask & ($this->gmp >> abs($amount)));
            } else {
                $gmp |= ($mask & ($this->gmp << $amount));
            }
        }

        $clone = clone $this;
        $clone->gmp = $gmp;
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
        $clone->gmp = ~$this->gmp;
        $clone->applySizeMask();
        return $clone;
    }

    public function and(Board $other): Board
    {
        $clone = clone $this;
        $clone->gmp &= (($other instanceof self)
            ? $other->gmp
            : gmp_init($other->toString(), 2));
        $clone->applySizeMask();
        return $clone;
    }

    public function or(Board $other): Board
    {
        $clone = clone $this;
        $clone->gmp |= (($other instanceof self)
            ? $other->gmp
            : gmp_init($other->toString(), 2));
        $clone->applySizeMask();
        return $clone;
    }

    public function xor(Board $other): Board
    {
        $clone = clone $this;
        $clone->gmp ^= (($other instanceof self)
            ? $other->gmp
            : gmp_init($other->toString(), 2));
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
        $resultGmp = gmp_init(0);

        for ($srcLayerSize = $this->size; $srcLayerSize > 1; $srcLayerSize--) {
            $dstLayerSize = $srcLayerSize - 1;
            $srcLayerMask = self::layerMask($srcLayerSize);
            $srcLayer = $this->gmp & $srcLayerMask;
            $promotionLayer = gmp_init(0);

            if ($type & (self::PROMOTE_ZERO | self::PROMOTE_ONE)) {
                $srcLayer = ~$this->gmp &  $srcLayerMask;
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
                    $rowMask = gmp_init(0);
                    foreach (range(0, $dstLayerSize - 1) as $index) {
                        gmp_setbit($rowMask, $index);
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
                $resultGmp |= $promotionRow;
            }
        }

        $clone = clone $this;
        $clone->gmp = $resultGmp;
        $clone->applySizeMask();
        return $clone;
    }

    public function equalsTo(Board $other): bool
    {
        return ($other instanceof self)
            ? $this->gmp == $other->gmp
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

    private static function layerMask(int $layerSize): GMP
    {
        static $layerMasks = [];

        if (!isset($layerMasks[$layerSize])) {
            $layerMask = gmp_init(0);
            $j = $layerSize ** 2;
            for ($i = 0; $i < $j; $i++) {
                gmp_setbit($layerMask, $i);
            }

            $layerMask = $layerMask << self::layerShift($layerSize);
            $layerMasks[$layerSize] = $layerMask;
        }

        return clone $layerMasks[$layerSize];
    }

    private static function boardMask(int $size): GMP
    {
        static $boardMasks = [];

        if (!isset($boardMasks[$size])) {
            $boardMask = gmp_init(0);
            for ($i = 1; $i <= $size; $i++) {
                $boardMask |= self::layerMask($i);
            }
            $boardMasks[$size] = $boardMask;
        }

        return clone $boardMasks[$size];
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

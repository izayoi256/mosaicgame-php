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

use MosaicGame\BitSet\BitSet;
use function abs;
use function assert;
use function count;
use function intdiv;
use function range;
use function sprintf;

abstract class BitSetBoard implements Board
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

    /** @var BitSet */
    private $bitSet;

    protected function __construct(int $size, BitSet $bitSet)
    {
        assert(0 < $size && $size <= self::MAX_SIZE, sprintf('Board size must be between 1 and %d.', self::MAX_SIZE));
        $this->size = $size;
        $this->bitSet = self::boardMask($size)->and($bitSet);
    }

    abstract protected static function stringToBitSet(int $size, string $string): BitSet;

    public static function fromString(int $size, string $cells): Board
    {
        return new static($size, static::stringToBitSet(self::bitSetSize($size), $cells));
    }


    public static function emptyBoard(int $size): Board
    {
        static $boards = [];
        return $boards[$size]
            ?? ($boards[$size] = new static($size, self::zeroBitSet($size)));
    }

    public static function groundBoard(int $size): Board
    {
        static $boards = [];
        return $boards[$size]
            ?? ($boards[$size] = self::emptyBoard($size)->or(new static($size, self::layerMask($size, $size))));
    }

    public static function neutralBoard(int $size): Board
    {
        static $boards = [];

        if (!isset($boards[$size])) {
            if ($size % 2 === 1) {
                $bitSet = self::oneBitSet($size);
                for ($i = 1; $i < $size; $i++) {
                    $bitSet = $bitSet->shift($i ** 2);
                }
                $bitSet = $bitSet->shift(intdiv($i ** 2, 2));
                $boards[$size] = new static($size, $bitSet);
            } else {
                $boards[$size] = self::emptyBoard($size);
            }
        }

        return $boards[$size];
    }

    public static function filledBoard(int $size): Board
    {
        static $boards = [];
        return $boards[$size]
            ?? ($boards[$size] = new static($size, self::boardMask($size)));
    }

    public function __toString()
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return $this->bitSet->toString();
    }

    public function size(): int
    {
        return $this->size;
    }

    public function count(): int
    {
        return count($this->bitSet);
    }

    public function mirrorHorizontal(): Board
    {
        /** @var BitSet[] $masks */
        static $masks;
        if (!isset($masks[$this->size])) {
            $bitSetSize = self::bitSetSize($this->size);
            $masks[$this->size] = [
                0 => static::stringToBitSet($bitSetSize, '10000001000000100000010000001000000100000010000000000000000000000000000000000000000010000100001000010000100000000000000000001001001000001'),
                1 => static::stringToBitSet($bitSetSize, '10000010000010000010000010000010000000000000000000000000000010001000100010000000000010100'),
                -1 => static::stringToBitSet($bitSetSize, '1000001000001000001000001000001000000000000000000000000000001000100010001000000000001010'),
                2 => static::stringToBitSet($bitSetSize, '100000010000001000000100000010000001000000100000000000000000000000000000000000000000100001000010000100001000000000000000000010010010000000'),
                -2 => static::stringToBitSet($bitSetSize, '1000000100000010000001000000100000010000001000000000000000000000000000000000000000001000010000100001000010000000000000000000100100100000'),
                3 => static::stringToBitSet($bitSetSize, '100000100000100000100000100000100000000000000000000000000000100010001000100000000000000000'),
                -3 => static::stringToBitSet($bitSetSize, '100000100000100000100000100000100000000000000000000000000000100010001000100000000000000'),
                4 => static::stringToBitSet($bitSetSize, '1000000100000010000001000000100000010000001000000000000000000000000000000000000000001000010000100001000010000000000000000000000000000000000'),
                -4 => static::stringToBitSet($bitSetSize, '100000010000001000000100000010000001000000100000000000000000000000000000000000000000100001000010000100001000000000000000000000000000000'),
                5 => static::stringToBitSet($bitSetSize, '1000001000001000001000001000001000000000000000000000000000000000000000000000000000000000000'),
                -5 => static::stringToBitSet($bitSetSize, '10000010000010000010000010000010000000000000000000000000000000000000000000000000000000'),
                6 => static::stringToBitSet($bitSetSize, '10000001000000100000010000001000000100000010000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
                -6 => static::stringToBitSet($bitSetSize, '10000001000000100000010000001000000100000010000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
            ];
        }

        $bitSet = self::zeroBitSet($this->size);
        foreach ($masks[$this->size] as $amount => $mask) {
            if ($amount < 0) {
                $bitSet = $bitSet->or(
                    $mask->and(
                        $this->bitSet->unshift(abs($amount))
                    )
                );
            } else {
                $bitSet = $bitSet->or(
                    $mask->and(
                        $this->bitSet->shift($amount)
                    )
                );
            }
        }
        return new static($this->size, $bitSet);
    }

    public function flipVertical(): Board
    {
        /** @var BitSet[] $masks */
        static $masks;
        if (!isset($masks[$this->size])) {
            $bitSetSize = self::bitSetSize($this->size);
            $masks[$this->size] = [
                0 => static::stringToBitSet($bitSetSize, '11111110000000000000000000000000000000000000000000000000000000000000000000111110000000000000000000000000000011100000001'),
                -2 => static::stringToBitSet($bitSetSize, '110'),
                2 => static::stringToBitSet($bitSetSize, '11000'),
                -6 => static::stringToBitSet($bitSetSize, '1111110000000000000000000000000000000000000000000000000000000000011100000'),
                6 => static::stringToBitSet($bitSetSize, '1111110000000000000000000000000000000000000000000000000000000000011100000000000'),
                -12 => static::stringToBitSet($bitSetSize, '111100000000000000'),
                -4 => static::stringToBitSet($bitSetSize, '1111000000000000000000'),
                4 => static::stringToBitSet($bitSetSize, '11110000000000000000000000'),
                12 => static::stringToBitSet($bitSetSize, '111100000000000000000000000000'),
                -20 => static::stringToBitSet($bitSetSize, '11111000000000000000000000000000000'),
                -10 => static::stringToBitSet($bitSetSize, '1111100000000000000000000000000000000000'),
                10 => static::stringToBitSet($bitSetSize, '11111000000000000000000000000000000000000000000000'),
                20 => static::stringToBitSet($bitSetSize, '1111100000000000000000000000000000000000000000000000000'),
                -30 => static::stringToBitSet($bitSetSize, '1111110000000000000000000000000000000000000000000000000000000'),
                -18 => static::stringToBitSet($bitSetSize, '1111110000000000000000000000000000000000000000000000000000000000000'),
                18 => static::stringToBitSet($bitSetSize, '1111110000000000000000000000000000000000000000000000000000000000000000000000000000000'),
                30 => static::stringToBitSet($bitSetSize, '1111110000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
                -42 => static::stringToBitSet($bitSetSize, '11111110000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
                -28 => static::stringToBitSet($bitSetSize, '111111100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
                -14 => static::stringToBitSet($bitSetSize, '1111111000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
                14 => static::stringToBitSet($bitSetSize, '111111100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
                28 => static::stringToBitSet($bitSetSize, '1111111000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
                42 => static::stringToBitSet($bitSetSize, '11111110000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
            ];
        }

        $bitSet = self::zeroBitSet($this->size);
        foreach ($masks[$this->size] as $amount => $mask) {
            if ($amount < 0) {
                $bitSet = $bitSet->or(
                    $mask->and(
                        $this->bitSet->unshift(abs($amount))
                    )
                );
            } else {
                $bitSet = $bitSet->or(
                    $mask->and(
                        $this->bitSet->shift($amount)
                    )
                );
            }
        }
        return new static($this->size, $bitSet);
    }

    public function flipDiagonal(): Board
    {
        /** @var BitSet[] $masks */
        static $masks;
        if (!isset($masks[$this->size])) {
            $bitSetSize = self::bitSetSize($this->size);
            $masks[$this->size] = [
                0 => static::stringToBitSet($bitSetSize, '10000010000010000010000010000010000010000000000010000100001000010000100001000000000100010001000100010000000100100100100000101010001101'),
                3 => static::stringToBitSet($bitSetSize, '10000'),
                -3 => static::stringToBitSet($bitSetSize, '10'),
                4 => static::stringToBitSet($bitSetSize, '1010000000000'),
                -4 => static::stringToBitSet($bitSetSize, '101000000'),
                8 => static::stringToBitSet($bitSetSize, '100000100000100000100000100000100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000010000000000000'),
                -8 => static::stringToBitSet($bitSetSize, '1000001000001000001000001000001000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000100000'),
                5 => static::stringToBitSet($bitSetSize, '1001001000000000000000000000'),
                -5 => static::stringToBitSet($bitSetSize, '10010010000000000000000'),
                10 => static::stringToBitSet($bitSetSize, '10010000000000000000000000000'),
                -10 => static::stringToBitSet($bitSetSize, '1001000000000000000'),
                15 => static::stringToBitSet($bitSetSize, '100000000000000000000000000000'),
                -15 => static::stringToBitSet($bitSetSize, '100000000000000'),
                6 => static::stringToBitSet($bitSetSize, '1000100010001000000000000000000000000000000000000000'),
                -6 => static::stringToBitSet($bitSetSize, '1000100010001000000000000000000000000000000000'),
                12 => static::stringToBitSet($bitSetSize, '10001000100000000000000000000000000000000000000000000'),
                -12 => static::stringToBitSet($bitSetSize, '10001000100000000000000000000000000000000'),
                18 => static::stringToBitSet($bitSetSize, '100010000000000000000000000000000000000000000000000000'),
                -18 => static::stringToBitSet($bitSetSize, '100010000000000000000000000000000000'),
                24 => static::stringToBitSet($bitSetSize, '10000010000010000010000000000000000000000000000000000000000000000000000000000000001000000000000000000000000000000000000000000000000000000'),
                -24 => static::stringToBitSet($bitSetSize, '10000010000010000010000000000000000000000000000000000000000000000000000000000000001000000000000000000000000000000'),
                7 => static::stringToBitSet($bitSetSize, '100001000010000100001000000000000000000000000000000000000000000000000000000000000000000'),
                -7 => static::stringToBitSet($bitSetSize, '10000100001000010000100000000000000000000000000000000000000000000000000000000000'),
                14 => static::stringToBitSet($bitSetSize, '1000010000100001000000000000000000000000000000000000000000000000000000000000000000000000'),
                -14 => static::stringToBitSet($bitSetSize, '10000100001000010000000000000000000000000000000000000000000000000000000000'),
                21 => static::stringToBitSet($bitSetSize, '10000100001000000000000000000000000000000000000000000000000000000000000000000000000000000'),
                -21 => static::stringToBitSet($bitSetSize, '10000100001000000000000000000000000000000000000000000000000000000000'),
                28 => static::stringToBitSet($bitSetSize, '100001000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
                -28 => static::stringToBitSet($bitSetSize, '10000100000000000000000000000000000000000000000000000000000000'),
                35 => static::stringToBitSet($bitSetSize, '1000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
                -35 => static::stringToBitSet($bitSetSize, '10000000000000000000000000000000000000000000000000000000'),
                16 => static::stringToBitSet($bitSetSize, '1000001000001000001000001000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
                -16 => static::stringToBitSet($bitSetSize, '100000100000100000100000100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
                32 => static::stringToBitSet($bitSetSize, '100000100000100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
                -32 => static::stringToBitSet($bitSetSize, '1000001000001000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
                40 => static::stringToBitSet($bitSetSize, '1000001000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
                -40 => static::stringToBitSet($bitSetSize, '100000100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
                48 => static::stringToBitSet($bitSetSize, '10000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
                -48 => static::stringToBitSet($bitSetSize, '10000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'),
            ];
        }

        $bitSet = self::zeroBitSet($this->size);
        foreach ($masks[$this->size] as $amount => $mask) {
            if ($amount < 0) {
                $bitSet = $bitSet->or(
                    $mask->and(
                        $this->bitSet->unshift(abs($amount))
                    )
                );
            } else {
                $bitSet = $bitSet->or(
                    $mask->and(
                        $this->bitSet->shift($amount)
                    )
                );
            }
        }
        return new static($this->size, $bitSet);
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
        return new static($this->size, $this->bitSet->flip());
    }

    public function and(Board $other): Board
    {
        return new static($this->size, $this->bitSet->and(self::boardToBitSet($other)));
    }

    public function or(Board $other): Board
    {
        return new static($this->size, $this->bitSet->or(self::boardToBitSet($other)));
    }

    public function xor(Board $other): Board
    {
        return new static($this->size, $this->bitSet->xor(self::boardToBitSet($other)));
    }

    public function equalsTo(Board $other): bool
    {
        return ($other instanceof self)
            ? $this->bitSet->equalsTo($other->bitSet)
            : $this->toString() === $other->toString();
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

    public function getIterator()
    {
        return $this->bitSet->getIterator();
    }

    public function toArray()
    {
        return $this->bitSet->toArray();
    }

    private function promote(int $type): self
    {
        $promotedBitSet = self::zeroBitSet($this->size);

        for ($srcLayerSize = $this->size; $srcLayerSize > 1; $srcLayerSize--) {
            $dstLayerSize = $srcLayerSize - 1;
            $srcLayerMask = self::layerMask($this->size, $srcLayerSize);
            $srcLayer = $this->bitSet->and($srcLayerMask);
            $promotedLayer = self::zeroBitSet($this->size);

            if ($type & (self::PROMOTE_ZERO | self::PROMOTE_ONE)) {
                $srcLayer = $srcLayer->flip()->and($srcLayerMask);
            }

            if ($type & (self::PROMOTE_ZERO | self::PROMOTE_FOUR)) {
                $p = $srcLayer->and($srcLayer->unshift(1));
                $p = $p->and($p->unshift($srcLayerSize));
                $promotedLayer = $promotedLayer->or($p);
            }

            if ($type & (self::PROMOTE_ONE | self::PROMOTE_THREE)) {
                $p1 = $srcLayer->and($srcLayer->unshift(1));
                $p1 = $p1->xor($p1->unshift($srcLayerSize));
                $p2 = $srcLayer->xor($srcLayer->unshift(1));
                $p2 = $p2->xor($p2->unshift($srcLayerSize));
                $promotedLayer = $promotedLayer->or($p1->and($p2));
            }

            if ($type & self::PROMOTE_TWO) {
                $p1 = $srcLayer->xor($srcLayer->unshift(1));
                $p1 = $p1->and($p1->unshift($srcLayerSize));
                $p2 = $srcLayer->xor($srcLayer->unshift($srcLayerSize));
                $p2 = $p2->and($p2->unshift(1));
                $promotedLayer = $promotedLayer->or($p1)->or($p2);
            }

            if ($type & self::PROMOTE_MAJORITY) {
                $p1 = $srcLayer->and($srcLayer->unshift(1));
                $p1 = $p1->or($p1->unshift($srcLayerSize));
                $p2 = $srcLayer->and($srcLayer->unshift($srcLayerSize));
                $p2 = $p2->or($p2->unshift(1));
                $promotedLayer = $promotedLayer->or($p1->and($p2));
            }

            if ($type & self::PROMOTE_HALF_OR_MORE) {
                $p1 = $srcLayer->or($srcLayer->unshift(1));
                $p1 = $p1->and($p1->unshift($srcLayerSize));
                $p2 = $srcLayer->or($srcLayer->unshift($srcLayerSize));
                $p2 = $p2->and($p2->unshift(1));
                $promotedLayer = $promotedLayer->or($p1)->or($p2);
            }

            for ($i = 0; $i < $dstLayerSize; $i++) {

                static $rowMasks = [];
                if (!isset($rowMasks[$dstLayerSize][$i])) {
                    $rowMask = self::zeroBitSet($this->size)
                        ->set(...range(0, $dstLayerSize - 1))
                        ->shift(self::layerShift($srcLayerSize))
                        ->shift(($srcLayerSize) * $i);
                    $rowMasks[$dstLayerSize][$i] = $rowMask;
                }
                $rowMask = $rowMasks[$dstLayerSize][$i];

                $promotedRow = $promotedLayer->and($rowMask);
                $promotedRow = $promotedRow->unshift($dstLayerSize * $dstLayerSize + $i);
                $promotedBitSet = $promotedBitSet->or($promotedRow);
            }
        }

        return new static($this->size, $promotedBitSet);
    }

    private static function zeroBitSet(int $size): BitSet
    {
        static $bitSets = [];
        return $bitSets[$size] ?? ($bitSets[$size] = static::stringToBitSet(self::bitSetSize($size), '0'));
    }

    private static function oneBitSet(int $size): BitSet
    {
        static $bitSets = [];
        return $bitSets[$size] ?? ($bitSets[$size] = static::stringToBitSet(self::bitSetSize($size), '1'));
    }

    private static function boardToBitSet(Board $board): BitSet
    {
        return ($board instanceof self)
            ? $board->bitSet
            : static::stringToBitSet($board->size(), $board->toString());
    }

    private static function layerMask(int $size, int $layerSize): BitSet
    {
        static $layerMasks = [];

        if (!isset($layerMasks[$size][$layerSize])) {
            $layerMask = self::zeroBitSet($size);
            $j = $layerSize ** 2;
            for ($i = 0; $i < $j; $i++) {
                $layerMask = $layerMask->set($i);
            }
            $layerMask = $layerMask->shift(self::layerShift($layerSize));
            $layerMasks[$size][$layerSize] = $layerMask;
        }

        return $layerMasks[$size][$layerSize];
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

    private static function boardMask(int $size): BitSet
    {
        static $boardMasks = [];

        if (!isset($boardMasks[$size])) {
            $boardMask = self::zeroBitSet($size);
            for ($i = 1; $i <= $size; $i++) {
                $boardMask = $boardMask->or(self::layerMask($size, $i));
            }
            $boardMasks[$size] = $boardMask;
        }

        return $boardMasks[$size];
    }

    private static function bitSetSize(int $size): int
    {
        static $bitSetSizes = [];

        if (!isset($bitSetSizes[$size])) {
            $bitSetSize = 0;
            for ($i = 1; $i <= $size; $i++) {
                $bitSetSize += $i ** 2;
            }
            $bitSetSizes[$size] = $bitSetSize;
        }

        return $bitSetSizes[$size];
    }
}

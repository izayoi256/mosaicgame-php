<?php declare(strict_types=1);
/*
 * This file is part of MosaicGame.
 *
 * (c) Shotaro Hama <qwert.izayoi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MosaicGame\BitSet;

use BadMethodCallException;
use function array_fill;
use function array_key_last;
use function array_map;
use function array_replace;
use function array_slice;
use function array_sum;
use function array_values;
use function array_walk;
use function assert;
use function bindec;
use function ceil;
use function count;
use function decbin;
use function intdiv;
use function is_int;
use function iterator_to_array;
use function preg_match;
use function str_pad;
use function str_repeat;
use function str_split;
use function strrev;
use function substr;
use function substr_count;
use const PHP_INT_SIZE;
use const STR_PAD_LEFT;

final class IntArrayBitSet implements BitSet
{
    /** @var int[] */
    private $ints;

    /** @var int */
    private $size;

    private const BITS_IN_INT = PHP_INT_SIZE * 8 - 1;

    private function __construct(int $size, int ...$ints)
    {
        assert($size >= 0, 'Size must be greater than or equal to 0.');

        $this->size = $size;
        $this->ints = $ints;

        $intsLength = count($this->ints);

        static $requiredIntsList = [];
        if (!isset($requiredIntsList[$size])) {
            $requiredIntsList[$size] = (int)ceil($size / self::BITS_IN_INT);
        }
        $requiredInts = $requiredIntsList[$size];

        if ($intsLength !== $requiredInts) {
            static $zeroInts = [];
            if (!isset($zeroInts[$requiredInts])) {
                $zeroInts[$requiredInts] = array_fill(0, $requiredInts, 0);
            }
            $this->ints = $intsLength > $requiredInts
                ? array_slice($this->ints, 0, $requiredInts)
                : array_replace($zeroInts[$requiredInts], $this->ints);
        }

        static $redundantDigitsList = [];
        if (!isset($redundantDigitsList[$size])) {
            $redundantDigitsList[$size] = $size % self::BITS_IN_INT;
        }
        $redundantDigits = $redundantDigitsList[$size];
        if ($redundantDigits > 0) {
            $lastIndex = array_key_last($this->ints);
            $this->ints[$lastIndex] &= ((1 << $redundantDigits) - 1);
        }

        array_walk($this->ints, static function (int &$int) {
            $int &= ~(1 << self::BITS_IN_INT);
        });
    }

    public static function empty(int $size): self
    {
        static $cache = [];
        if (!isset($cache[$size])) {
            $cache[$size] = new static($size);
        }
        return $cache[$size];
    }

    public static function filled(int $size): self
    {
        static $cache = [];
        if (!isset($cache[$size])) {
            $cache[$size] = self::fromArray($size, array_fill(0, (int)ceil($size / self::BITS_IN_INT), ~0));
        }
        return $cache[$size];
    }

    public static function fromArray(int $size, array $ints): self
    {
        return new self($size, ...array_values($ints));
    }

    public static function fromString(int $size, string $bitsString): self
    {
        assert(preg_match('/\A[01]*\z/', $bitsString), 'Invalid format.');

        $reversed = strrev($bitsString);
        $splitted = str_split($reversed, self::BITS_IN_INT);
        $ints = array_map(static function (string $bits) {
            return bindec(strrev($bits));
        }, $splitted);

        return new self($size, ...$ints);
    }

    public function __toString()
    {
        return $this->__toString();
    }

    public function toString(): string
    {
        $result = '';
        foreach ($this->ints as $int) {
            $result = str_pad(decbin($int), self::BITS_IN_INT, '0', STR_PAD_LEFT) . $result;
        }
        return substr($result, -$this->size, $this->size);
    }

    public function size(): int
    {
        return $this->size;
    }

    public function count()
    {
        return array_sum(array_map(function (int $int) {
            return substr_count(decbin($int), '1');
        }, $this->ints));
    }

    public function set(int ...$offsets): BitSet
    {
        $ints = $this->ints;
        foreach ($offsets as $offset) {
            assert(
                is_int($offset) && 0 <= $offset && $offset <= ($this->size - 1),
                "Undefined offset: {$offset}",
            );
            $i = intdiv($offset, self::BITS_IN_INT);
            $j = $offset % self::BITS_IN_INT;
            $ints[$i] |= 1 << $j;
        }
        return new self($this->size, ...$ints);
    }

    public function setAll(): BitSet
    {
        return self::filled($this->size);
    }

    public function clear(int ...$offsets): BitSet
    {
        $ints = $this->ints;
        foreach ($offsets as $offset) {
            assert(
                is_int($offset) && 0 <= $offset && $offset <= ($this->size - 1),
                "Undefined offset: {$offset}",
            );
            $i = intdiv($offset, self::BITS_IN_INT);
            $j = $offset % self::BITS_IN_INT;
            $ints[$i] &= ~(1 << $j);
        }
        return new self($this->size, ...$ints);
    }

    public function clearAll(): BitSet
    {
        return self::empty($this->size);
    }

    public function and(BitSet $other): BitSet
    {
        $otherInts = ($other instanceof self)
            ? $other->ints
            : self::fromString($other->size(), $other->toString())->ints;
        $ints = array_map(function (int $a, int $b) {
            return $a & $b;
        }, $this->ints, $otherInts);
        return new self($this->size, ...$ints);
    }

    public function or(BitSet $other): BitSet
    {
        $otherInts = ($other instanceof self)
            ? $other->ints
            : self::fromString($other->size(), $other->toString())->ints;
        $ints = array_map(function (int $a, int $b) {
            return $a | $b;
        }, $this->ints, $otherInts);
        return new self($this->size, ...$ints);
    }

    public function xor(BitSet $other): BitSet
    {
        $otherInts = ($other instanceof self)
            ? $other->ints
            : self::fromString($other->size(), $other->toString())->ints;
        $ints = array_map(function (int $a, int $b) {
            return $a ^ $b;
        }, $this->ints, $otherInts);
        return new self($this->size, ...$ints);
    }

    public function flip(): BitSet
    {
        return static::fromString($this->size, strtr($this->toString(), ['0' => '1', '1' => '0']));
    }

    public function lshift(int $amount): BitSet
    {
        assert($amount >= 0, "Illegal shift amount: {$amount}");

        return self::fromString($this->size, $this->toString() . str_repeat('0', $amount));
    }

    public function rshift(int $amount): BitSet
    {
        assert($amount >= 0, "Illegal shift amount: {$amount}");

        return self::fromString($this->size, substr($this->toString(), 0, $this->size - $amount));
    }

    public function equalsTo(BitSet $other): bool
    {
        return ($other instanceof self && $this->ints === $other->ints) ||
            $this->toString() === $other->toString();
    }

    public function offsetExists($offset)
    {
        return is_int($offset) && 0 <= $offset && $offset <= ($this->size - 1);
    }

    public function offsetGet($offset)
    {
        assert(
            is_int($offset) && 0 <= $offset && $offset <= ($this->size - 1),
            "Undefined offset: {$offset}",
        );
        $i = intdiv($offset, self::BITS_IN_INT);
        $j = $offset % self::BITS_IN_INT;
        return ($this->ints[$i] & (1 << $j)) !== 0;
    }

    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('BitSet is immutable.');
    }

    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('BitSet is immutable.');
    }

    public function getIterator()
    {
        yield from [];
        for ($i = 0; $i < $this->size; $i++) {
            yield $i => $this[$i];
        }
    }

    public function toArray()
    {
        return iterator_to_array($this);
    }
}

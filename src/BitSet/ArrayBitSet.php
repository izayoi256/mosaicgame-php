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
use InvalidArgumentException;
use OutOfRangeException;
use function array_diff_key;
use function array_fill;
use function array_fill_keys;
use function array_filter;
use function array_intersect_key;
use function array_keys;
use function array_map;
use function array_reverse;
use function count;
use function implode;
use function is_int;
use function iterator_to_array;
use function preg_match;
use function strlen;
use function strtr;
use function substr;
use const ARRAY_FILTER_USE_KEY;

final class ArrayBitSet implements BitSet
{
    /** @var mixed[] */
    private $bits;

    /** @var int */
    private $size;

    private function __construct(int $size, array $bits = [])
    {
        if ($size < 0) {
            throw new InvalidArgumentException('ArrayBitSet size must be greater than or equal to 0.');
        }

        $this->size = $size;
        $this->bits = array_filter($bits, static function (int $offset) use ($size) {
            return 0 <= $offset && $offset <= ($size - 1);
        }, ARRAY_FILTER_USE_KEY);
    }

    public static function empty(int $size): self
    {
        return new static($size);
    }

    public static function filled(int $size): self
    {
        return self::fromArray($size, array_fill(0, $size, true));
    }

    public static function fromArray(int $size, array $array): self
    {
        return new static($size, array_filter($array));
    }

    public static function fromString(int $size, string $bitsString): self
    {
        if (!preg_match('/\A[01]*\z/', $bitsString)) {
            throw new InvalidArgumentException('Invalid format.');
        }

        $iterator = (static function () use ($size, $bitsString) {
            yield from [];
            $length = strlen($bitsString);
            for ($i = 0; $i < $size; $i++) {
                if (($i + 1) > $length) {
                    break;
                }
                if (substr($bitsString, (-1 - $i), 1) === '1') {
                    yield $i => $i;
                }
            }
        })();

        return new static($size, iterator_to_array($iterator));
    }

    public function __toString()
    {
        return $this->toString();
    }

    public function toString(): string
    {
        $iterator = (function () {
            yield from [];
            for ($i = 0; $i < $this->size; $i++) {
                yield isset($this->bits[$i])
                    ? '1'
                    : '0';
            }
        })();
        return implode('', array_reverse(iterator_to_array($iterator)));
    }

    public function size(): int
    {
        return $this->size;
    }

    public function count(): int
    {
        return count($this->bits);
    }

    public function set(int ...$offsets): BitSet
    {
        $bits = $this->bits;
        foreach ($offsets as $offset) {
            $this->assertOffset($offset);
            $bits[$offset] = $offset;
        }
        return $this->withBits($bits);
    }

    public function setAll(): BitSet
    {
        return $this->withBits(array_fill(0, $this->size, true));
    }

    public function clear(int ...$offsets): BitSet
    {
        $bits = $this->bits;
        foreach ($offsets as $offset) {
            $this->assertOffset($offset);
            unset($bits[$offset]);
        }
        return $this->withBits($bits);
    }

    public function clearAll(): BitSet
    {
        return $this->withBits([]);
    }

    public function and(BitSet $other): BitSet
    {
        return $this->withBits(array_intersect_key($this->bits, static::bitSetToBits($other)));
    }

    public function or(BitSet $other): BitSet
    {
        return $this->withBits($this->bits + static::bitSetToBits($other));
    }

    public function xor(BitSet $other): BitSet
    {
        $otherBits = static::bitSetToBits($other);
        return $this->withBits(array_diff_key($this->bits, $otherBits) + array_diff_key($otherBits, $this->bits));
    }

    private static function bitSetToBits(BitSet $bitSet): array
    {
        return ($bitSet instanceof self)
            ? $bitSet->bits
            : array_filter(iterator_to_array($bitSet));
    }

    public function flip(): BitSet
    {
        return static::fromString($this->size, strtr($this->toString(), ['0' => '1', '1' => '0']));
    }

    public function shift(int $amount): BitSet
    {
        static::assertShiftAmount($amount);

        $bits = array_map(static function (int $offset) use ($amount) {
            return $offset + $amount;
        }, array_keys($this->bits));
        return $this->withBits(array_fill_keys($bits, true));
    }

    public function unshift(int $amount): BitSet
    {
        static::assertShiftAmount($amount);

        $bits = array_map(static function (int $offset) use ($amount) {
            return $offset - $amount;
        }, array_keys($this->bits));
        return $this->withBits(array_fill_keys($bits, true));
    }

    public function equalsTo(BitSet $other): bool
    {
        $otherBits = static::bitSetToBits($other);
        return !array_diff_key($this->bits, $otherBits) && !array_diff_key($otherBits, $this->bits);
    }

    private function assertOffset($offset): void
    {
        if (!is_int($offset) || $offset < 0 || $offset > ($this->size - 1)) {
            throw new OutOfRangeException("Undefined offset: {$offset}");
        }
    }

    private static function assertShiftAmount(int $amount): void
    {
        if ($amount < 0) {
            throw new OutOfRangeException("Illegal shift amount: {$amount}");
        }
    }

    private function withBits(array $bits): self
    {
        return new static($this->size, $bits);
    }

    public function offsetExists($offset)
    {
        try {
            $this->assertOffset($offset);
        } catch (OutOfRangeException $e) {
            return false;
        }
        return true;
    }

    public function offsetGet($offset)
    {
        $this->assertOffset($offset);
        return isset($this->bits[$offset]);
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
            yield $i => isset($this->bits[$i]);
        }
    }

    public function toArray()
    {
        return iterator_to_array($this);
    }
}

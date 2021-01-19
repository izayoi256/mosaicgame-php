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
use function array_diff_key;
use function array_fill;
use function array_fill_keys;
use function array_filter;
use function array_intersect_key;
use function array_keys;
use function array_map;
use function array_reverse;
use function assert;
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
        assert($size >= 0, 'ArrayBitSet size must be greater than or equal to 0.');

        $this->size = $size;
        $this->bits = array_filter($bits, static function (int $offset) use ($size) {
            return 0 <= $offset && $offset <= ($size - 1);
        }, ARRAY_FILTER_USE_KEY);
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
            $cache[$size] = self::fromArray($size, array_fill(0, $size, true));
        }
        return $cache[$size];
    }

    public static function fromArray(int $size, array $array): self
    {
        return new static($size, array_filter($array));
    }

    public static function fromString(int $size, string $bitsString): self
    {
        assert(preg_match('/\A[01]*\z/', $bitsString), 'Invalid format.');

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
            assert(
                is_int($offset) && 0 <= $offset && $offset <= ($this->size - 1),
                "Undefined offset: {$offset}",
            );
            $bits[$offset] = $offset;
        }
        return new self($this->size, $bits);
    }

    public function setAll(): BitSet
    {
        return new self($this->size, array_fill(0, $this->size, true));
    }

    public function clear(int ...$offsets): BitSet
    {
        $bits = $this->bits;
        foreach ($offsets as $offset) {
            assert(
                is_int($offset) && 0 <= $offset && $offset <= ($this->size - 1),
                "Undefined offset: {$offset}",
            );
            unset($bits[$offset]);
        }
        return new self($this->size, $bits);
    }

    public function clearAll(): BitSet
    {
        return new self($this->size, []);
    }

    public function and(BitSet $other): BitSet
    {
        $otherBits = ($other instanceof self)
            ? $other->bits
            : array_filter(iterator_to_array($other));
        return new self($this->size, array_intersect_key($this->bits, $otherBits));
    }

    public function or(BitSet $other): BitSet
    {
        $otherBits = ($other instanceof self)
            ? $other->bits
            : array_filter(iterator_to_array($other));
        return new self($this->size, $this->bits + $otherBits);
    }

    public function xor(BitSet $other): BitSet
    {
        $otherBits = ($other instanceof self)
            ? $other->bits
            : array_filter(iterator_to_array($other));
        return new self($this->size, array_diff_key($this->bits, $otherBits) + array_diff_key($otherBits, $this->bits));
    }

    public function flip(): BitSet
    {
        return static::fromString($this->size, strtr($this->toString(), ['0' => '1', '1' => '0']));
    }

    public function lshift(int $amount): BitSet
    {
        assert($amount >= 0, "Illegal shift amount: {$amount}");

        $bits = array_map(static function (int $offset) use ($amount) {
            return $offset + $amount;
        }, array_keys($this->bits));
        return new self($this->size, array_fill_keys($bits, true));
    }

    public function rshift(int $amount): BitSet
    {
        assert($amount >= 0, "Illegal shift amount: {$amount}");

        $bits = array_map(static function (int $offset) use ($amount) {
            return $offset - $amount;
        }, array_keys($this->bits));
        return new self($this->size, array_fill_keys($bits, true));
    }

    public function equalsTo(BitSet $other): bool
    {
        $otherBits = ($other instanceof self)
            ? $other->bits
            : array_filter(iterator_to_array($other));
        return !array_diff_key($this->bits, $otherBits) && !array_diff_key($otherBits, $this->bits);
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

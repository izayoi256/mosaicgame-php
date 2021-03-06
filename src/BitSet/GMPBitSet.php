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
use GMP;
use function assert;
use function gmp_and;
use function gmp_clrbit;
use function gmp_cmp;
use function gmp_com;
use function gmp_init;
use function gmp_or;
use function gmp_popcount;
use function gmp_scan1;
use function gmp_setbit;
use function gmp_strval;
use function gmp_sub;
use function gmp_testbit;
use function gmp_xor;
use function is_int;
use function iterator_to_array;
use function str_pad;
use function str_repeat;
use const STR_PAD_LEFT;

final class GMPBitSet implements BitSet
{
    /** @var int */
    private $size;

    /** @var GMP */
    private $gmp;

    private function __construct(int $size, GMP $gmp)
    {
        assert($size >= 0, 'GMPBitSet size must be greater than or equal to 0.');

        $this->size = $size;
        $this->gmp = $gmp;
        $this->applySizeMask();
    }

    private function applySizeMask(): void
    {
        if (gmp_scan1($this->gmp, $this->size) !== -1) {
            static $cache = [];
            if (!isset($cache[$this->size])) {
                $zeros = str_repeat('0', $this->size);
                $cache[$this->size] = gmp_sub(gmp_init("0b1{$zeros}"), 1);
            }
            $this->gmp = gmp_and($this->gmp, $cache[$this->size]);
        }
    }

    public static function fromGMP(int $size, GMP $gmp): self
    {
        return new self($size, $gmp);
    }

    public static function empty(int $size): self
    {
        static $cache = [];
        if (!isset($cache[$size])) {
            $cache[$size] = self::fromGMP($size, gmp_init(0));
        }
        return $cache[$size];
    }

    public static function filled(int $size): self
    {
        static $cache = [];
        if (!isset($cache[$size])) {
            $zeros = str_repeat('0', $size);
            $cache[$size] = self::fromGMP($size, gmp_sub(gmp_init("0b1{$zeros}"), 1));
        }
        return $cache[$size];
    }

    public function __toString()
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return str_pad(gmp_strval($this->gmp, 2), $this->size, '0', STR_PAD_LEFT);
    }

    public function size(): int
    {
        return $this->size;
    }

    public function getIterator()
    {
        yield from [];
        for ($i = 0; $i < $this->size; $i++) {
            yield $i => gmp_testbit($this->gmp, $i);
        }
    }

    public function toArray()
    {
        return iterator_to_array($this);
    }

    public function count()
    {
        return gmp_popcount($this->gmp);
    }

    public function equalsTo(BitSet $other): bool
    {
        return gmp_cmp(
                $this->gmp,
                ($other instanceof self)
                    ? $other->gmp
                    : gmp_init($other->toString(), 2)
            ) === 0;
    }

    public function set(int ...$offsets): BitSet
    {
        $gmp = clone $this->gmp;
        foreach ($offsets as $offset) {
            assert(
                is_int($offset) && 0 <= $offset && $offset <= ($this->size - 1),
                "Undefined offset: {$offset}",
            );
            gmp_setbit($gmp, $offset);
        }
        $clone = clone $this;
        $clone->gmp = $gmp;
        $clone->applySizeMask();
        return $clone;
    }

    public function setAll(): BitSet
    {
        $clone = clone $this;
        $clone->gmp = gmp_init(str_repeat('1', $this->size), 2);
        $clone->applySizeMask();
        return $clone;
    }

    public function clear(int ...$offsets): BitSet
    {
        $gmp = clone $this->gmp;
        foreach ($offsets as $offset) {
            assert(
                is_int($offset) && 0 <= $offset && $offset <= ($this->size - 1),
                "Undefined offset: {$offset}",
            );
            gmp_clrbit($gmp, $offset);
        }
        $clone = clone $this;
        $clone->gmp = $gmp;
        $clone->applySizeMask();
        return $clone;
    }

    public function clearAll(): BitSet
    {
        return static::empty($this->size);
    }

    public function and(BitSet $other): BitSet
    {
        $clone = clone $this;
        $clone->gmp = gmp_and($this->gmp, ($other instanceof self)
            ? $other->gmp
            : gmp_init($other->toString(), 2));
        $clone->applySizeMask();
        return $clone;
    }

    public function or(BitSet $other): BitSet
    {
        $clone = clone $this;
        $clone->gmp = gmp_or($this->gmp, ($other instanceof self)
            ? $other->gmp
            : gmp_init($other->toString(), 2));
        $clone->applySizeMask();
        return $clone;
    }

    public function xor(BitSet $other): BitSet
    {
        $clone = clone $this;
        $clone->gmp = gmp_xor($this->gmp, ($other instanceof self)
            ? $other->gmp
            : gmp_init($other->toString(), 2));
        $clone->applySizeMask();
        return $clone;
    }

    public function flip(): BitSet
    {
        $clone = clone $this;
        $clone->gmp = gmp_com($this->gmp);
        $clone->applySizeMask();
        return $clone;
    }

    public function lshift(int $amount): BitSet
    {
        assert($amount >= 0, "Illegal shift amount: {$amount}");
        $clone = clone $this;
        $clone->gmp = $this->gmp << $amount;
        $clone->applySizeMask();
        return $clone;
    }

    public function rshift(int $amount): BitSet
    {
        assert($amount >= 0, "Illegal shift amount: {$amount}");
        $clone = clone $this;
        $clone->gmp = $this->gmp >> $amount;
        $clone->applySizeMask();
        return $clone;
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
        return gmp_testbit($this->gmp, $offset);
    }

    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('BitSet is immutable.');
    }

    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('BitSet is immutable.');
    }
}

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
use OutOfRangeException;
use function assert;
use function gmp_and;
use function gmp_clrbit;
use function gmp_cmp;
use function gmp_com;
use function gmp_init;
use function gmp_popcount;
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

        static $cache = [];
        if (!isset($cache[$size])) {
            $zeros = str_repeat('0', $size);
            $cache[$size] = gmp_sub(gmp_init("0b1{$zeros}"), 1);
        }
        $this->gmp = gmp_and($gmp, $cache[$size]);
    }

    public static function fromGMP(int $size, GMP $gmp): self
    {
        return new self($size, $gmp);
    }

    public static function empty(int $size): self
    {
        return self::fromGMP($size, gmp_init(0));
    }

    public static function filled(int $size): self
    {
        $zeros = str_repeat('0', $size);
        return self::fromGMP($size, gmp_sub(gmp_init("0b1{$zeros}"), 1));
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
        return gmp_cmp($this->gmp, self::bitSetToGMP($other)) === 0;
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
        return $this->withGMP($gmp);
    }

    public function setAll(): BitSet
    {
        return $this->withGMP(gmp_init(str_repeat('1', $this->size), 2));
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
        return $this->withGMP($gmp);
    }

    public function clearAll(): BitSet
    {
        return static::empty($this->size);
    }

    public function and(BitSet $other): BitSet
    {
        return $this->withGMP(gmp_and($this->gmp, self::bitSetToGMP($other)));
    }

    public function or(BitSet $other): BitSet
    {
        return $this->withGMP(gmp_or($this->gmp, self::bitSetToGMP($other)));
    }

    public function xor(BitSet $other): BitSet
    {
        return $this->withGMP(gmp_xor($this->gmp, self::bitSetToGMP($other)));
    }

    public function flip(): BitSet
    {
        return $this->withGMP(gmp_com($this->gmp));
    }

    public function shift(int $amount): BitSet
    {
        assert($amount >= 0, "Illegal shift amount: {$amount}");
        return $this->withGMP($this->gmp << $amount);
    }

    public function unshift(int $amount): BitSet
    {
        assert($amount >= 0, "Illegal shift amount: {$amount}");
        return $this->withGMP($this->gmp >> $amount);
    }

    private static function bitSetToGMP(BitSet $bitSet): GMP
    {
        return ($bitSet instanceof self)
            ? $bitSet->gmp
            : gmp_init($bitSet->toString(), 2);
    }

    private function withGMP(GMP $gmp): self
    {
        return self::fromGMP($this->size, $gmp);
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

    private static function assertShiftAmount(int $amount): void
    {
        if ($amount < 0) {
            throw new OutOfRangeException("Illegal shift amount: {$amount}");
        }
    }
}

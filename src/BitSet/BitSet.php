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

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * This interface must be implemented as immutable class.
 */
interface BitSet extends ArrayAccess, Countable, IteratorAggregate
{
    public function __toString();

    public function toString(): string;

    public function size(): int;

    /**
     * Return an iterator of the bitset in the format [offset => boolean].
     *
     * @return Traversable
     */
    public function getIterator();

    /**
     * Return an array of the bitset in the format [offset => boolean].
     *
     * @return bool[]
     */
    public function toArray();

    /**
     * Return the count of set bits.
     *
     * @return int
     */
    public function count();

    /**
     * Return if the bitset of this instance equals to others.
     * This method may return true even if they are not the same instance.
     *
     * @param self $other
     * @return bool
     */
    public function equalsTo(self $other): bool;

    /**
     * Return an instance with the specified offsets turned to 1.
     * This method MUST be implemented in a way to keep immutability.
     *
     * @param int ...$offsets
     * @return self
     */
    public function set(int ...$offsets): self;

    /**
     * Return an instance with all offsets turned to 1.
     * This method MUST be implemented in a way to keep immutability.
     *
     * @return self
     */
    public function setAll(): self;

    /**
     * Return an instance with the specified offsets turned to 0.
     * This method MUST be implemented in a way to keep immutability.
     *
     * @param int ...$offsets
     * @return self
     */
    public function clear(int ...$offsets): self;

    /**
     * Return an instance with all offsets turned to 0.
     * This method MUST be implemented in a way to keep immutability.
     *
     * @return self
     */
    public function clearAll(): self;

    /**
     * Return an instance with the result of an AND operation.
     * This method MUST be implemented in a way to keep immutability.
     *
     * @param self $other
     * @return self
     */
    public function and(self $other): self;

    /**
     * Return an instance with the result of an OR operation.
     * This method MUST be implemented in a way to keep immutability.
     *
     * @param self $other
     * @return self
     */
    public function or(self $other): self;

    /**
     * Return an instance with the result of an XOR operation.
     * This method MUST be implemented in a way to keep immutability.
     *
     * @param self $other
     * @return self
     */
    public function xor(self $other): self;

    /**
     * Return an instance with the flipped bits.
     * This method MUST be implemented in a way to keep immutability.
     *
     * @return self
     */
    public function flip(): self;

    /**
     * Return an instance with the result of a LEFT SHIFT operation.
     * This method MUST be implemented in a way to keep immutability.
     *
     * @param int $amount
     * @return self
     */
    public function lshift(int $amount): self;

    /**
     * Return an instance with the result of a RIGHT SHIFT operation.
     * This method MUST be implemented in a way to keep immutability.
     *
     * @param int $amount
     * @return self
     */
    public function rshift(int $amount): self;
}

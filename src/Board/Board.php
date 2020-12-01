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

use Countable;
use Traversable;

/**
 * This interface must be implemented as immutable class.
 */
interface Board extends Countable
{
    public function __toString();

    public function toString(): string;

    /**
     * Return an iterator of the board in the format [offset => boolean].
     *
     * @return Traversable
     */
    public function getIterator();

    /**
     * Return an array of the board in the format [offset => boolean].
     *
     * @return bool[]
     */
    public function toArray();

    /**
     * Return the count of set cells.
     *
     * @return int
     */
    public function count(): int;

    public function size(): int;

    /**
     * Return a horizontally mirrored instance.
     * This method MUST be implemented in a way to keep immutability.
     *
     * @return self
     */
    public function mirrorHorizontal(): self;

    /**
     * Return a vertically flipped instance.
     * This method MUST be implemented in a way to keep immutability.
     *
     * @return self
     */
    public function flipVertical(): self;

    /**
     * Return a diagonally flipped instance.
     * This method MUST be implemented in a way to keep immutability.
     *
     * @return self
     */
    public function flipDiagonal(): self;

    /**
     * Return a 90 degrees rotated instance.
     * This method MUST be implemented in a way to keep immutability.
     *
     * @return self
     */
    public function rotate90(): self;

    /**
     * Return a 180 degrees rotated instance.
     * This method MUST be implemented in a way to keep immutability.
     *
     * @return self
     */
    public function rotate180(): self;

    /**
     * Return a 270 degrees rotated instance.
     * This method MUST be implemented in a way to keep immutability.
     *
     * @return self
     */
    public function rotate270(): self;

    /**
     * Return an instance with the flipped cells.
     * This method MUST be implemented in a way to keep immutability.
     *
     * @return self
     */
    public function flip(): self;

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
     * Return an instance with promotion result by zero.
     *
     * @return self
     */
    public function promoteZero(): self;

    /**
     * Return an instance with promotion result by one.
     *
     * @return self
     */
    public function promoteOne(): self;

    /**
     * Return an instance with promotion result by two.
     *
     * @return self
     */
    public function promoteTwo(): self;

    /**
     * Return an instance with promotion result by three.
     *
     * @return self
     */
    public function promoteThree(): self;

    /**
     * Return an instance with promotion result by four.
     *
     * @return self
     */
    public function promoteFour(): self;

    /**
     * Return an instance with promotion result by majority.
     *
     * @return self
     */
    public function promoteMajority(): self;

    /**
     * Return if the board of this instance equals to others.
     *
     * This method may return true even if they are not the same instance.
     *
     * @param self $other
     * @return bool
     */
    public function equalsTo(self $other): bool;
}

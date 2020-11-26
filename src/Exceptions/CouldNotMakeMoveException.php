<?php declare(strict_types=1);
/*
 * This file is part of MosaicGame.
 *
 * (c) Shotaro Hama <qwert.izayoi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MosaicGame\Exceptions;

use DomainException;
use MosaicGame\Game\Move\Move;
use function sprintf;

class CouldNotMakeMoveException extends DomainException
{
    public static function gameIsAlreadyOver(): self
    {
        return new self('The game is already over.');
    }

    public static function invalidMove(Move $move): self
    {
        return new self(sprintf('Invalid move: %s', $move->toOffset()));
    }
}

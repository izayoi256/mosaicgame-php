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

class CouldNotRedoException extends DomainException
{
    public static function noMoreRedoableMoves(): self
    {
        return new self('You can\'t redo the game anymore.');
    }
}

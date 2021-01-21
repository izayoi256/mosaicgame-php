<?php declare(strict_types=1);
/*
 * This file is part of MosaicGame.
 *
 * (c) Shotaro Hama <qwert.izayoi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MosaicGame\Test\Game\Move;

use MosaicGame\Board\GMPBoard;
use MosaicGame\Game\Move\GMPMove;
use MosaicGame\Game\Move\Move;
use PHPUnit\Framework\TestCase;
use function array_map;

final class GMPMoveTest extends TestCase
{
    public function testToBoard()
    {
        $move = GMPMove::fromOffset(5);
        $board = $move->toBoard(7);
        $this->assertSame(sprintf('%0140s', '100000'), $board->toString());
    }

    public function testFromBoard()
    {
        $board = GMPBoard::fromString(7, '1100000101001');
        $moveOffsets = array_map(static function (Move $move) {
            return $move->toOffset();
        }, GMPMove::fromBoard($board));
        $expected = [
            0,
            3,
            5,
            11,
            12
        ];
        $this->assertSame($expected, $moveOffsets);
    }
}

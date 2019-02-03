<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\CallGraph;

use Innmind\Debug\{
    CallGraph\Node,
    Exception\LogicException,
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PointInTime\Earth\PointInTime,
};
use PHPUnit\Framework\TestCase;

class NodeTest extends TestCase
{
    public function testNormalizeWhenNeverEnteredACall()
    {
        $clock = $this->createMock(TimeContinuumInterface::class);
        $clock
            ->expects($this->at(0))
            ->method('now')
            ->willReturn(new PointInTime('2019-01-01T12:00:00.000+0000'));
        $clock
            ->expects($this->at(1))
            ->method('now')
            ->willReturn(new PointInTime('2019-01-01T12:00:00.042+0000'));
        $root = Node::root(
            $clock,
            'root'
        );

        $this->assertSame(
            [
                'name' => 'root',
                'value' => 42,
                'children' => [],
            ],
            $root->normalize()
        );
    }

    public function testNestingCalls()
    {
        $clock = $this->createMock(TimeContinuumInterface::class);
        $clock
            ->expects($this->at(0))
            ->method('now')
            ->willReturn(new PointInTime('2019-01-01T12:00:00.000+0000')); // enter root
        $clock
            ->expects($this->at(1))
            ->method('now')
            ->willReturn(new PointInTime('2019-01-01T12:00:00.000+0000')); // enter call 1
        $clock
            ->expects($this->at(2))
            ->method('now')
            ->willReturn(new PointInTime('2019-01-01T12:00:00.000+0000')); // enter call 2
        $clock
            ->expects($this->at(3))
            ->method('now')
            ->willReturn(new PointInTime('2019-01-01T12:00:00.025+0000')); // leave call 2
        $clock
            ->expects($this->at(4))
            ->method('now')
            ->willReturn(new PointInTime('2019-01-01T12:00:00.050+0000')); // leave call 1
        $clock
            ->expects($this->at(5))
            ->method('now')
            ->willReturn(new PointInTime('2019-01-01T12:00:00.060+0000')); // enter call 3
        $clock
            ->expects($this->at(6))
            ->method('now')
            ->willReturn(new PointInTime('2019-01-01T12:00:00.090+0000')); // leave call 3
        $clock
            ->expects($this->at(7))
            ->method('now')
            ->willReturn(new PointInTime('2019-01-01T12:00:00.100+0000'));
        $root = Node::root(
            $clock,
            'root'
        );

        $root->enter('call 1');
        $root->enter('call 2');
        $root->leave();
        $root->leave();
        $root->enter('call 3');
        $root->leave();
        $root->end();

        $this->assertSame(
            [
                'name' => 'root',
                'value' => 100,
                'children' => [
                    [
                        'name' => 'call 1',
                        'value' => 50,
                        'children' => [
                            [
                                'name' => 'call 2',
                                'value' => 25,
                                'children' => [],
                            ],
                        ],
                    ],
                    [
                        'name' => 'call 3',
                        'value' => 30,
                        'children' => [],
                    ],
                ],
            ],
            $root->normalize()
        );
    }

    public function testNodeAreAutomaticallyEndedAtNormalization()
    {
        $clock = $this->createMock(TimeContinuumInterface::class);
        $clock
            ->expects($this->at(0))
            ->method('now')
            ->willReturn(new PointInTime('2019-01-01T12:00:00.000+0000')); // enter root
        $clock
            ->expects($this->at(1))
            ->method('now')
            ->willReturn(new PointInTime('2019-01-01T12:00:00.000+0000')); // enter call 1
        $clock
            ->expects($this->at(2))
            ->method('now')
            ->willReturn(new PointInTime('2019-01-01T12:00:00.000+0000')); // enter call 2
        $clock
            ->expects($this->at(3))
            ->method('now')
            ->willReturn(new PointInTime('2019-01-01T12:00:00.025+0000')); // leave call 2
        $clock
            ->expects($this->at(4))
            ->method('now')
            ->willReturn(new PointInTime('2019-01-01T12:00:00.050+0000')); // leave call 1
        $clock
            ->expects($this->at(5))
            ->method('now')
            ->willReturn(new PointInTime('2019-01-01T12:00:00.100+0000'));
        $root = Node::root(
            $clock,
            'root'
        );

        $root->enter('call 1');
        $root->enter('call 2');

        $this->assertSame(
            [
                'name' => 'root',
                'value' => 100,
                'children' => [
                    [
                        'name' => 'call 1',
                        'value' => 50,
                        'children' => [
                            [
                                'name' => 'call 2',
                                'value' => 25,
                                'children' => [],
                            ],
                        ],
                    ],
                ],
            ],
            $root->normalize()
        );
    }

    public function testThrowWhenEnteringACallWhenGraphEnded()
    {
        $root = Node::root(
            $this->createMock(TimeContinuumInterface::class),
            'root'
        );

        $root->end();

        $this->expectException(LogicException::class);

        $root->enter('foo');
    }

    public function testThrowWhenLeavingACallWhenGraphEnded()
    {
        $root = Node::root(
            $this->createMock(TimeContinuumInterface::class),
            'root'
        );

        $root->enter('foo');
        $root->end();

        $this->expectException(LogicException::class);

        $root->leave();
    }
}

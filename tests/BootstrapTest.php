<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug;

use function Innmind\Debug\bootstrap;
use Innmind\Debug\{
    Profiler,
    HttpFramework,
    CLI,
    OperatingSystem,
    CallGraph,
    CommandBus,
    EventBus,
    Closure,
    CodeEditor,
};
use Innmind\OperatingSystem\Factory;
use Innmind\Url\Url;
use Innmind\HttpFramework\{
    RequestHandler,
    Controller,
};
use Innmind\CLI\Command;
use Innmind\ObjectGraph\{
    Graph,
    Assert\Stack,
    Visualize,
    LocationRewriter,
};
use Innmind\CommandBus\CommandBus as CommandBusInterface;
use Innmind\EventBus\EventBus as EventBusInterface;
use Innmind\StackTrace\{
    Render,
    Link,
};
use Innmind\Immutable\StreamInterface;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testInterface()
    {
        $debug = bootstrap(
            Factory::build(),
            Url::fromString('http://localhost:8000/')
        );

        $this->assertIsArray($debug);
        $this->assertIsCallable($debug['profiler']);
        $this->assertIsCallable($debug['http']);
        $this->assertIsCallable($debug['cli']);
        $this->assertIsCallable($debug['os']);
        $this->assertIsCallable($debug['call_graph']);
        $this->assertIsCallable($debug['controller']);
        $this->assertIsCallable($debug['command_bus']);
        $this->assertIsCallable($debug['event_bus']);
        $this->assertIsCallable($debug['callable']);

        $this->assertInstanceOf(Profiler::class, $debug['profiler']());

        $stack = Stack::of(
            OperatingSystem\CallGraph\OperatingSystem::class,
            OperatingSystem\Debug\OperatingSystem::class
        );
        $this->assertTrue($stack((new Graph)($debug['os']())));

        $this->assertInstanceOf(CallGraph::class, $debug['call_graph']());
        $this->assertInstanceOf(
            Controller::class,
            $debug['controller']($this->createMock(Controller::class))
        );
        $this->assertInstanceOf(
            CommandBus\CaptureCallGraph::class,
            $debug['command_bus']($this->createMock(CommandBusInterface::class))
        );
        $this->assertInstanceOf(
            EventBus\CaptureCallGraph::class,
            $debug['event_bus']($this->createMock(EventBusInterface::class))
        );
        $this->assertInstanceOf(
            Closure\CaptureCallGraph::class,
            $debug['callable'](function(){})
        );

        $handler = $debug['http']($this->createMock(RequestHandler::class));
        $this->assertInstanceOf(RequestHandler::class, $handler);
        $stack = Stack::of(
            HttpFramework\StartProfile::class,
            HttpFramework\CaptureHttp::class,
            HttpFramework\CaptureException::class,
            HttpFramework\StartCallGraph::class,
            HttpFramework\CaptureAppGraph::class
        );
        $this->assertTrue($stack((new Graph)($handler)));

        $commands = $debug['cli'](
            $this->createMock(Command::class),
            $this->createMock(Command::class)
        );
        $this->assertInstanceOf(StreamInterface::class, $commands);
        $this->assertSame(Command::class, (string) $commands->type());
        $this->assertCount(2, $commands);
        $command = $commands->first();
        $this->assertInstanceOf(Command::class, $command);
        $stack = Stack::of(
            CLI\StartProfile::class,
            CLI\CaptureException::class,
            CLI\StartCallGraph::class,
            CLI\CaptureAppGraph::class
        );
        $this->assertTrue($stack((new Graph)($command)));
    }

    public function testUseSublimeSchemeToRenderGraphWhenUsingSublimeTextEditor()
    {
        $debug = bootstrap(
            Factory::build(),
            Url::fromString('http://localhost:8000/'),
            null,
            CodeEditor::sublimeText()
        );

        $handler = $debug['http']($this->createMock(RequestHandler::class));

        $stack = Stack::of(
            Render::class,
            Link\SublimeHandler::class
        );
        $this->assertTrue($stack((new Graph)($handler)));

        $stack = Stack::of(
            Visualize::class,
            LocationRewriter\SublimeHandler::class
        );
        $this->assertTrue($stack((new Graph)($handler)));
    }

    public function testDoesntUseSublimeSchemeToRenderGraphWhenNoTextEditorSpecified()
    {
        $debug = bootstrap(
            Factory::build(),
            Url::fromString('http://localhost:8000/')
        );

        $handler = $debug['http']($this->createMock(RequestHandler::class));

        $stack = Stack::of(
            Render::class,
            Link\SublimeHandler::class
        );
        $this->assertFalse($stack((new Graph)($handler)));

        $stack = Stack::of(
            Visualize::class,
            LocationRewriter\SublimeHandler::class
        );
        $this->assertFalse($stack((new Graph)($handler)));
    }
}

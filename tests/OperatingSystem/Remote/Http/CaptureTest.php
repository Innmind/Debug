<?php
declare(strict_types = 1);

namespace Tests\Innmind\Debug\OperatingSystem\Remote\Capture;

use Innmind\Debug\{
    OperatingSystem\Remote\Http\Capture,
    CallGraph,
    Profiler\Section\CaptureCallGraph,
    Profiler\Profile\Identity,
};
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Message\Request\Request,
    Message\Response\Response,
    Message\StatusCode\StatusCode,
    Message\Method\Method,
    ProtocolVersion\ProtocolVersion,
};
use Innmind\Url\Url;
use Innmind\Rest\Client\Server;
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Json\Json;
use PHPUnit\Framework\TestCase;

class CaptureTest extends TestCase
{
    public function testInterface()
    {
        $transport = new Capture(
            $inner = $this->createMock(Transport::class),
            $graph = new CallGraph(
                $section = new CaptureCallGraph(
                    $server = $this->createMock(Server::class)
                ),
                $this->createMock(TimeContinuumInterface::class)
            )
        );
        $request = new Request(
            Url::fromString('http://example.com/foo'),
            Method::get(),
            new ProtocolVersion(2, 0)
        );
        $response = new Response(
            $code = StatusCode::of('OK'),
            $code->associatedReasonPhrase(),
            $request->protocolVersion()
        );
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($response);
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function($resource): bool {
                return $resource->properties()->get('graph')->value() === Json::encode([
                    'name' => 'foo',
                    'value' => 0,
                    'children' => [
                        [
                            'name' => 'http(http://example.com/foo)',
                            'value' => 0,
                            'children' => [],
                        ],
                    ],
                ]);
            }));

        $graph->start('foo');
        $this->assertInstanceOf(Transport::class, $transport);
        $this->assertSame($response, $transport($request));
        $graph->end();
        $section->finish(new Identity('profile-uuid'));
    }
}

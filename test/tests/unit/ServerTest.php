<?php

namespace WellRESTed\Test\Unit\Server;

use Prophecy\Argument;
use WellRESTed\Server;

/**
 * @coversDefaultClass WellRESTed\Server
 * @uses WellRESTed\Server
 */
class ServerTest extends \PHPUnit_Framework_TestCase
{
    private $dispatcher;
    private $request;
    private $response;
    private $responder;
    private $server;

    public function setUp()
    {
        parent::setUp();
        $this->request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $this->response = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $this->responder = $this->prophesize('WellRESTed\Responder\ResponderInterface');
        $this->responder->respond(Argument::cetera())->willReturn();
        $this->dispatcher = $this->prophesize('WellRESTed\Dispatching\DispatcherInterface');
        $this->dispatcher->dispatch(Argument::cetera())->will(
            function ($args) {
                list($middleware, $request, $response, $next) = $args;
                return $next($request, $response);
            }
        );

        $this->server = $this->getMockBuilder('WellRESTed\Server')
            ->setMethods(["getDispatcher", "getRequest", "getResponse", "getResponder"])
            ->disableOriginalConstructor()
            ->getMock();
        $this->server->expects($this->any())
            ->method("getDispatcher")
            ->will($this->returnValue($this->dispatcher->reveal()));
        $this->server->expects($this->any())
            ->method("getRequest")
            ->will($this->returnValue($this->request->reveal()));
        $this->server->expects($this->any())
            ->method("getResponse")
            ->will($this->returnValue($this->response->reveal()));
        $this->server->expects($this->any())
            ->method("getResponder")
            ->will($this->returnValue($this->responder->reveal()));
        $this->server->__construct();
    }

    /**
     * @covers ::__construct
     * @covers ::getDispatcher
     * @uses WellRESTed\Dispatching\Dispatcher
     */
    public function testCreatesInstances()
    {
        $server = new Server();
        $this->assertNotNull($server);
    }

    /**
     * @covers ::add
     */
    public function testAddIsFluid()
    {
        $server = new Server();
        $this->assertSame($server, $server->add("middleware"));
    }

    /**
     * @covers ::add
     * @covers ::dispatch
     */
    public function testDispatchesMiddlewareStack()
    {
        $next = function ($request, $response) {
            return $response;
        };

        $this->server->add("first");
        $this->server->add("second");
        $this->server->add("third");

        $this->server->dispatch($this->request->reveal(), $this->response->reveal(), $next);

        $this->dispatcher->dispatch(
            ["first", "second", "third"],
            $this->request->reveal(),
            $this->response->reveal(),
            $next
        )->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Respond

    /**
     * @covers ::respond
     */
    public function testRespondDispatchesRequest()
    {
        $this->server->respond();
        $this->dispatcher->dispatch(
            Argument::any(),
            $this->request->reveal(),
            Argument::any(),
            Argument::any()
        )->shouldHaveBeenCalled();
    }

    /**
     * @covers ::respond
     */
    public function testRespondDispatchesResponse()
    {
        $this->server->respond();
        $this->dispatcher->dispatch(
            Argument::any(),
            Argument::any(),
            $this->response->reveal(),
            Argument::any()
        )->shouldHaveBeenCalled();
    }

    /**
     * @covers ::respond
     */
    public function testRespondSendsResponseToResponder()
    {
        $this->server->respond();
        $this->responder->respond(
            $this->request->reveal(),
            $this->response->reveal()
        )->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Router

    public function testCreatesRouterWithDispatcher()
    {
        $this->request->getMethod()->willReturn("GET");
        $this->request->getRequestTarget()->willReturn("/");

        $next = function ($request, $response) {
            return $response;
        };

        $router = $this->server->makeRouter();
        $router->register("GET", "/", "middleware");
        $router->dispatch($this->request->reveal(), $this->response->reveal(), $next);

        $this->dispatcher->dispatch(
            "middleware",
            $this->request->reveal(),
            $this->response->reveal(),
            $next
        )->shouldHaveBeenCalled();
    }
}

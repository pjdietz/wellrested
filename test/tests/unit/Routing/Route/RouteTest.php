<?php

namespace WellRESTed\Test\Unit\Routing\Route;

use Prophecy\Argument;

/**
 * @coversDefaultClass WellRESTed\Routing\Route\Route
 * @uses WellRESTed\Routing\Route\Route
 * @group route
 */
class RouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     */
    public function testCreatesInstance()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = $this->getMockForAbstractClass(
            'WellRESTed\Routing\Route\Route',
            ["/target", $methodMap->reveal()]);
        $this->assertNotNull($route);
    }

    /**
     * @covers ::getTarget
     */
    public function testReturnsTarget()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = $this->getMockForAbstractClass(
            'WellRESTed\Routing\Route\Route',
            ["/target", $methodMap->reveal()]);
        $this->assertSame("/target", $route->getTarget());
    }

    /**
     * @covers ::getMethodMap
     */
    public function testReturnsMethodMap()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $route = $this->getMockForAbstractClass(
            'WellRESTed\Routing\Route\Route',
            ["/target", $methodMap->reveal()]);
        $this->assertSame($methodMap->reveal(), $route->getMethodMap());
    }

    /**
     * @covers ::dispatch
     */
    public function testDispatchesMethodMap()
    {
        $methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $methodMap->dispatch(Argument::cetera())->willReturn();

        $route = $this->getMockForAbstractClass(
            'WellRESTed\Routing\Route\Route',
            ["/target", $methodMap->reveal()]);

        $request = $this->prophesize('Psr\Http\Message\ServerRequestInterface')->reveal();
        $response = $this->prophesize('Psr\Http\Message\ResponseInterface')->reveal();
        $next = function ($request, $response) {
            return $response;
        };
        $route->dispatch($request, $response, $next);

        $methodMap->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }
}

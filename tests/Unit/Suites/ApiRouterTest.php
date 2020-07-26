<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\RestApi;

use LizardsAndPumpkins\Http\Exception\HeaderNotPresentException;
use LizardsAndPumpkins\Http\HttpRequest;
use LizardsAndPumpkins\Http\HttpUrlParser;
use LizardsAndPumpkins\Http\Routing\HttpRequestHandler;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\RestApi\ApiRouter
 */
class ApiRouterTest extends TestCase
{
    /**
     * @var ApiRouter
     */
    private $apiRouter;

    /**
     * @var ApiRequestHandlerLocator
     */
    private $stubApiRequestHandlerLocator;

    /**
     * @var HttpRequest
     */
    private $stubHttpRequest;

    /**
     * @var HttpUrlParser
     */
    private $stubUrlParser;

    final protected function setUp(): void
    {
        $this->stubApiRequestHandlerLocator = $this->createMock(ApiRequestHandlerLocator::class);
        $this->stubUrlParser = $this->createMock(HttpUrlParser::class);
        $this->apiRouter = new ApiRouter($this->stubApiRequestHandlerLocator, $this->stubUrlParser);

        $this->stubHttpRequest = $this->createMock(HttpRequest::class);
    }

    public function testNullIsReturnedIfUrlIsNotLedByApiPrefix(): void
    {
        $this->stubUrlParser->method('getPath')->willReturn('foo/bar');
        $result = $this->apiRouter->route($this->stubHttpRequest);

        $this->assertNull($result);
    }

    public function testNullIsReturnedIfVersionFormatIsInvalid(): void
    {
        $this->stubHttpRequest->method('hasHeader')->with('Accept')->willReturn(true);
        $this->stubHttpRequest->method('getHeader')->with('Accept')->willReturn('application/json');
        $this->stubUrlParser->method('getPath')->willReturn('api/foo');
        $result = $this->apiRouter->route($this->stubHttpRequest);

        $this->assertNull($result);
    }

    public function testReturnsNullIfRequestHasNoAcceptHeader(): void
    {
        $this->stubHttpRequest->method('hasHeader')->with('Accept')->willReturn(false);
        $this->stubHttpRequest->method('getHeader')->with('Accept')->willThrowException(new HeaderNotPresentException);
        $this->stubUrlParser->method('getPath')->willReturn('api');
        $result = $this->apiRouter->route($this->stubHttpRequest);

        $this->assertNull($result);
    }

    public function testNullIsReturnedIfEndpointCodeIsNotSpecified(): void
    {
        $this->stubHttpRequest->method('hasHeader')->with('Accept')->willReturn(true);
        $this->stubHttpRequest->method('getHeader')->with('Accept')
            ->willReturn('application/vnd.lizards-and-pumpkins.foo.v1+json');
        $this->stubUrlParser->method('getPath')->willReturn('api');
        $result = $this->apiRouter->route($this->stubHttpRequest);

        $this->assertNull($result);
    }

    public function testNullIsReturnedIfApiRequestHandlerCanNotProcessRequest(): void
    {
        $stubRequestHandler = $this->createMock(HttpRequestHandler::class);
        $stubRequestHandler->method('canProcess')->willReturn(false);

        $this->stubApiRequestHandlerLocator->expects($this->once())
            ->method('getApiRequestHandler')
            ->willReturn($stubRequestHandler);

        $this->stubUrlParser->method('getPath')->willReturn('api/foo');
        $this->stubHttpRequest->method('hasHeader')->with('Accept')->willReturn(true);
        $this->stubHttpRequest->method('getHeader')->with('Accept')
            ->willReturn('application/vnd.lizards-and-pumpkins.foo.v1+json');
        $result = $this->apiRouter->route($this->stubHttpRequest);

        $this->assertNull($result);
    }

    public function testReturnsHttpRequestHandler(): void
    {
        $stubRequestHandler = $this->createMock(HttpRequestHandler::class);
        $stubRequestHandler->method('canProcess')->willReturn(true);

        $this->stubApiRequestHandlerLocator->expects($this->once())
            ->method('getApiRequestHandler')
            ->willReturn($stubRequestHandler);

        $this->stubHttpRequest->method('hasHeader')->with('Accept')->willReturn(true);
        $this->stubHttpRequest->method('getHeader')->with('Accept')
            ->willReturn('application/vnd.lizards-and-pumpkins.foo.v1+json');
        $this->stubUrlParser->method('getPath')->willReturn('api/foo');
        $result = $this->apiRouter->route($this->stubHttpRequest);

        $this->assertInstanceOf(HttpRequestHandler::class, $result);
    }
}

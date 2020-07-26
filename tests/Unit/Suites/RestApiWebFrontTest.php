<?php

declare(strict_types = 1);

namespace LizardsAndPumpkins\RestApi;

use LizardsAndPumpkins\Core\Factory\Factory;
use LizardsAndPumpkins\Http\HttpHeaders;
use LizardsAndPumpkins\Http\HttpRequest;
use LizardsAndPumpkins\Http\HttpResponse;
use LizardsAndPumpkins\Http\HttpUrlParser;
use LizardsAndPumpkins\Http\Routing\HttpRequestHandler;
use LizardsAndPumpkins\Http\Routing\HttpRouter;
use LizardsAndPumpkins\Http\Routing\HttpRouterChain;
use LizardsAndPumpkins\Http\WebFront;
use LizardsAndPumpkins\Core\Factory\MasterFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\RestApi\RestApiWebFront
 */
class RestApiWebFrontTest extends TestCase
{
    /**
     * @var RestApiWebFront
     */
    private $webFront;

    /**
     * @var MasterFactory|MockObject
     */
    private $mockMasterFactory;

    /**
     * @var HttpResponse
     */
    private $stubHttpResponse;

    /**
     * @var HttpRequestHandler|MockObject
     */
    private $stubHttpRequestHandler;

    final protected function setUp(): void
    {
        $dummyHttpRequest = $this->createMock(HttpRequest::class);
        $dummyFactory = $this->createMock(Factory::class);

        $this->stubHttpResponse = $this->createMock(HttpResponse::class);
        $this->stubHttpResponse->method('getStatusCode')->willReturn(HttpResponse::STATUS_OK);

        $this->stubHttpRequestHandler = $this->createMock(HttpRequestHandler::class);
        $this->stubHttpRequestHandler->method('process')->willReturn($this->stubHttpResponse);

        $stubRouterChain = $this->createMock(HttpRouterChain::class);
        $stubRouterChain->method('route')->willReturn($this->stubHttpRequestHandler);

        $stubMasterFactory = $this->getMockBuilder(MasterFactory::class)
            ->onlyMethods(get_class_methods(MasterFactory::class))
            ->addMethods([
                'createHttpRouterChain',
                'getApiRequestHandlerLocator',
                'createHttpUrlParser',
                'createApiRouter',
            ])
            ->getMock();
        $stubMasterFactory->method('createHttpUrlParser')->willReturn($this->createMock(HttpUrlParser::class));
        $stubMasterFactory->method('createHttpRouterChain')->willReturn($stubRouterChain);
        $stubMasterFactory->method('createApiRouter')->willReturn($this->createMock(HttpRouter::class));

        $this->webFront = new class(
            $dummyHttpRequest,
            $stubMasterFactory,
            $dummyFactory
        ) extends RestApiWebFront {
            /**
             * @var MasterFactory
             */
            private $testMasterFactory;

            public function __construct(
                HttpRequest $request,
                MasterFactory $testMasterFactory,
                Factory $stubFactory
            ) {
                parent::__construct($request, $stubFactory);

                $this->testMasterFactory = $testMasterFactory;
            }

            final protected function createMasterFactory() : MasterFactory
            {
                return $this->testMasterFactory;
            }
        };
    }

    public function testIsWebFront(): void
    {
        $this->assertInstanceOf(WebFront::class, $this->webFront);
    }

    public function testReturnsHttpResponse(): void
    {
        $this->assertInstanceOf(HttpResponse::class, $this->webFront->processRequest());
    }

    public function testCorsHeadersAreAdded(): void
    {
        $originalHeaders = ['Foo' => 'Bar'];
        $stubHttpHeaders = HttpHeaders::fromArray($originalHeaders);
        $this->stubHttpResponse->method('getHeaders')->willReturn($stubHttpHeaders);

        $response = $this->webFront->processRequest();

        $expectedHeaders = array_merge($originalHeaders, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => '*',
            'Content-Type' => 'application/json',
        ]);

        $this->assertEquals($expectedHeaders, $response->getHeaders()->getAll());
    }

    public function testReturnsJsonErrorResponseInCaseOfExceptions(): void
    {
        $exceptionMessage = 'foo';

        $this->stubHttpRequestHandler->method('process')->willThrowException(new \Exception($exceptionMessage));

        $response = $this->webFront->processRequest();

        $this->assertSame(HttpResponse::STATUS_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame(json_encode(['error' => $exceptionMessage]), $response->getBody());
    }
}

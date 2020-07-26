<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\RestApi;

use LizardsAndPumpkins\Core\Factory\Factory;
use LizardsAndPumpkins\Core\Factory\MasterFactory;
use LizardsAndPumpkins\Http\HttpUrlParser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LizardsAndPumpkins\RestApi\RestApiFactory
 * @uses   \LizardsAndPumpkins\RestApi\ApiRouter
 */
class RestApiFactoryTest extends TestCase
{
    /**
     * @var RestApiFactory
     */
    private $factory;

    public function assertApiRequestHandlerIsRegistered(ApiRequestHandlerLocator $locator, string $code, int $version)
    {
        $handler = $locator->getApiRequestHandler($code, $version);
        $message = sprintf('No API request handler "%s" for version "%s" registered', $code, $version);
        $this->assertNotInstanceOf(NullApiRequestHandler::class, $handler, $message);
    }

    final protected function setUp(): void
    {
        /** @var MasterFactory|MockObject $stubMasterFactory */
        $stubMasterFactory = $this->getMockBuilder(MasterFactory::class)
            ->onlyMethods(get_class_methods(MasterFactory::class))
            ->addMethods(['getApiRequestHandlerLocator', 'createHttpUrlParser'])
            ->getMock();
        $stubMasterFactory->method('getApiRequestHandlerLocator')
            ->willReturn($this->createMock(ApiRequestHandlerLocator::class));
        $stubMasterFactory->method('createHttpUrlParser')
            ->willReturn($this->createMock(HttpUrlParser::class));

        $this->factory = new RestApiFactory();
        $this->factory->setMasterFactory($stubMasterFactory);
    }

    public function testIsFactory(): void
    {
        $this->assertInstanceOf(Factory::class, $this->factory);
    }

    public function testApiRouterIsReturned(): void
    {
        $this->assertInstanceOf(ApiRouter::class, $this->factory->createApiRouter());
    }
}

<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\RestApi;

use LizardsAndPumpkins\Http\Routing\HttpRequestHandler;

class ApiRequestHandlerLocator
{
    private $requestHandlers = [];

    public function register(string $code, int $version, callable $requestHandlerFactory): void
    {
        $key = $this->getRequestProcessorLocatorKey($code, $version);
        $this->requestHandlers[$key] = $requestHandlerFactory;
    }

    public function getApiRequestHandler(string $code, int $version): HttpRequestHandler
    {
        $key = $this->getRequestProcessorLocatorKey($code, $version);

        if (! isset($this->requestHandlers[$key])) {
            return new NullApiRequestHandler;
        }

        return ($this->requestHandlers[$key])();
    }

    private function getRequestProcessorLocatorKey(string $code, int $version): string
    {
        return sprintf('v%s_%s', $version, $code);
    }
}

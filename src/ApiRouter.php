<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\RestApi;

use LizardsAndPumpkins\Http\HttpRequest;
use LizardsAndPumpkins\Http\HttpUrlParser;
use LizardsAndPumpkins\Http\Routing\HttpRequestHandler;
use LizardsAndPumpkins\Http\Routing\HttpRouter;

class ApiRouter implements HttpRouter
{
    const API_URL_PREFIX = 'api';

    /**
     * @var ApiRequestHandlerLocator
     */
    private $requestHandlerLocator;

    /**
     * @var HttpUrlParser
     */
    private $urlParser;

    public function __construct(ApiRequestHandlerLocator $requestHandlerLocator, HttpUrlParser $urlParser)
    {
        $this->requestHandlerLocator = $requestHandlerLocator;
        $this->urlParser = $urlParser;
    }

    public function route(HttpRequest $request): ?HttpRequestHandler
    {
        $urlToken = explode('/', $this->urlParser->getPath($request->getUrl()));

        if (self::API_URL_PREFIX !== array_shift($urlToken)) {
            return null;
        }

        if (! $version = $this->getApiVersion($request)) {
            return null;
        }

        if (! $requestHandlerCode = array_shift($urlToken)) {
            return null;
        }

        $requestHandler = $this->requestHandlerLocator->getApiRequestHandler(
            strtolower($request->getMethod() . '_' . $requestHandlerCode),
            $version
        );

        if ($requestHandler->canProcess($request)) {
            return $requestHandler;
        }

        return null;
    }

    private function getApiVersion(HttpRequest $request): ?int
    {
        if (! $request->hasHeader('Accept') || ! preg_match(
                '/^application\/vnd\.lizards-and-pumpkins\.\w+\.v(?<version>\d+)\+(?:json|xml)$/',
                $request->getHeader('Accept'),
                $matchedVersion
            )) {
            return null;
        }

        return (int) $matchedVersion['version'];
    }
}

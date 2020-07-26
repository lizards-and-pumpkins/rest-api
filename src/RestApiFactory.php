<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\RestApi;

use LizardsAndPumpkins\Core\Factory\Factory;
use LizardsAndPumpkins\Core\Factory\FactoryTrait;

class RestApiFactory implements Factory
{
    use FactoryTrait;

    public function createApiRouter(): ApiRouter
    {
        return new ApiRouter(
            $this->getMasterFactory()->getApiRequestHandlerLocator(),
            $this->getMasterFactory()->createHttpUrlParser()
        );
    }
}

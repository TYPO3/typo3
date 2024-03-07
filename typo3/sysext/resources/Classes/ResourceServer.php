<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Resources\Message\ResourceRequestHandlerInterface;
use TYPO3\CMS\Resources\Message\ResourceRequestInterface;
use TYPO3\CMS\Resources\Message\ResourceResponseInterface;
use TYPO3\CMS\Resources\Routing\ResourceRouter;
use Webmozart\Assert\Assert;

readonly class ResourceServer implements ResourceRequestHandlerInterface
{
    public function __construct(
        private ResourceRouter $resourceRouter,
        private ContainerInterface $serviceLocator
    )
    {}

    public function handle(ResourceRequestInterface $request): ResourceResponseInterface
    {
        $routeResult = $this->resourceRouter->matchRequest($request);
        $request = $request->withAttribute('route', $routeResult);

        $controller = $this->serviceLocator->get($routeResult->getResourceRouteOptions()->getVersion()->getControllerServiceId());
        Assert::isInstanceOf($controller, ResourceRequestHandlerInterface::class);

        return $controller->handle($request);
    }
}

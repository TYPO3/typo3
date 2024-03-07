<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Routing;

use Symfony\Component\Routing\Route;

class ResourceRoute extends Route
{
    private ResourceRouteOptionsInterface $resourceRouteOptions;

    public function __construct(string $path, ResourceRouteOptionsInterface $resourceRouteOptions, array|string $methods = [], array $options = [], ?string $condition = '')
    {
        parent::setPath($path);
        $this->setResourceRouteOptions($resourceRouteOptions);
        parent::setMethods($methods);
        parent::setOptions($options);
        parent::setCondition($condition);
        parent::setSchemes(ResourceRequestContext::SCHEME_TYPO3);
    }

    public function setResourceRouteOptions(ResourceRouteOptionsInterface $resourceRouteOptions): void
    {
        $this->resourceRouteOptions = $resourceRouteOptions;
    }

    public function getResourceRouteOptions(): ResourceRouteOptionsInterface
    {
        return $this->resourceRouteOptions;
    }
}

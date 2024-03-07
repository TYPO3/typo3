<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Routing;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use TYPO3\CMS\Resources\Message\ResourceRequestInterface;

class ResourceRouter
{
    protected RouteCollection $routeCollection;

    public function __construct() {
        $this->routeCollection = new RouteCollection();
    }

    public function addRouteCollection(RouteCollection $routeCollection): void
    {
        $this->routeCollection->addCollection($routeCollection);
    }

    /**
     * @return ResourceRoute[]
     */
    public function getRoutes(): iterable
    {
        return $this->routeCollection->getIterator();
    }

    public function hasRoute(string $routeName): bool
    {
        return $this->routeCollection->get($routeName) !== null;
    }

    public function getRoute(string $routeName): null|ResourceRoute|Route
    {
        return $this->routeCollection->get($routeName);
    }

    public function matchRequest(ResourceRequestInterface $request): ResourceRouteResult
    {
        $requestContext = ResourceRequestContext::fromResourceRequest($request);
        $path = $request->getRequestTarget();

        $result = (new UrlMatcher($this->routeCollection, $requestContext))->match($path);
        $routeName = $result['_route'];
        unset($result['_route']);
        $matchedRoute = $this->routeCollection->get($routeName);
        if ($matchedRoute === null || !$matchedRoute instanceof ResourceRoute) {
            throw new ResourceNotFoundException('The requested resource "' . $path . '" was not found.', 1709803409);
        }

        return new ResourceRouteResult($routeName, $matchedRoute->getResourceRouteOptions(), $result);
    }
}

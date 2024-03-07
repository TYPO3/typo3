<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Routing\Factory;

use Symfony\Component\Routing\RouteCollection;
use TYPO3\CMS\Resources\Definition\Metadata\ScopeInterface;
use TYPO3\CMS\Resources\Definition\Metadata\VersionInterface;
use TYPO3\CMS\Resources\Definition\MetadataInterface;
use TYPO3\CMS\Resources\Definition\RegistryInterface;
use TYPO3\CMS\Resources\Message\Numerus;
use TYPO3\CMS\Resources\Routing\ResourceRoute;
use TYPO3\CMS\Resources\Routing\ResourceRouteOptions;
use TYPO3\CMS\Resources\Routing\ResourceRouteOptionsInterface;

class RegistryRouteCollectionFactory
{

    public static function createFromResourceRegistry(RegistryInterface $registry): RouteCollection
    {
        $routes = new RouteCollection();
        foreach ($registry->findAll() as $definition) {
            foreach ($definition->getVersions() as $version) {
                $routeName = self::getRouteName($definition, $version);
                $routePath = self::getRoutePath($definition, $version);
                $options = self::getRouteOptions($definition, $version, Numerus::Collection);

                $routes->add(
                    $routeName . ':' . Numerus::Collection->value,
                    new ResourceRoute(
                        $routePath,
                        self::getRouteOptions($definition, $version, Numerus::Collection)
                    ));

                $routes->add(
                    $routeName . ':' . Numerus::Item->value,
                    new ResourceRoute(
                        $routePath . '/{id}',
                        self::getRouteOptions($definition, $version, Numerus::Item)
                    ));
            }
        }
        return $routes;
    }

    private static function getRoutePath(MetadataInterface $definition, VersionInterface $version): string
    {
        $routePathSegments = [$definition->getId(), $version->getName()];
        if ($definition->getScope()->getFQN() !== ScopeInterface::SCOPE_GLOBAL) {
            $routePathSegments[] = '{namespace}';
        }
        return implode('/', $routePathSegments);
    }

    private static function getRouteName(MetadataInterface $definition, VersionInterface $version): string
    {
        return "{$definition->getId()}/{$version->getName()}";
    }

    private static function getRouteOptions(MetadataInterface $definition, VersionInterface $version, Numerus $numerus): ResourceRouteOptionsInterface
    {
        return new ResourceRouteOptions($numerus, $version, $definition);
    }
}

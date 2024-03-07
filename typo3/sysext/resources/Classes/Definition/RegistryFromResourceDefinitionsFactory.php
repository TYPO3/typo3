<?php

namespace TYPO3\CMS\Resources\Definition;

class RegistryFromResourceDefinitionsFactory
{
    public static function createFromResourceDefinition(array $resourceDefinitions): Registry
    {
        $resourceDefinitions = \array_map(function(array $resourceDefinition) {
            $id = implode('.', [$resourceDefinition['names']['plural'], $resourceDefinition['group']]);

            $group = new Metadata\Group($resourceDefinition['group']);

            $names = new Metadata\Names(
                $resourceDefinition['names']['plural'],
                $resourceDefinition['names']['singular'],
                $resourceDefinition['names']['kind'],
                $resourceDefinition['names']['shortnames'] ?: [],
            );

            $scope = new Metadata\Scope($resourceDefinition['scope']);

            $versions = new Metadata\VersionCollection(array_map(function (array $versionConfig) use ($group, $names): Metadata\VersionInterface {
                return new Metadata\Version(
                    implode('/', [$group->getFQN(), $versionConfig['name']]),
                    $versionConfig['name'],
                    $versionConfig['served'],
                    $versionConfig['controller']
                );
            }, $resourceDefinition['versions']));

            return new Metadata($id, $group, $names, $scope, $versions);
        }, $resourceDefinitions);

        $registry = new Registry();
        $registry->saveAll(...$resourceDefinitions);
        return $registry;
    }
}

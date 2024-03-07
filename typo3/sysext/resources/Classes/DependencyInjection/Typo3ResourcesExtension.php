<?php

namespace TYPO3\CMS\Resources\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Resources\Controller\ResourceController;
use TYPO3\CMS\Resources\Definition\Metadata;
use TYPO3\CMS\Resources\Definition\MetadataInterface;
use TYPO3\CMS\Resources\Definition\Registry;
use TYPO3\CMS\Resources\Definition\RegistryFromResourceDefinitionsFactory;
use TYPO3\CMS\Resources\Definition\RegistryInterface;

/**
 * This is a copy of \Sylius\Bundle\ResourceBundle\DependencyInjection\SyliusResourceExtension
 * It should follow the original one closely, so an integration stays feasible.
 */
final class Typo3ResourcesExtension extends Extension implements PrependExtensionInterface
{
    private ContainerBuilder $container;

    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->container = $container;
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $this->loadResourceDefinitions($config['definitions'], $container);
    }

    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        $this->container = $container;
        $configuration = new Configuration();
        $container->addObjectResource($configuration);
        return $configuration;
    }

    public function prepend(ContainerBuilder $container): void
    {
    }

    private function loadResourceDefinitions(array $loadedResources, ContainerBuilder $container): void
    {
        /** @var array<string, array> $resourceDefinitions */
        $resourceDefinitions = $container->hasParameter('typo3.resources.definitions') ? $container->getParameter('typo3.resources.definitions') : [];
        $resourceDefinitions = \array_merge($resourceDefinitions, $loadedResources);
        $resourceDefinitions = \array_map($this->createDefaultResourceControllers(...), $resourceDefinitions);
        $container->setParameter('typo3.resources.definitions', $resourceDefinitions);

        $container->register('typo3.resources.registry', Registry::class)
            ->setFactory([RegistryFromResourceDefinitionsFactory::class, 'createFromResourceDefinition'])
            ->addArgument(new Parameter('typo3.resources.definitions'));
    }

    private function createDefaultResourceControllers(array $resourceDefinition): array
    {
        $controllerIdPrefix = implode('.', ['typo3.resources.controllers', $resourceDefinition['names']['plural'], $resourceDefinition['group']]);

        $resourceDefinition['versions'] = \array_map(function (array $version) use ($controllerIdPrefix): array {
            if ($version['controller'] !== ResourceController::class) {
                return $version;
            }

            $version['controller'] = $controllerIdPrefix . '/' . $version['name'];
            $this->container->register($version['controller'], ResourceController::class)
                ->setPublic(true)
                ->setArgument('$repository', new Reference(ltrim($version['model']['repository'], '@')));

            return $version;
        }, $resourceDefinition['versions']);

        return $resourceDefinition;
    }
}

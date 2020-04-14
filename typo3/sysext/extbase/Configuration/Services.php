<?php

declare(strict_types=1);
namespace TYPO3\CMS\Extbase;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $containerConfigurator, ContainerBuilder $container) {
    $container->registerForAutoconfiguration(Mvc\RequestHandlerInterface::class)->addTag('extbase.request_handler');
    $container->registerForAutoconfiguration(Mvc\Controller\ControllerInterface::class)->addTag('extbase.controller');
    $container->registerForAutoconfiguration(Mvc\Controller\AbstractController::class)->addTag('extbase.prototype_controller');
    $container->registerForAutoconfiguration(Mvc\Controller\ActionController::class)->addTag('extbase.action_controller');
    $container->registerForAutoconfiguration(Mvc\View\ViewInterface::class)->addTag('extbase.view');

    $container->addCompilerPass(new class() implements CompilerPassInterface {
        public function process(ContainerBuilder $container): void
        {
            foreach ($container->findTaggedServiceIds('extbase.request_handler') as $id => $tags) {
                $container->findDefinition($id)->setPublic(true);
            }
            foreach ($container->findTaggedServiceIds('extbase.controller') as $id => $tags) {
                $container->findDefinition($id)->setPublic(true);
            }
            foreach ($container->findTaggedServiceIds('extbase.prototype_controller') as $id => $tags) {
                $container->findDefinition($id)->setShared(false);
            }
            foreach ($container->findTaggedServiceIds('extbase.action_controller') as $id => $tags) {
                $container->findDefinition($id)->setShared(false);
            }
            foreach ($container->findTaggedServiceIds('extbase.view') as $id => $tags) {
                $container->findDefinition($id)->setShared(false)->setPublic(true);
            }

            // Push alias definition defined in symfony into the extbase container
            // 'aliasDefinitions' is a private property of the Symfony ContainerBuilder class
            // but as 'alias' statements an not be tagged, that is the only way to retrieve
            // these aliases to map them to the extbase container
            $reflection = new \ReflectionClass(get_class($container));
            $aliasDefinitions = $reflection->getProperty('aliasDefinitions');
            $aliasDefinitions->setAccessible(true);

            $extbaseContainer = $container->findDefinition(Object\Container\Container::class);
            // Add registerImplementation() call for aliases
            foreach ($aliasDefinitions->getValue($container) as $from => $alias) {
                if (!class_exists($from) && !interface_exists($from)) {
                    continue;
                }
                $to = (string)$alias;
                // Ignore aliases that are used to inject early instances into the container (instantiated during TYPO3 Bootstrap)
                // and aliases that refer to service names instead of class names
                if (strpos($to, '_early.') === 0 || !class_exists($to)) {
                    continue;
                }

                $extbaseContainer->addMethodCall('registerImplementation', [$from, $to]);
            }
        }
    });
};

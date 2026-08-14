<?php

declare(strict_types=1);

namespace TYPO3\CMS\Frontend;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $container) {
    $container->addCompilerPass(new class implements CompilerPassInterface {
        public function process(ContainerBuilder $container): void
        {
            foreach ($container->findTaggedServiceIds('frontend.contentobject') as $id => $tags) {
                $container->findDefinition($id)->setShared(false);
            }

            // Menu content objects (the sub types of HMENU, e.g. TMENU) carry state while
            // rendering and create the menu objects of the next level themselves. They are
            // therefore never shared and are handed the locator of all registered menu types,
            // so extensions only need to add the 'frontend.menucontentobject' tag.
            $menuContentObjects = [];
            foreach ($container->findTaggedServiceIds('frontend.menucontentobject') as $id => $tags) {
                foreach ($tags as $tag) {
                    if (isset($tag['identifier'])) {
                        $menuContentObjects[$tag['identifier']] = new Reference($id);
                    }
                }
            }
            $menuContentObjectLocator = ServiceLocatorTagPass::register($container, $menuContentObjects);
            foreach (array_keys($container->findTaggedServiceIds('frontend.menucontentobject')) as $id) {
                $definition = $container->findDefinition($id);
                $definition->setShared(false);
                $definition->setArgument('$menuContentObjectLocator', $menuContentObjectLocator);
            }
        }
    });
};

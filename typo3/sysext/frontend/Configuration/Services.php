<?php

declare(strict_types=1);

namespace TYPO3\CMS\Frontend;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $container) {
    $container->addCompilerPass(new class () implements CompilerPassInterface {
        public function process(ContainerBuilder $container): void
        {
            foreach ($container->findTaggedServiceIds('frontend.contentobject') as $id => $tags) {
                $container->findDefinition($id)->setShared(false);
            }
        }
    });
};

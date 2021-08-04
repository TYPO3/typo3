<?php

declare(strict_types=1);
namespace TYPO3\CMS\Fluid;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerForAutoconfiguration(ViewHelperInterface::class)->addTag('fluid.viewhelper');

    $containerBuilder->addCompilerPass(new class() implements CompilerPassInterface {
        public function process(ContainerBuilder $container)
        {
            foreach ($container->findTaggedServiceIds('fluid.viewhelper') as $id => $tags) {
                $container->findDefinition($id)->setPublic(true)->setShared(false);
            }
        }
    });
};

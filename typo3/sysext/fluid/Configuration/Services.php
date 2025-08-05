<?php

declare(strict_types=1);

namespace TYPO3\CMS\Fluid;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperResolverDelegateInterface;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    // Tag ViewHelper services and configure them to be used by Fluid, which currently
    // cannot inject them properly and doesn't support shared ViewHelper classes
    $containerBuilder->registerForAutoconfiguration(ViewHelperInterface::class)->addTag('fluid.viewhelper');
    $containerBuilder->addCompilerPass(new class () implements CompilerPassInterface {
        public function process(ContainerBuilder $container)
        {
            foreach ($container->findTaggedServiceIds('fluid.viewhelper') as $id => $tags) {
                $container->findDefinition($id)->setPublic(true)->setShared(false);
            }
        }
    });

    // Tag ViewHelperResolver delegate services and configure them to be used by Fluid,
    // which currently cannot inject them properly
    $containerBuilder->registerForAutoconfiguration(ViewHelperResolverDelegateInterface::class)->addTag('fluid.resolverdelegate');
    $containerBuilder->addCompilerPass(new PublicServicePass('fluid.resolverdelegate'));
};

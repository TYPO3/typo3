<?php

declare(strict_types=1);

namespace TYPO3\CMS\Extbase;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;
use TYPO3\CMS\Extbase\DependencyInjection\TypeConverterPass;

return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $container) {
    $container->registerForAutoconfiguration(Mvc\Controller\ControllerInterface::class)->addTag('extbase.controller');
    $container->registerForAutoconfiguration(Mvc\Controller\ActionController::class)->addTag('extbase.action_controller');
    $container->registerForAutoconfiguration(Validation\Validator\ValidatorInterface::class)->addTag('extbase.validator');
    $container->addCompilerPass(new PublicServicePass('extbase.validator', true));

    $container->addCompilerPass(new class () implements CompilerPassInterface {
        public function process(ContainerBuilder $container): void
        {
            foreach ($container->findTaggedServiceIds('extbase.controller') as $id => $tags) {
                $container->findDefinition($id)->setPublic(true);
            }
            foreach ($container->findTaggedServiceIds('extbase.action_controller') as $id => $tags) {
                $container->findDefinition($id)->setShared(false);
            }
        }
    });

    $container->addCompilerPass(new TypeConverterPass('extbase.type_converter'));
};

<?php

declare(strict_types=1);

namespace TYPO3\CMS\Form;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;
use TYPO3\CMS\Form\ConfigurationModuleProvider\FormYamlProvider;
use TYPO3\CMS\Form\Domain\Finishers\FinisherInterface;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ProviderRegistry;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerForAutoconfiguration(FinisherInterface::class)->addTag('form.finisher');
    $containerBuilder->addCompilerPass(new PublicServicePass('form.finisher', true));

    if ($containerBuilder->hasDefinition(ProviderRegistry::class)) {
        $container->services()->defaults()->autowire()->autoconfigure()->public()
            ->set('lowlevel.configuration.module.provider.formyamlconfiguration')
            ->class(FormYamlProvider::class)
            ->tag(
                'lowlevel.configuration.module.provider',
                [
                    'identifier' => 'formYamlConfiguration',
                    'after' => 'eventListeners',
                ]
            );
    }
};

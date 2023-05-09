<?php

declare(strict_types=1);

namespace TYPO3\CMS\Backend;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ProviderRegistry;
use TYPO3\CMS\Reactions\ConfigurationModuleProvider\ReactionsProvider;
use TYPO3\CMS\Reactions\Reaction\ReactionInterface;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder
        ->registerForAutoconfiguration(ReactionInterface::class)
        ->setPublic(true)
        ->setLazy(true)
        ->addTag('reactions.reaction');

    if ($containerBuilder->hasDefinition(ProviderRegistry::class)) {
        $container->services()->defaults()->autowire()->autoconfigure()->public()
            ->set('lowlevel.configuration.module.provider.reactions')
            ->class(ReactionsProvider::class)
            ->tag(
                'lowlevel.configuration.module.provider',
                [
                    'identifier' => 'reactions',
                    'label' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:reactions',
                    'after' => 'mfaproviders',
                ]
            );
    }
};

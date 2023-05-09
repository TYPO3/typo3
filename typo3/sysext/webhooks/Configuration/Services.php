<?php

declare(strict_types=1);

namespace TYPO3\CMS\Webhooks;

use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\Attribute\WebhookMessage;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ProviderRegistry;
use TYPO3\CMS\Webhooks\ConfigurationModuleProvider\WebhookTypesProvider;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerAttributeForAutoconfiguration(
        WebhookMessage::class,
        static function (ChildDefinition $definition, WebhookMessage $attribute): void {
            $definition->addTag(WebhookMessage::TAG_NAME, ['identifier' => $attribute->identifier, 'description' => $attribute->description, 'method' => $attribute->method]);
        }
    );
    $containerBuilder->addCompilerPass(new DependencyInjection\WebhookCompilerPass(WebhookMessage::TAG_NAME));

    if ($containerBuilder->hasDefinition(ProviderRegistry::class)) {
        $container->services()->defaults()->autowire()->autoconfigure()->public()
            ->set('lowlevel.configuration.module.provider.webhooks')
            ->class(WebhookTypesProvider::class)
            ->arg('$sendersLocator', new ServiceLocatorArgument(new TaggedIteratorArgument('messenger.sender', 'identifier')))
            ->tag(
                'lowlevel.configuration.module.provider',
                [
                    'identifier' => 'webhooks',
                    'label' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:webhooks',
                    'after' => 'reactions',
                ]
            );
    }
};

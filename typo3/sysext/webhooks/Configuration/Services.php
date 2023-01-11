<?php

declare(strict_types=1);

namespace TYPO3\CMS\Webhooks;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\Attribute\WebhookMessage;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerAttributeForAutoconfiguration(
        WebhookMessage::class,
        static function (ChildDefinition $definition, WebhookMessage $attribute): void {
            $definition->addTag(WebhookMessage::TAG_NAME, ['identifier' => $attribute->identifier, 'description' => $attribute->description, 'method' => $attribute->method]);
        }
    );
    $containerBuilder->addCompilerPass(new DependencyInjection\WebhookCompilerPass(WebhookMessage::TAG_NAME));
};

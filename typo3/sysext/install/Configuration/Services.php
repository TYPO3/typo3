<?php

declare(strict_types=1);

namespace TYPO3\CMS\Install;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerAttributeForAutoconfiguration(
        UpgradeWizard::class,
        static function (ChildDefinition $definition, UpgradeWizard $attribute): void {
            $definition->addTag(UpgradeWizard::TAG_NAME, ['identifier' => $attribute->identifier]);
        }
    );
};

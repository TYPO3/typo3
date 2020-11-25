<?php

declare(strict_types=1);

namespace TYPO3\CMS\Lowlevel;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator, ContainerBuilder $containerBuilder): void {
    $containerBuilder->addCompilerPass(
        new DependencyInjection\ConfigurationModuleProviderPass('lowlevel.configuration.module.provider')
    );
};

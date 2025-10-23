<?php

declare(strict_types=1);

namespace TYPO3\CMS\Reports;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerForAutoconfiguration(StatusProviderInterface::class)->addTag('reports.status');
};

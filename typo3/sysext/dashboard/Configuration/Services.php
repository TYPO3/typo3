<?php

declare(strict_types=1);
namespace TYPO3\CMS\Dashboard;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->addCompilerPass(new DependencyInjection\DashboardWidgetPass('dashboard.widget'));
};

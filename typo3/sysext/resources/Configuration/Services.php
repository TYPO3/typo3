<?php declare(strict_types=1);

namespace TYPO3\CMS\Core;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Resources\DependencyInjection\Typo3ResourcesExtension;

return function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerExtension(new Typo3ResourcesExtension());
};

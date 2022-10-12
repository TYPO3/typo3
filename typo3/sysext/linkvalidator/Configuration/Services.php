<?php

declare(strict_types=1);

namespace TYPO3\CMS\Linkvalidator;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Linkvalidator\Linktype\LinktypeInterface;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerForAutoconfiguration(LinktypeInterface::class)->addTag('linkvalidator.linktype');
};

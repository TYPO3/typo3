<?php

declare(strict_types=1);
namespace TYPO3\CMS\Backend\RecordList;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Recordlist\Browser\ElementBrowserInterface;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerForAutoconfiguration(ElementBrowserInterface::class)->addTag('recordlist.elementbrowser');
};

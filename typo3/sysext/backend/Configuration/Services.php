<?php

declare(strict_types=1);
namespace TYPO3\CMS\Backend;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Backend\ContextMenu\ItemProviders\ProviderInterface;
use TYPO3\CMS\Backend\ElementBrowser\ElementBrowserInterface;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerForAutoconfiguration(ElementBrowserInterface::class)->addTag('recordlist.elementbrowser');
    $containerBuilder->registerForAutoconfiguration(ToolbarItemInterface::class)->addTag('backend.toolbar.item');
    $containerBuilder->registerForAutoconfiguration(ProviderInterface::class)->addTag('backend.contextmenu.itemprovider');
    $containerBuilder->addCompilerPass(new PublicServicePass('backend.controller'));
};

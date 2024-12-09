<?php

declare(strict_types=1);

namespace TYPO3\CMS\Backend;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\ContextMenu\ItemProviders\ProviderInterface;
use TYPO3\CMS\Backend\ElementBrowser\ElementBrowserInterface;
use TYPO3\CMS\Backend\Form\NodeInterface;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchProviderInterface;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerForAutoconfiguration(ElementBrowserInterface::class)->addTag('recordlist.elementbrowser');
    $containerBuilder->registerForAutoconfiguration(ToolbarItemInterface::class)->addTag('backend.toolbar.item');
    $containerBuilder->registerForAutoconfiguration(ProviderInterface::class)->addTag('backend.contextmenu.itemprovider');
    $containerBuilder->registerForAutoconfiguration(SearchProviderInterface::class)->addTag('livesearch.provider');
    $containerBuilder->registerForAutoconfiguration(NodeInterface::class)->addTag('backend.form.node');

    $containerBuilder->addCompilerPass(new PublicServicePass('backend.controller'));

    // Single NodeInterface nodes *may* be stateful, for instance when they have properties that are not
    // properly reset in render(), or if stateful services are injected. Thus, the second argument is true,
    // which will trigger a new object creation each time, instead of re-using an already existing one.
    // Note we *may* be able to get rid of this later (to create fewer objects), but it may need some changes,
    // plus proper communication that single nodes have to be set "shared: false" manually, in case they are stateful.
    $containerBuilder->addCompilerPass(new PublicServicePass('backend.form.node', true));

    $containerBuilder->addCompilerPass(new PublicServicePass('backend.form.dataprovider'));

    // adds tag backend.controller to services
    $containerBuilder->registerAttributeForAutoconfiguration(
        AsController::class,
        static function (ChildDefinition $definition, AsController $attribute): void {
            $definition->addTag(AsController::TAG_NAME);
        }
    );
};

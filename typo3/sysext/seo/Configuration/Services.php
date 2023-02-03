<?php

declare(strict_types=1);

namespace TYPO3\CMS\Seo;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Dashboard\WidgetRegistry;
use TYPO3\CMS\Seo\Widgets\PagesWithoutDescriptionWidget;
use TYPO3\CMS\Seo\Widgets\Provider\PagesWithoutDescriptionDataProvider;

return function (ContainerConfigurator $configurator, ContainerBuilder $containerBuilder) {
    $services = $configurator->services();

    /**
     * Check if WidgetRegistry is defined, which means that EXT:dashboard is available.
     * Registration directly in Services.yaml will break without EXT:dashboard installed!
     */
    if ($containerBuilder->hasDefinition(WidgetRegistry::class)) {
        $services->set('dashboard.widget.pagesWithoutMetaDescription')
            ->class(PagesWithoutDescriptionWidget::class)
            ->arg('$dataProvider', new Reference(PagesWithoutDescriptionDataProvider::class))
            ->arg('$backendViewFactory', new Reference(BackendViewFactory::class))
            ->arg('$options', ['refreshAvailable' => true])
            ->tag('dashboard.widget', [
                'identifier' => 'seo-pagesWithoutMetaDescription',
                'groupNames' => 'seo',
                'title' => 'LLL:EXT:seo/Resources/Private/Language/locallang_dashboard.xlf:widget.pagesWithoutMetaDescription.title',
                'description' => 'LLL:EXT:seo/Resources/Private/Language/locallang_dashboard.xlf:widget.pagesWithoutMetaDescription.description',
                'iconIdentifier' => 'content-widget-list',
                'height' => 'large',
                'width' => 'medium',
            ]);
    }
};

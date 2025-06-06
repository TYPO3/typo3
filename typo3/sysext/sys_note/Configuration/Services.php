<?php

declare(strict_types=1);

namespace TYPO3\CMS\SysNote;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Dashboard\WidgetRegistry;
use TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository;
use TYPO3\CMS\SysNote\Widgets\PagesWithInternalNote;

return function (ContainerConfigurator $configurator, ContainerBuilder $containerBuilder) {
    $services = $configurator->services();

    /**
     * Check if WidgetRegistry is defined, which means that EXT:dashboard is available.
     * Registration directly in Services.yaml will break without EXT:dashboard installed!
     */
    if ($containerBuilder->hasDefinition(WidgetRegistry::class)) {
        $services->set('dashboard.widget.pages_width_internal_note')
            ->class(PagesWithInternalNote::class)
            ->arg('$backendViewFactory', new Reference(BackendViewFactory::class))
            ->arg('$sysNoteRepository', new Reference(SysNoteRepository::class))
            ->arg('$options', ['refreshAvailable' => true])
            ->tag('dashboard.widget', [
                'identifier' => 'pages_width_internal_note',
                'groupNames' => 'sys_note',
                'title' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_widget_pages_with_internal_note.xlf:widget.pagesWithInternalNote.title',
                'description' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_widget_pages_with_internal_note.xlf:widget.pagesWithInternalNote.description',
                'iconIdentifier' => 'content-note',
                'height' => 'medium',
                'width' => 'medium',
            ]);
    }
};

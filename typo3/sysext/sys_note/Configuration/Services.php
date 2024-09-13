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
        $services->set('dashboard.widget.sys_notes.all')
            ->class(PagesWithInternalNote::class)
            ->arg('$backendViewFactory', new Reference(BackendViewFactory::class))
            ->arg('$sysNoteRepository', new Reference(SysNoteRepository::class))
            ->arg('$options', ['refreshAvailable' => true])
            ->tag('dashboard.widget', [
                'identifier' => 'sys_note_all',
                'groupNames' => 'sys_note',
                'title' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang.xlf:widget.all.title',
                'description' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang.xlf:widget.all.description',
                'iconIdentifier' => 'content-note',
                'height' => 'medium',
                'width' => 'medium',
            ]);

        $services->set('dashboard.widget.sys_notes.default')
            ->class(PagesWithInternalNote::class)
            ->arg('$backendViewFactory', new Reference(BackendViewFactory::class))
            ->arg('$sysNoteRepository', new Reference(SysNoteRepository::class))
            ->arg('$options', ['refreshAvailable' => true, 'category' => 0])
            ->tag('dashboard.widget', [
                'identifier' => 'sys_note_default',
                'groupNames' => 'sys_note',
                'title' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang.xlf:widget.pagesWithInternalNoteDefault.title',
                'description' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang.xlf:widget.pagesWithInternalNoteDefault.description',
                'iconIdentifier' => 'content-note',
                'height' => 'medium',
                'width' => 'medium',
            ]);

        $services->set('dashboard.widget.sys_notes.instructions')
            ->class(PagesWithInternalNote::class)
            ->arg('$backendViewFactory', new Reference(BackendViewFactory::class))
            ->arg('$sysNoteRepository', new Reference(SysNoteRepository::class))
            ->arg('$options', ['refreshAvailable' => true, 'category' => 1])
            ->tag('dashboard.widget', [
                'identifier' => 'sys_note_instructions',
                'groupNames' => 'sys_note',
                'title' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang.xlf:widget.pagesWithInternalNoteInstructions.title',
                'description' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang.xlf:widget.pagesWithInternalNoteInstructions.description',
                'iconIdentifier' => 'content-note',
                'height' => 'medium',
                'width' => 'medium',
            ]);

        $services->set('dashboard.widget.sys_notes.template')
            ->class(PagesWithInternalNote::class)
            ->arg('$backendViewFactory', new Reference(BackendViewFactory::class))
            ->arg('$sysNoteRepository', new Reference(SysNoteRepository::class))
            ->arg('$options', ['refreshAvailable' => true, 'category' => 2])
            ->tag('dashboard.widget', [
                'identifier' => 'sys_note_template',
                'groupNames' => 'sys_note',
                'title' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang.xlf:widget.pagesWithInternalNoteTemplate.title',
                'description' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang.xlf:widget.pagesWithInternalNoteTemplate.description',
                'iconIdentifier' => 'content-note',
                'height' => 'medium',
                'width' => 'medium',
            ]);

        $services->set('dashboard.widget.sys_notes.notes')
            ->class(PagesWithInternalNote::class)
            ->arg('$backendViewFactory', new Reference(BackendViewFactory::class))
            ->arg('$sysNoteRepository', new Reference(SysNoteRepository::class))
            ->arg('$options', ['refreshAvailable' => true, 'category' => 3])
            ->tag('dashboard.widget', [
                'identifier' => 'sys_note_notes',
                'groupNames' => 'sys_note',
                'title' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang.xlf:widget.pagesWithInternalNoteNotes.title',
                'description' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang.xlf:widget.pagesWithInternalNoteNotes.description',
                'iconIdentifier' => 'content-note',
                'height' => 'medium',
                'width' => 'medium',
            ]);

        $services->set('dashboard.widget.sys_notes.todos')
            ->class(PagesWithInternalNote::class)
            ->arg('$backendViewFactory', new Reference(BackendViewFactory::class))
            ->arg('$sysNoteRepository', new Reference(SysNoteRepository::class))
            ->arg('$options', ['refreshAvailable' => true, 'category' => 4])
            ->tag('dashboard.widget', [
                'identifier' => 'sys_note_todos',
                'groupNames' => 'sys_note',
                'title' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang.xlf:widget.pagesWithInternalNoteToDos.title',
                'description' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang.xlf:widget.pagesWithInternalNoteToDos.description',
                'iconIdentifier' => 'content-note',
                'height' => 'medium',
                'width' => 'medium',
            ]);
    }
};

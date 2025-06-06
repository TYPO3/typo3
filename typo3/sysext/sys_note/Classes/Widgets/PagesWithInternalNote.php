<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\SysNote\Widgets;

use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetContext;
use TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetResult;
use TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository;

class PagesWithInternalNote implements WidgetRendererInterface
{
    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly SysNoteRepository $sysNoteRepository,
        private readonly BackendViewFactory $backendViewFactory,
        private readonly array $options = []
    ) {}

    /**
     * @return SettingDefinition[]
     */
    public function getSettingsDefinitions(): array
    {
        return [
            new SettingDefinition(
                key: 'category',
                type: 'string',
                default: (string)($this->options['category'] ?? ''),
                label: 'LLL:EXT:sys_note/Resources/Private/Language/locallang_widget_pages_with_internal_note.xlf:widget.pagesWithInternalNote.setting.category.label',
                description: 'LLL:EXT:sys_note/Resources/Private/Language/locallang_widget_pages_with_internal_note.xlf:widget.pagesWithInternalNote.setting.category.description',
                enum: [
                    '' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_widget_pages_with_internal_note.xlf:widget.pagesWithInternalNote.setting.category.label.all',
                    '0' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.0',
                    '1' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.1',
                    '2' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.2',
                    '3' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.3',
                    '4' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.4',
                ],
                readonly: array_key_exists('category', $this->options),
            ),
        ];
    }

    public function renderWidget(WidgetContext $context): WidgetResult
    {
        $view = $this->backendViewFactory->create($context->request, ['typo3/cms-dashboard', 'typo3/cms-sys-note']);

        $category = $context->settings->get('category') !== '' ? (int)$context->settings->get('category') : null;
        $view->assignMultiple([
            'sysNotes' => $this->sysNoteRepository->findByCategoryRestricted($category),
            'category' => $category,
            'configuration' => $this->configuration,
            'dateFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],
            'timeFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
        ]);

        return new WidgetResult(
            label: $this->getWidgetLabel($category),
            content: $view->render('Widget/PagesWithInternalNote.html'),
            refreshable: true,
        );
    }

    protected function getWidgetLabel(?int $category): string
    {
        if ($category === 0) {
            return $this->getLanguageService()->sL('LLL:EXT:sys_note/Resources/Private/Language/locallang_widget_pages_with_internal_note.xlf:widget.pagesWithInternalNote.default.title');
        }
        if ($category === 1) {
            return $this->getLanguageService()->sL('LLL:EXT:sys_note/Resources/Private/Language/locallang_widget_pages_with_internal_note.xlf:widget.pagesWithInternalNote.instructions.title');
        }
        if ($category === 2) {
            return $this->getLanguageService()->sL('LLL:EXT:sys_note/Resources/Private/Language/locallang_widget_pages_with_internal_note.xlf:widget.pagesWithInternalNote.template.title');
        }
        if ($category === 3) {
            return $this->getLanguageService()->sL('LLL:EXT:sys_note/Resources/Private/Language/locallang_widget_pages_with_internal_note.xlf:widget.pagesWithInternalNote.notes.title');
        }
        if ($category === 4) {
            return $this->getLanguageService()->sL('LLL:EXT:sys_note/Resources/Private/Language/locallang_widget_pages_with_internal_note.xlf:widget.pagesWithInternalNote.todo.title');
        }

        return $this->getLanguageService()->sL('LLL:EXT:sys_note/Resources/Private/Language/locallang_widget_pages_with_internal_note.xlf:widget.pagesWithInternalNote.all.title');
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}

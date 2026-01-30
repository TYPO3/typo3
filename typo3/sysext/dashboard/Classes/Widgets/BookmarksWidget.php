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

namespace TYPO3\CMS\Dashboard\Widgets;

use TYPO3\CMS\Backend\Backend\Bookmark\BookmarkService;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Settings\SettingsInterface;

/**
 * Widget to display user bookmarks on the dashboard.
 *
 * The widget renders a custom element that reads bookmark data
 * from the central BookmarkStore in the top frame.
 */
final readonly class BookmarksWidget implements WidgetRendererInterface, JavaScriptInterface
{
    public function __construct(
        private WidgetConfigurationInterface $configuration,
        private BackendViewFactory $backendViewFactory,
        private BookmarkService $bookmarkService,
        /** @var array{limit?: int, group?: string} */
        private array $options = [],
    ) {}

    /**
     * @return SettingDefinition[]
     */
    public function getSettingsDefinitions(): array
    {
        return [
            new SettingDefinition(
                key: 'group',
                type: 'string',
                default: $this->options['group'] ?? '',
                label: 'dashboard.widget_bookmarks:widget.bookmarks.setting.groups.label',
                description: 'dashboard.widget_bookmarks:widget.bookmarks.setting.groups.description',
                readonly: array_key_exists('group', $this->options),
                enum: $this->getAvailableGroups(),
            ),
            new SettingDefinition(
                key: 'limit',
                type: 'int',
                default: (int)($this->options['limit'] ?? 0),
                label: 'dashboard.widget_bookmarks:widget.bookmarks.setting.limit.label',
                description: 'dashboard.widget_bookmarks:widget.bookmarks.setting.limit.description',
                readonly: array_key_exists('limit', $this->options),
            ),
        ];
    }

    public function renderWidget(WidgetContext $context): WidgetResult
    {
        $view = $this->backendViewFactory->create($context->request);
        $view->assignMultiple([
            'options' => $this->options,
            'settings' => $context->settings,
            'configuration' => $this->configuration,
        ]);

        return new WidgetResult(
            content: $view->render('Widget/BookmarksWidget'),
            label: $this->resolveLabel($context->settings),
            refreshable: true,
        );
    }

    public function getJavaScriptModuleInstructions(): array
    {
        return [
            JavaScriptModuleInstruction::create('@typo3/dashboard/widget/bookmarks-widget-element.js'),
        ];
    }

    private function getAvailableGroups(): array
    {
        $groups = [
            '' => 'dashboard.widget_bookmarks:widget.bookmarks.setting.showAll',
        ];

        // Only show groups that contain bookmarks
        $groupsWithBookmarks = [];
        foreach ($this->bookmarkService->getBookmarks() as $bookmark) {
            $groupsWithBookmarks[$bookmark->groupId] = true;
        }

        foreach ($this->bookmarkService->getGroups() as $group) {
            if (isset($groupsWithBookmarks[$group->id])) {
                $groups[(string)$group->id] = $group->label;
            }
        }
        return $groups;
    }

    private function resolveLabel(SettingsInterface $settings): ?string
    {
        $group = $settings->get('group');
        if ($group !== '') {
            $groupId = is_numeric($group) ? (int)$group : $group;
            foreach ($this->bookmarkService->getGroups() as $bookmarkGroup) {
                if ($bookmarkGroup->id === $groupId) {
                    $widgetTitle = $this->getLanguageService()->sL($this->configuration->getTitle());
                    return $widgetTitle . ': ' . $bookmarkGroup->label;
                }
            }
        }

        // Fall back to default widget title
        return null;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}

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

use TYPO3\CMS\Backend\Backend\Shortcut\ShortcutRepository;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Settings\SettingsInterface;

final readonly class BookmarksWidget implements WidgetRendererInterface
{
    public function __construct(
        private WidgetConfigurationInterface $configuration,
        private BackendViewFactory $backendViewFactory,
        private ShortcutRepository $shortcutRepository,
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
                key: 'label',
                type: 'string',
                default: '',
                label: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widget.bookmarks.setting.label.label',
                description: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widget.bookmarks.setting.label.description',
            ),
            new SettingDefinition(
                key: 'group',
                type: 'string',
                default: (string)($this->options['group'] ?? ''),
                label: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widget.bookmarks.setting.groups.label',
                description: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widget.bookmarks.setting.groups.description',
                readonly: array_key_exists('group', $this->options),
                enum: $this->getAvailableGroups(),
            ),
            new SettingDefinition(
                key: 'limit',
                type: 'int',
                default: (int)($this->options['limit'] ?? 10),
                label: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widget.bookmarks.setting.limit.label',
                description: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widget.bookmarks.setting.limit.description',
                readonly: array_key_exists('limit', $this->options),
            ),
        ];
    }

    public function renderWidget(WidgetContext $context): WidgetResult
    {
        $shortcutMenu = $this->getShortcuts($context->settings);

        $view = $this->backendViewFactory->create($context->request);
        $view->assignMultiple([
            'options' => $this->options,
            'settings' => $context->settings,
            'shortcutMenu' => $shortcutMenu,
            'configuration' => $this->configuration,
        ]);

        return new WidgetResult(
            content: $view->render('Widget/BookmarksWidget'),
            label: $this->resolveLabel($context, $shortcutMenu),
            refreshable: true,
        );
    }

    /**
     * @return array<int, array{id: int, title: string, shortcuts: array}>
     */
    private function getShortcuts(SettingsInterface $settings): array
    {
        $shortcutMenu = [];
        $group = $settings->get('group') !== '' ? (int)$settings->get('group') : null;
        $groups = $this->shortcutRepository->getGroupsFromShortcuts();
        if ($group !== null) {
            if (!isset($groups[$group])) {
                $groups = [];
            } else {
                $groups = [$group => $groups[$group]];
            }
        }
        arsort($groups, SORT_NUMERIC);
        $limit = max(1, (int)$settings->get('limit'));
        foreach ($groups as $groupId => $groupLabel) {
            $shortcuts = $this->shortcutRepository->getShortcutsByGroup($groupId);
            if ($shortcuts === []) {
                continue;
            }
            if ($limit > 0) {
                if (count($shortcuts) > $limit) {
                    $shortcuts = array_slice($shortcuts, 0, $limit);
                }
                $limit -= count($shortcuts);
                $shortcutMenu[] = [
                    'id' => $groupId,
                    'title' => $groupLabel,
                    'shortcuts' => $shortcuts,
                    'single' => $group !== null,
                ];
            }
        }
        return $shortcutMenu;
    }

    private function getAvailableGroups(): array
    {
        $groups = $this->shortcutRepository->getGroupsFromShortcuts();
        $groups[0] = 'LLL:EXT:backend/Resources/Private/Language/locallang_toolbar.xlf:toolbarItems.bookmarks.notGrouped';
        $groups[''] = 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widget.bookmarks.setting.label.showAll';
        return $groups;
    }

    private function resolveLabel(WidgetContext $context, array $shortcutMenu): ?string
    {
        $label = $context->settings->get('label');
        if ($label !== '') {
            // Use defined label
            return $label;
        }
        if (count($shortcutMenu) === 1) {
            $group = reset($shortcutMenu);
            $label = $group['title'] ?? '';
            if ($label !== '') {
                // Use group title
                return $label;
            }
        }
        // Fall back to default widget title
        return null;
    }
}

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

use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Dashboard\Widgets\Provider\LatestBeLoginsDataProvider;

/**
 * This widget will show a list of recent backend user logins.
 *
 * The list contains:
 * - backend user avatar and name
 * - login time
 *
 * The following options are available during registration:
 * - limit `int` number of logins to display
 */
readonly class LatestBeLoginsWidget implements WidgetRendererInterface, AdminOnlyWidgetInterface
{
    public function __construct(
        private BackendViewFactory $backendViewFactory,
        private LatestBeLoginsDataProvider $dataProvider,
        private ButtonProviderInterface $buttonProvider,
        private WidgetConfigurationInterface $configuration,
    ) {}

    public function getSettingsDefinitions(): array
    {
        return [
            new SettingDefinition(
                key: 'limit',
                type: 'int',
                default: 10,
                label: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.latestBeLogins.settings.limit',
                description: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.latestBeLogins.settings.limit.description',
                options: [
                    'min' => 1,
                ],
            ),
        ];
    }

    public function renderWidget(WidgetContext $context): WidgetResult
    {
        $limit = $context->settings->get('limit');
        $items = $this->dataProvider->getItems($limit);

        $view = $this->backendViewFactory->create($context->request);
        $view->assignMultiple([
            'items' => $items,
            'button' => $this->buttonProvider,
            'configuration' => $this->configuration,
            'dateFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
        ]);

        return new WidgetResult(
            content: $view->render('Widget/LatestBeLoginsWidget'),
            refreshable: true,
        );
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}

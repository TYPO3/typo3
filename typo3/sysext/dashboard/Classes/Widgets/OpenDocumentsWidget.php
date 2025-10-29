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
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Dashboard\Widgets\Provider\RecentlyOpenedDocuments;

/**
 * Widget to show recently opened documents in the backend.
 */
class OpenDocumentsWidget implements WidgetRendererInterface
{
    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly RecentlyOpenedDocuments $dataProvider,
        private readonly BackendViewFactory $backendViewFactory,
    ) {}

    public function getSettingsDefinitions(): array
    {
        return [
            new SettingDefinition(
                key: 'limit',
                type: 'int',
                default: 10,
                label: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:open_documents.settings.limit',
                description: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:open_documents.settings.limit.description',
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
            'configuration' => $this->configuration,
        ]);

        return new WidgetResult(
            content: $view->render('Widget/OpenDocumentsWidget'),
            refreshable: true,
        );
    }
}

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

namespace TYPO3\CMS\Seo\Widgets;

use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetContext;
use TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetResult;
use TYPO3\CMS\Seo\Widgets\Provider\PagesWithoutDescriptionDataProvider;

/**
 * @internal
 */
final class PagesWithoutDescriptionWidget implements WidgetRendererInterface
{
    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly PagesWithoutDescriptionDataProvider $dataProvider,
        private readonly BackendViewFactory $backendViewFactory,
        private readonly array $options,
    ) {}

    /**
     * @return SettingDefinition[]
     */
    public function getSettingsDefinitions(): array
    {
        return [];
    }

    public function renderWidget(WidgetContext $context): WidgetResult
    {
        $view = $this->backendViewFactory->create($context->request, ['typo3/cms-dashboard', 'typo3/cms-seo']);
        $view->assignMultiple([
            'pages' => $this->dataProvider->getPages(),
            'options' => $this->options,
            'configuration' => $this->configuration,
        ]);
        return new WidgetResult(
            content: $view->render('Widget/PagesWithoutDescription'),
            refreshable: $this->options['refreshAvailable'] ?? false,
        );
    }
}

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

use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Concrete Number with Icon implementation
 *
 * The widget will show widget with an icon, a number, a title and a subtitle. The number is provided by a data
 * provider.
 *
 * The following options are available during registration:
 * - icon           string        The icon-identifier of the icon that should be shown in the widget. You should
 *                                register your icon with the Icon API
 * - title          string        The main title that will be shown in the widget as an explanation of the shown number.
 *                                You can either enter a normal string or a translation string
 *                                (eg. LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.failedLogins.title)
 * - subtitle       string        The subtitle that will give some additional information about the number and title.
 *                                You can either enter a normal string or a translation string
 *                                (eg. LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.failedLogins.subtitle)
 *
 * @see NumberWithIconDataProviderInterface
 */
class NumberWithIconWidget implements WidgetInterface
{
    /**
     * @var WidgetConfigurationInterface
     */
    private $configuration;
    /**
     * @var StandaloneView
     */
    private $view;
    /**
     * @var array
     */
    private $options;
    /**
     * @var NumberWithIconDataProviderInterface
     */
    private $dataProvider;

    public function __construct(
        WidgetConfigurationInterface $configuration,
        NumberWithIconDataProviderInterface $dataProvider,
        StandaloneView $view,
        array $options = []
    ) {
        $this->configuration = $configuration;
        $this->view = $view;
        $this->options = $options;
        $this->dataProvider = $dataProvider;
    }

    /**
     * @inheritDoc
     */
    public function renderWidgetContent(): string
    {
        $this->view->setTemplate('Widget/NumberWithIconWidget');
        $this->view->assignMultiple([
            'icon' => $this->options['icon'] ?? '',
            'title' => $this->options['title'] ?? '',
            'subtitle' => $this->options['subtitle'] ?? '',
            'number' => $this->dataProvider->getNumber(),
            'options' => $this->options,
            'configuration' => $this->configuration,
        ]);
        return $this->view->render();
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}

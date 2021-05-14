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
 * Concrete List Widget implementation
 *
 * The widget will show a simple list with items provided by a data provider. You can add a button to the widget by
 * defining a button provider.
 *
 * There are no options available for this widget
 *
 * @see ListDataProviderInterface
 * @see ButtonProviderInterface
 */
class ListWidget implements WidgetInterface
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
     * @var ButtonProviderInterface|null
     */
    private $buttonProvider;

    /**
     * @var ListDataProviderInterface
     */
    private $dataProvider;

    public function __construct(
        WidgetConfigurationInterface $configuration,
        ListDataProviderInterface $dataProvider,
        StandaloneView $view,
        $buttonProvider = null,
        array $options = []
    ) {
        $this->configuration = $configuration;
        $this->view = $view;
        $this->options = $options;
        $this->buttonProvider = $buttonProvider;
        $this->dataProvider = $dataProvider;
    }

    public function renderWidgetContent(): string
    {
        $this->view->setTemplate('Widget/ListWidget');
        $this->view->assignMultiple([
            'items' => $this->getItems(),
            'options' => $this->options,
            'button' => $this->buttonProvider,
            'configuration' => $this->configuration,
        ]);
        return $this->view->render();
    }

    protected function getItems(): array
    {
        return $this->dataProvider->getItems();
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}

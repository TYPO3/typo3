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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
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
class ListWidget implements WidgetInterface, RequestAwareWidgetInterface
{
    private ServerRequestInterface $request;

    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly ListDataProviderInterface $dataProvider,
        private readonly BackendViewFactory $backendViewFactory,
        // @deprecated since v12, will be removed in v13 together with services 'dashboard.views.widget' and Factory
        protected readonly ?StandaloneView $view = null,
        private readonly ?ButtonProviderInterface $buttonProvider = null,
        private readonly array $options = [],
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $view = $this->backendViewFactory->create($this->request);
        $view->assignMultiple([
            'items' => $this->getItems(),
            'options' => $this->options,
            'button' => $this->buttonProvider,
            'configuration' => $this->configuration,
        ]);
        return $view->render('Widget/ListWidget');
    }

    protected function getItems(): array
    {
        return $this->dataProvider->getItems();
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Seo\Widgets\Provider\PagesWithoutDescriptionDataProvider;

/**
 * @internal
 */
final class PagesWithoutDescriptionWidget implements WidgetInterface, RequestAwareWidgetInterface
{
    private ServerRequestInterface $request;

    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly PagesWithoutDescriptionDataProvider $dataProvider,
        private readonly BackendViewFactory $backendViewFactory,
        private readonly array $options,
    ) {
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $view = $this->backendViewFactory->create($this->request, ['typo3/cms-dashboard', 'typo3/cms-seo']);
        $view->assignMultiple([
            'pages' => $this->dataProvider->getPages(),
            'options' => $this->getOptions(),
            'configuration' => $this->configuration,
        ]);
        return $view->render('Widget/PagesWithoutDescription');
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}

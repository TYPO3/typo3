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

namespace TYPO3\CMS\Extensionmanager\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;

/**
 * Controller for distribution related actions.
 *
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class DistributionController extends AbstractController
{
    public function __construct(
        protected readonly PackageManager $packageManager,
        protected readonly PageRenderer $pageRenderer,
        protected readonly IconFactory $iconFactory
    ) {}

    /**
     * Shows information about a single distribution. Reachable from 'Get preconfigured distribution'.
     */
    public function showAction(Extension $extension): ResponseInterface
    {
        $extensionKey = $extension->getExtensionKey();
        // Check if extension/package is installed
        $active = $this->packageManager->isPackageActive($extensionKey);
        $view = $this->initializeModuleTemplate($this->request);
        $view = $this->registerDocHeaderButtons($view);
        $view->assign('distributionActive', $active);
        $view->assign('extension', $extension);
        $this->pageRenderer->loadJavaScriptModule('@typo3/extensionmanager/distribution-image.js');
        return $view->renderResponse('Distribution/Show');
    }

    /**
     * Add 'back to list' icon to doc-header.
     */
    protected function registerDocHeaderButtons(ModuleTemplate $view): ModuleTemplate
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $uri = $this->uriBuilder->reset()->uriFor('distributions', [], 'List');
        $title = $this->translate('extConfTemplate.backToList');
        $icon = $this->iconFactory->getIcon('actions-view-go-back', IconSize::SMALL);
        $button = $buttonBar->makeLinkButton()
            ->setHref($uri)
            ->setTitle($title)
            ->setShowLabelText(true)
            ->setIcon($icon);
        $buttonBar->addButton($button);
        return $view;
    }
}

<?php

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
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;

/**
 * Controller for distribution related actions
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class DistributionController extends AbstractModuleController
{
    protected PackageManager $packageManager;
    protected PageRenderer $pageRenderer;
    protected IconFactory $iconFactory;

    public function __construct(PackageManager $packageManager, PageRenderer $pageRenderer, IconFactory $iconFactory)
    {
        $this->packageManager = $packageManager;
        $this->pageRenderer = $pageRenderer;
        $this->iconFactory = $iconFactory;
    }

    /**
     * Shows information about the distribution
     *
     * @param Extension $extension
     * @return ResponseInterface
     */
    public function showAction(Extension $extension): ResponseInterface
    {
        $extensionKey = $extension->getExtensionKey();
        // Check if extension/package is installed
        $active = $this->packageManager->isPackageActive($extensionKey);

        $this->view->assign('distributionActive', $active);
        $this->view->assign('extension', $extension);

        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Extensionmanager/DistributionImage');

        $moduleTemplate = $this->initializeModuleTemplate($this->request);
        $moduleTemplate = $this->registerDocHeaderButtons($moduleTemplate);
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Registers the Icons into the docheader
     */
    protected function registerDocHeaderButtons(ModuleTemplate $moduleTemplate): ModuleTemplate
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $uri = $this->uriBuilder->reset()->uriFor('distributions', [], 'List');
        $title = $this->translate('extConfTemplate.backToList');
        $icon = $this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL);
        $button = $buttonBar->makeLinkButton()
            ->setHref($uri)
            ->setTitle($title)
            ->setIcon($icon);
        $buttonBar->addButton($button, ButtonBar::BUTTON_POSITION_LEFT);

        return $moduleTemplate;
    }
}

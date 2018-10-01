<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

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
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Controller for configuration related actions.
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class UpdateScriptController extends AbstractModuleController
{
    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        if ($view instanceof BackendTemplateView) {
            /** @var BackendTemplateView $view */
            parent::initializeView($view);
            $this->generateMenu();
            $this->registerDocheaderButtons();
        }
    }

    /**
     * Show the content of the update script (if any).
     *
     * @param string $extensionKey Extension key
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
     */
    public function showAction($extensionKey)
    {
        /** @var \TYPO3\CMS\Extensionmanager\Utility\UpdateScriptUtility $updateScriptUtility */
        $updateScriptUtility = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Utility\UpdateScriptUtility::class);
        $updateScriptResult = $updateScriptUtility->executeUpdateIfNeeded($extensionKey);
        $this->view
            ->assign('updateScriptResult', $updateScriptResult)
            ->assign('extensionKey', $extensionKey);
    }

    /**
     * Registers the Icons into the docheader
     *
     * @throws \InvalidArgumentException
     */
    protected function registerDocheaderButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();

        $uriBuilder = $this->controllerContext->getUriBuilder();

        $uri = $uriBuilder->reset()->uriFor('index', [], 'List');
        $title = $this->translate('extConfTemplate.backToList');
        $icon = $this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL);
        $button = $buttonBar->makeLinkButton()
            ->setHref($uri)
            ->setTitle($title)
            ->setIcon($icon);
        $buttonBar->addButton($button, ButtonBar::BUTTON_POSITION_LEFT);
    }
}

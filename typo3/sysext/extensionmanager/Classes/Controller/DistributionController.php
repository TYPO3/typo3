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
 * Controller for distribution related actions
 */
class DistributionController extends AbstractModuleController
{
    /**
     * @var \TYPO3\CMS\Core\Package\PackageManager
     */
    protected $packageManager;

    /**
     * @param \TYPO3\CMS\Core\Package\PackageManager $packageManager
     */
    public function injectPackageManager(\TYPO3\CMS\Core\Package\PackageManager $packageManager)
    {
        $this->packageManager = $packageManager;
    }

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     * @return void
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
     * Shows information about the distribution
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
     */
    public function showAction(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension)
    {
        $extensionKey = $extension->getExtensionKey();
        // Check if extension/package is installed
        $active = $this->packageManager->isPackageActive($extensionKey);

        // Create link for extension configuration
        if ($active && file_exists(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey) . 'ext_conf_template.txt')) {
            $uriBuilder = $this->controllerContext->getUriBuilder();
            $action = 'showConfigurationForm';
            $configurationLink = $uriBuilder->reset()->uriFor(
                $action,
                ['extension' => ['key' => $extensionKey]],
                'Configuration'
            );
        } else {
            $configurationLink = false;
        }
        $this->view->assign('distributionActive', $active);
        $this->view->assign('configurationLink', $configurationLink);
        $this->view->assign('extension', $extension);
    }

    /**
     * Registers the Icons into the docheader
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function registerDocheaderButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();

        $uriBuilder = $this->controllerContext->getUriBuilder();

        $uri = $uriBuilder->reset()->uriFor('distributions', [], 'List');
        $title = $this->translate('extConfTemplate.backToList');
        $icon = $this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL);
        $button = $buttonBar->makeLinkButton()
            ->setHref($uri)
            ->setTitle($title)
            ->setIcon($icon);
        $buttonBar->addButton($button, ButtonBar::BUTTON_POSITION_LEFT);
    }
}

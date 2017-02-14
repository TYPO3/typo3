<?php
namespace TYPO3\CMS\About\Controller;

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

use TYPO3\CMS\About\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Module 'about' shows some standard information for TYPO3 CMS: About-text, version number and so on.
 */
class AboutController extends ActionController
{
    /**
     * @var ViewInterface
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @var ExtensionRepository
     */
    protected $extensionRepository;

    /**
     * @param ExtensionRepository $extensionRepository
     */
    public function injectExtensionRepository(ExtensionRepository $extensionRepository)
    {
        $this->extensionRepository = $extensionRepository;
    }

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);
        // Disable Path
        $view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation([]);
    }

    /**
     * Main action: Show standard information
     */
    public function indexAction()
    {
        $this->view
            ->assign('currentVersion', TYPO3_version)
            ->assign('copyrightYear', TYPO3_copyright_year)
            ->assign('donationUrl', TYPO3_URL_DONATE)
            ->assign('loadedExtensions', $this->extensionRepository->findAllLoaded());
    }
}

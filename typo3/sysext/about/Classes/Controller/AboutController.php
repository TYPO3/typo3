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

use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Module 'about' shows some standard information for TYPO3 CMS: About-text, version number and so on.
 */
class AboutController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * @var
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @var \TYPO3\CMS\About\Domain\Repository\ExtensionRepository
     */
    protected $extensionRepository;

    /**
     * @param \TYPO3\CMS\About\Domain\Repository\ExtensionRepository $extensionRepository
     */
    public function injectExtensionRepository(\TYPO3\CMS\About\Domain\Repository\ExtensionRepository $extensionRepository)
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
     *
     * @return void
     */
    public function indexAction()
    {
        $extensions = $this->extensionRepository->findAllLoaded();
        $this->view
            ->assign('TYPO3Version', TYPO3_version)
            ->assign('TYPO3CopyrightYear', TYPO3_copyright_year)
            ->assign('TYPO3UrlDonate', TYPO3_URL_DONATE)
            ->assign('loadedExtensions', $extensions);
    }
}

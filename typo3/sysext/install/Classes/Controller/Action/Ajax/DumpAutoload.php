<?php
namespace TYPO3\CMS\Install\Controller\Action\Ajax;

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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Status\NoticeStatus;
use TYPO3\CMS\Install\Status\OkStatus;
use TYPO3\CMS\Install\View\JsonView;

/**
 * Ajax wrapper for dumping autoload.
 */
class DumpAutoload extends AbstractAjaxAction
{
    /**
     * @var \TYPO3\CMS\Install\View\JsonView
     */
    protected $view;

    /**
     * @param JsonView $view
     */
    public function __construct(JsonView $view = null)
    {
        $this->view = $view ?: GeneralUtility::makeInstance(JsonView::class);
    }

    /**
     * Initialize the handle action, sets up fluid stuff and assigns default variables.
     * @ToDo Refactor View Initialization for all Ajax Controllers
     */
    protected function initializeHandle()
    {
        // empty on purpose because AbstractAjaxAction still overwrites $this->view with StandaloneView
    }

    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction()
    {
        if (Bootstrap::usesComposerClassLoading()) {
            $message = GeneralUtility::makeInstance(NoticeStatus::class);
            $message->setTitle('Skipped generating additional class loading information in composer mode.');
        } else {
            ClassLoadingInformation::dumpClassLoadingInformation();
            $message = GeneralUtility::makeInstance(OkStatus::class);
            $message->setTitle('Successfully dumped class loading information for extensions.');
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => [ $message ],
        ]);
        return $this->view->render();
    }
}

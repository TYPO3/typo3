<?php
declare(strict_types=1);
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Controller\Action\AbstractAction;
use TYPO3\CMS\Install\View\JsonView;

/**
 * General purpose AJAX controller action helper methods and bootstrap
 */
abstract class AbstractAjaxAction extends AbstractAction
{
    /**
     * @var JsonView
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
     * AbstractAjaxAction still overwrites $this->view with StandaloneView, which is
     * shut off here.
     */
    protected function initializeHandle()
    {
        // Deliberately empty
    }

    /**
     * Handles the action.
     *
     * @return string Rendered content
     */
    public function handle(): string
    {
        $this->initializeHandle();
        return json_encode($this->executeAction());
    }
}

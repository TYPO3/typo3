<?php
namespace TYPO3\CMS\Belog\Controller;

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

/**
 * Controller for log entry listings in Web->Info module
 */
class WebInfoController extends \TYPO3\CMS\Belog\Controller\AbstractController
{
    /**
     * Set context to 'in page mode'
     *
     * @return void
     */
    public function initializeAction()
    {
        $this->isInPageContext = true;
        $this->pageId = (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');
        parent::initializeAction();
    }
}

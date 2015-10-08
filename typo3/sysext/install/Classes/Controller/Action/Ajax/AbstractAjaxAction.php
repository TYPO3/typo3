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

/**
 * General purpose AJAX controller action helper methods and bootstrap
 */
abstract class AbstractAjaxAction extends \TYPO3\CMS\Install\Controller\Action\AbstractAction
{
    /**
     * Handles the action
     *
     * @return string Rendered content
     */
    public function handle()
    {
        $this->initializeHandle();
        return json_encode($this->executeAction());
    }
}

<?php
declare(strict_types = 1);

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
use TYPO3\CMS\Core\Registry;

/**
 * Save ignored documentation file items and hide them from display
 */
class SaveIgnoredItems extends AbstractAjaxAction
{

    /**
     * Executes the action
     *
     * @return string content
     * @throws \InvalidArgumentException
     */
    protected function executeAction(): string
    {
        $registry = new Registry();
        $filePath = $this->postValues['ignoreFile'];
        $fileHash = md5_file($filePath);
        $registry->set('upgradeAnalysisIgnoredFiles', $fileHash, $filePath);
        return json_encode('');
    }
}

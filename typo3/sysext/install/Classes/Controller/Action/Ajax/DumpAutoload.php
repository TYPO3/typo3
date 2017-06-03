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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Install\Status\NoticeStatus;
use TYPO3\CMS\Install\Status\OkStatus;
use TYPO3\CMS\Install\Status\StatusInterface;

/**
 * Ajax wrapper for dumping autoload.
 */
class DumpAutoload extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction(): array
    {
        $statusMessages[] = $this->dumpAutoload();

        $this->view->assignMultiple([
            'success' => true,
            'status' => $statusMessages,
        ]);
        return $this->view->render();
    }

    /**
     * Dumps Extension Autoload Information
     *
     * @return StatusInterface
     */
    protected function dumpAutoload(): StatusInterface
    {
        if (Bootstrap::usesComposerClassLoading()) {
            $message = new NoticeStatus();
            $message->setTitle('Skipped generating additional class loading information in composer mode.');
        } else {
            ClassLoadingInformation::dumpClassLoadingInformation();
            $message = new OkStatus();
            $message->setTitle('Successfully dumped class loading information for extensions.');
        }
        return $message;
    }
}

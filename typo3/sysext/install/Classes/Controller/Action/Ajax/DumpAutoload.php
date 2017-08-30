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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;

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
        $messageQueue = new FlashMessageQueue('install');
        if (Bootstrap::usesComposerClassLoading()) {
            $messageQueue->enqueue(new FlashMessage(
                '',
                'Skipped generating additional class loading information in composer mode.',
                FlashMessage::NOTICE
            ));
        } else {
            ClassLoadingInformation::dumpClassLoadingInformation();
            $messageQueue->enqueue(new FlashMessage(
                '',
                'Successfully dumped class loading information for extensions.'
            ));
        }
        $this->view->assignMultiple([
            'success' => true,
            'status' => $messageQueue
        ]);
        return $this->view->render();
    }
}

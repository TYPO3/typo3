<?php
namespace TYPO3\CMS\Core\Messaging;

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
 * A class representing flash messages.
 */
class FlashMessageService implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Array of \TYPO3\CMS\Core\Messaging\FlashMessageQueue objects
     *
     * @var array
     */
    protected $flashMessageQueues = [];

    /**
     * Return the message queue for the given identifier.
     * If no queue exists, an empty one will be created.
     *
     * @param string $identifier
     * @return \TYPO3\CMS\Core\Messaging\FlashMessageQueue
     * @api
     */
    public function getMessageQueueByIdentifier($identifier = 'core.template.flashMessages')
    {
        if (!isset($this->flashMessageQueues[$identifier])) {
            $this->flashMessageQueues[$identifier] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Messaging\FlashMessageQueue::class,
                $identifier
            );
        }
        return $this->flashMessageQueues[$identifier];
    }
}

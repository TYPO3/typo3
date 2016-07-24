<?php

namespace TYPO3\CMS\Core\Mail;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 3 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Small wrapper for \Swift_MemorySpool
 *
 * Because TYPO3 doesn't offer a terminate signal or hook,
 * and taking in account the risk that extensions do some redirects or even exits,
 * we simpley use the destructor of a singleton class which should be pretty much
 * at the end of a request.
 *
 * To have only one memory spool per request seems to be more appropriate anyway.
 *
 * @api experimental! This class is experimental and subject to change!
 */
class MemorySpool extends \Swift_MemorySpool implements \TYPO3\CMS\Core\SingletonInterface
{
    public function __destruct()
    {
        $this->sendMessages();
    }

    public function sendMessages()
    {
        $mailer = GeneralUtility::makeInstance(Mailer::class);
        try {
            $this->flushQueue($mailer->getRealTransport());
        } catch (\Swift_TransportException $exception) {
            $this->getLogger()->error(sprintf('Exception occurred while flushing email queue: %s', $exception->getMessage()));
        }
    }

    /**
     * Get class logger
     *
     * @return TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger()
    {
        return GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }
}

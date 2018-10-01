<?php
namespace TYPO3\CMS\Scheduler\Example;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides testing procedures
 * @internal This class is an example is not considered part of the Public TYPO3 API.
 */
class TestTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{
    /**
     * An email address to be used during the process
     *
     * @var string $email
     */
    public $email;

    /**
     * Function executed from the Scheduler.
     * Sends an email
     *
     * @return bool
     */
    public function execute()
    {
        $success = false;
        if (!empty($this->email)) {
            // If an email address is defined, send a message to it
            $this->logger->info('[TYPO3\\CMS\\Scheduler\\Example\\TestTask]: Test email sent to "' . $this->email . '"');
            // Get execution information
            $exec = $this->getExecution();
            // Get call method
            if (Environment::isCli()) {
                $calledBy = 'CLI module dispatcher';
                $site = '-';
            } else {
                $calledBy = 'TYPO3 backend';
                $site = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
            }
            $start = $exec->getStart();
            $end = $exec->getEnd();
            $interval = $exec->getInterval();
            $multiple = $exec->getMultiple();
            $cronCmd = $exec->getCronCmd();
            $mailBody = 'SCHEDULER TEST-TASK' . LF . '- - - - - - - - - - - - - - - -' . LF . 'UID: ' . $this->taskUid . LF . 'Sitename: ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . LF . 'Site: ' . $site . LF . 'Called by: ' . $calledBy . LF . 'tstamp: ' . date('Y-m-d H:i:s') . ' [' . time() . ']' . LF . 'maxLifetime: ' . $this->scheduler->extConf['maxLifetime'] . LF . 'start: ' . date('Y-m-d H:i:s', $start) . ' [' . $start . ']' . LF . 'end: ' . (empty($end) ? '-' : date('Y-m-d H:i:s', $end) . ' [' . $end . ']') . LF . 'interval: ' . $interval . LF . 'multiple: ' . ($multiple ? 'yes' : 'no') . LF . 'cronCmd: ' . ($cronCmd ? $cronCmd : 'not used');
            // Prepare mailer and send the mail
            try {
                /** @var \TYPO3\CMS\Core\Mail\MailMessage $mailer */
                $mailer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);
                $mailer->setFrom([$this->email => 'SCHEDULER TEST-TASK']);
                $mailer->setReplyTo([$this->email => 'SCHEDULER TEST-TASK']);
                $mailer->setSubject('SCHEDULER TEST-TASK');
                $mailer->setBody($mailBody);
                $mailer->setTo($this->email);
                $mailsSend = $mailer->send();
                $success = $mailsSend > 0;
            } catch (\Exception $e) {
                throw new \TYPO3\CMS\Core\Exception($e->getMessage(), 1476048416);
            }
        } else {
            // No email defined, just log the task
            $this->logger->warning('[TYPO3\\CMS\\Scheduler\\Example\\TestTask]: No email address given');
        }
        return $success;
    }

    /**
     * This method returns the destination mail address as additional information
     *
     * @return string Information to display
     */
    public function getAdditionalInformation()
    {
        return $GLOBALS['LANG']->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.email') . ': ' . $this->email;
    }
}

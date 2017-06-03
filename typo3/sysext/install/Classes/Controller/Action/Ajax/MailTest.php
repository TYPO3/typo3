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

use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Status\ErrorStatus;
use TYPO3\CMS\Install\Status\OkStatus;

/**
 * Send a test mail
 */
class MailTest extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction(): array
    {
        $messages = [];
        $recipient = $this->postValues['email'];
        if (empty($recipient) || !GeneralUtility::validEmail($recipient)) {
            $message = new ErrorStatus();
            $message->setTitle('Mail not sent');
            $message->setMessage('Given address is not a valid email address.');
            $messages[] = $message;
        } else {
            $mailMessage = GeneralUtility::makeInstance(MailMessage::class);
            $mailMessage
                ->addTo($recipient)
                ->addFrom($this->getSenderEmailAddress(), $this->getSenderEmailName())
                ->setSubject($this->getEmailSubject())
                ->setBody('<html><body>html test content</body></html>', 'text/html')
                ->addPart('TEST CONTENT')
                ->send();
            $message = new OkStatus();
            $message->setTitle('Test mail sent');
            $message->setMessage('Recipient: ' . $recipient);
            $messages[] = $message;
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messages,
        ]);
        return $this->view->render();
    }

    /**
     * Get sender address from configuration
     * ['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
     * If this setting is empty fall back to 'no-reply@example.com'
     *
     * @return string Returns an email address
     */
    protected function getSenderEmailAddress(): string
    {
        return !empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'])
            ? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
            : 'no-reply@example.com';
    }

    /**
     * Gets sender name from configuration
     * ['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']
     * If this setting is empty, it falls back to a default string.
     *
     * @return string
     */
    protected function getSenderEmailName(): string
    {
        return !empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'])
            ? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']
            : 'TYPO3 CMS install tool';
    }

    /**
     * Gets email subject from configuration
     * ['TYPO3_CONF_VARS']['SYS']['sitename']
     * If this setting is empty, it falls back to a default string.
     *
     * @return string
     */
    protected function getEmailSubject(): string
    {
        $name = !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'])
            ? ' from site "' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '"'
            : '';
        return 'Test TYPO3 CMS mail delivery' . $name;
    }
}

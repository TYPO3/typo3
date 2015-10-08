<?php
namespace TYPO3\CMS\Compatibility6\Controller;

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
use TYPO3\CMS\Core\Utility\MailUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Formmail class
 * used to submit data, and hooks into TSFE
 */
class FormDataSubmissionController
{
    /**
     * @var string
     */
    protected $reserved_names = 'recipient,recipient_copy,auto_respond_msg,auto_respond_checksum,redirect,subject,attachment,from_email,from_name,replyto_email,replyto_name,organisation,priority,html_enabled,quoted_printable,submit_x,submit_y';

    /**
     * Collection of suspicious header data, used for logging
     *
     * @var array
     */
    protected $dirtyHeaders = array();

    /**
     * @var string
     */
    protected $characterSet;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var string
     */
    protected $fromName;

    /**
     * @var string
     */
    protected $replyToName;

    /**
     * @var string
     */
    protected $organisation;

    /**
     * @var string
     */
    protected $fromAddress;

    /**
     * @var string
     */
    protected $replyToAddress;

    /**
     * @var int
     */
    protected $priority;

    /**
     * @var string
     */
    protected $autoRespondMessage;

    /**
     * @var string
     */
    protected $encoding = 'quoted-printable';

    /**
     * @var \TYPO3\CMS\Core\Mail\MailMessage
     */
    protected $mailMessage;

    /**
     * @var string
     */
    protected $recipient;

    /**
     * @var string
     */
    protected $plainContent = '';

    /**
     * @var array Files to clean up at the end (attachments)
     */
    protected $temporaryFiles = array();

    /**
     * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected $frontendController = null;

    /**
     * hook to be executed by TypoScriptFrontendController
     *
     * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $frontendController
     */
    public function checkDataSubmission($frontendController)
    {
        $this->frontendController = $frontendController;

        // Checks if any email-submissions
        $formtype_mail = isset($_POST['formtype_mail']) || isset($_POST['formtype_mail_x']);
        if ($formtype_mail) {
            $refInfo = parse_url(GeneralUtility::getIndpEnv('HTTP_REFERER'));
            if (GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY') == $refInfo['host'] || $this->frontendController->TYPO3_CONF_VARS['SYS']['doNotCheckReferer']) {
                if ($this->locDataCheck($_POST['locationData'])) {
                    if ($formtype_mail) {
                        $this->prepareAndSend();
                        $GLOBALS['TT']->setTSlogMessage('"Check Data Submission": Return value: email', 0);
                    }
                }
            } else {
                $GLOBALS['TT']->setTSlogMessage('"Check Data Submission": HTTP_HOST and REFERER HOST did not match when processing submitted formdata!', 3);
            }
        }
    }

    /**
     * Checks if a formmail submission can be sent as email
     *
     * @param string $locationData The input from $_POST['locationData']
     * @return void|int
     */
    protected function locDataCheck($locationData)
    {
        $locData = explode(':', $locationData);
        if (!$locData[1] || $this->frontendController->sys_page->checkRecord($locData[1], $locData[2], 1)) {
            // $locData[1] -check means that a record is checked only if the locationData has a value for a record else than the page.
            if (!empty($this->frontendController->sys_page->getPage($locData[0]))) {
                return 1;
            }
            $GLOBALS['TT']->setTSlogMessage('LocationData Error: The page pointed to by location data (' . $locationData . ') was not accessible.', 2);
        } else {
            $GLOBALS['TT']->setTSlogMessage('LocationData Error: Location data (' . $locationData . ') record pointed to was not accessible.', 2);
        }
    }

    /**
     * Sends the emails from the formmail content object.
     *
     * @return void
     */
    protected function prepareAndSend()
    {
        $EMAIL_VARS = GeneralUtility::_POST();
        $locationData = $EMAIL_VARS['locationData'];
        unset($EMAIL_VARS['locationData']);
        unset($EMAIL_VARS['formtype_mail'], $EMAIL_VARS['formtype_mail_x'], $EMAIL_VARS['formtype_mail_y']);
        $integrityCheck = $this->frontendController->TYPO3_CONF_VARS['FE']['strictFormmail'];
        if (!$this->frontendController->TYPO3_CONF_VARS['FE']['secureFormmail']) {
            // Check recipient field:
            // These two fields are the ones which contain recipient addresses that can be misused to send mail from foreign servers.
            $encodedFields = explode(',', 'recipient, recipient_copy');
            foreach ($encodedFields as $fieldKey) {
                if ((string)$EMAIL_VARS[$fieldKey] !== '') {
                    // Decode...
                    if ($res = \TYPO3\CMS\Compatibility6\Utility\FormUtility::codeString($EMAIL_VARS[$fieldKey], true)) {
                        $EMAIL_VARS[$fieldKey] = $res;
                    } elseif ($integrityCheck) {
                        // Otherwise abort:
                        $GLOBALS['TT']->setTSlogMessage('"Formmail" discovered a field (' . $fieldKey . ') which could not be decoded to a valid string. Sending formmail aborted due to security reasons!', 3);
                        return;
                    } else {
                        $GLOBALS['TT']->setTSlogMessage('"Formmail" discovered a field (' . $fieldKey . ') which could not be decoded to a valid string. The security level accepts this, but you should consider a correct coding though!', 2);
                    }
                }
            }
        } else {
            $locData = explode(':', $locationData);
            $record = $this->frontendController->sys_page->checkRecord($locData[1], $locData[2], 1);
            $EMAIL_VARS['recipient'] = $record['subheader'];
            $EMAIL_VARS['recipient_copy'] = $this->extractRecipientCopy($record['bodytext']);
        }
        // Hook for preprocessing of the content for formmails:
        if (is_array($this->frontendController->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['sendFormmail-PreProcClass'])) {
            foreach ($this->frontendController->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['sendFormmail-PreProcClass'] as $_classRef) {
                $_procObj = GeneralUtility::getUserObj($_classRef);
                $EMAIL_VARS = $_procObj->sendFormmail_preProcessVariables($EMAIL_VARS, $this);
            }
        }
        $this->start($EMAIL_VARS);
        $r = $this->sendtheMail();
        $GLOBALS['TT']->setTSlogMessage('"Formmail" invoked, sending mail to ' . $EMAIL_VARS['recipient'], 0);
    }

    /**
     * Extracts the value of recipient copy field from a formmail CE bodytext
     *
     * @param string $bodytext The content of the related bodytext field
     * @return string The value of the recipient_copy field, or an empty string
     */
    protected function extractRecipientCopy($bodytext)
    {
        $fdef = array();
        //|recipient_copy=hidden|karsten@localhost.localdomain
        preg_match('/^[\\s]*\\|[\\s]*recipient_copy[\\s]*=[\\s]*hidden[\\s]*\\|(.*)$/m', $bodytext, $fdef);
        return $fdef[1] ?: '';
    }

    /**
     * Start function
     * This class is able to generate a mail in formmail-style from the data in $V
     * Fields:
     *
     * [recipient]:			email-adress of the one to receive the mail. If array, then all values are expected to be recipients
     * [attachment]:		....
     *
     * [subject]:			The subject of the mail
     * [from_email]:		Sender email. If not set, [email] is used
     * [from_name]:			Sender name. If not set, [name] is used
     * [replyto_email]:		Reply-to email. If not set [from_email] is used
     * [replyto_name]:		Reply-to name. If not set [from_name] is used
     * [organisation]:		Organization (header)
     * [priority]:			Priority, 1-5, default 3
     * [html_enabled]:		If mail is sent as html
     * [use_base64]:		If set, base64 encoding will be used instead of quoted-printable
     *
     * @param array $valueList Contains values for the field names listed above (with slashes removed if from POST input)
     * @param bool $base64 Whether to base64 encode the mail content
     * @return void
     */
    public function start($valueList, $base64 = false)
    {
        $this->mailMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);
        if ($GLOBALS['TSFE']->config['config']['formMailCharset']) {
            // Respect formMailCharset if it was set
            $this->characterSet = $GLOBALS['TSFE']->csConvObj->parse_charset($GLOBALS['TSFE']->config['config']['formMailCharset']);
        } elseif ($GLOBALS['TSFE']->metaCharset != $GLOBALS['TSFE']->renderCharset) {
            // Use metaCharset for mail if different from renderCharset
            $this->characterSet = $GLOBALS['TSFE']->metaCharset;
        } else {
            // Otherwise use renderCharset as default
            $this->characterSet = $GLOBALS['TSFE']->renderCharset;
        }
        if ($base64 || $valueList['use_base64']) {
            $this->encoding = 'base64';
        }
        if (isset($valueList['recipient'])) {
            // Convert form data from renderCharset to mail charset
            $this->subject = $valueList['subject'] ? $valueList['subject'] : 'Formmail on ' . GeneralUtility::getIndpEnv('HTTP_HOST');
            $this->subject = $this->sanitizeHeaderString($this->subject);
            $this->fromName = $valueList['from_name'] ? $valueList['from_name'] : ($valueList['name'] ? $valueList['name'] : '');
            $this->fromName = $this->sanitizeHeaderString($this->fromName);
            $this->replyToName = $valueList['replyto_name'] ? $valueList['replyto_name'] : $this->fromName;
            $this->replyToName = $this->sanitizeHeaderString($this->replyToName);
            $this->organisation = $valueList['organisation'] ? $valueList['organisation'] : '';
            $this->organisation = $this->sanitizeHeaderString($this->organisation);
            $this->fromAddress = $valueList['from_email'] ? $valueList['from_email'] : ($valueList['email'] ? $valueList['email'] : '');
            if (!GeneralUtility::validEmail($this->fromAddress)) {
                $this->fromAddress = MailUtility::getSystemFromAddress();
                $this->fromName = MailUtility::getSystemFromName();
            }
            $this->replyToAddress = $valueList['replyto_email'] ? $valueList['replyto_email'] : $this->fromAddress;
            $this->priority = $valueList['priority'] ? MathUtility::forceIntegerInRange($valueList['priority'], 1, 5) : 3;
            // Auto responder
            $this->autoRespondMessage = trim($valueList['auto_respond_msg']) && $this->fromAddress ? trim($valueList['auto_respond_msg']) : '';
            if ($this->autoRespondMessage !== '') {
                // Check if the value of the auto responder message has been modified with evil intentions
                $autoRespondChecksum = $valueList['auto_respond_checksum'];
                $correctHmacChecksum = GeneralUtility::hmac($this->autoRespondMessage, 'content_form');
                if ($autoRespondChecksum !== $correctHmacChecksum) {
                    GeneralUtility::sysLog('Possible misuse of DataSubmissionController auto respond method. Subject: ' . $valueList['subject'], 'core', GeneralUtility::SYSLOG_SEVERITY_ERROR);
                    return;
                } else {
                    $this->autoRespondMessage = $this->sanitizeHeaderString($this->autoRespondMessage);
                }
            }
            $plainTextContent = '';
            $htmlContent = '<table border="0" cellpadding="2" cellspacing="2">';
            // Runs through $V and generates the mail
            if (is_array($valueList)) {
                foreach ($valueList as $key => $val) {
                    if (!GeneralUtility::inList($this->reserved_names, $key)) {
                        $space = strlen($val) > 60 ? LF : '';
                        $val = is_array($val) ? implode($val, LF) : $val;
                        // Convert form data from renderCharset to mail charset (HTML may use entities)
                        $plainTextValue = $val;
                        $HtmlValue = htmlspecialchars($val);
                        $plainTextContent .= strtoupper($key) . ':  ' . $space . $plainTextValue . LF . $space;
                        $htmlContent .= '<tr><td bgcolor="#eeeeee"><font face="Verdana" size="1"><strong>' . strtoupper($key) . '</strong></font></td><td bgcolor="#eeeeee"><font face="Verdana" size="1">' . nl2br($HtmlValue) . '&nbsp;</font></td></tr>';
                    }
                }
            }
            $htmlContent .= '</table>';
            $this->plainContent = $plainTextContent;
            if ($valueList['html_enabled']) {
                $this->mailMessage->setBody($htmlContent, 'text/html', $this->characterSet);
                $this->mailMessage->addPart($plainTextContent, 'text/plain', $this->characterSet);
            } else {
                $this->mailMessage->setBody($plainTextContent, 'text/plain', $this->characterSet);
            }
            for ($a = 0; $a < 10; $a++) {
                $variableName = 'attachment' . ($a ?: '');
                if (!isset($_FILES[$variableName])) {
                    continue;
                }

                if ($_FILES[$variableName]['error'] !== UPLOAD_ERR_OK) {
                    GeneralUtility::sysLog(
                        'Error in uploaded file in DataSubmissionController: temporary file "' .
                            $_FILES[$variableName]['tmp_name'] . '" ("' . $_FILES[$variableName]['name'] . '") Error code: ' .
                            $_FILES[$variableName]['error'],
                        'core',
                        GeneralUtility::SYSLOG_SEVERITY_ERROR
                    );
                    continue;
                }

                if (!is_uploaded_file($_FILES[$variableName]['tmp_name'])) {
                    GeneralUtility::sysLog(
                        'Possible abuse of DataSubmissionController: temporary file "' . $_FILES[$variableName]['tmp_name'] .
                            '" ("' . $_FILES[$variableName]['name'] . '") was not an uploaded file.',
                        'core',
                        GeneralUtility::SYSLOG_SEVERITY_ERROR
                    );
                    continue;
                }

                $theFile = GeneralUtility::upload_to_tempfile($_FILES[$variableName]['tmp_name']);
                $theName = $_FILES[$variableName]['name'];
                if ($theFile && file_exists($theFile)) {
                    if (filesize($theFile) < $GLOBALS['TYPO3_CONF_VARS']['FE']['formmailMaxAttachmentSize']) {
                        $this->mailMessage->attach(\Swift_Attachment::fromPath($theFile)->setFilename($theName));
                    }
                }
                $this->temporaryFiles[] = $theFile;
            }
            $from = $this->fromName ? array($this->fromAddress => $this->fromName) : array($this->fromAddress);
            $this->recipient = $this->parseAddresses($valueList['recipient']);
            $this->mailMessage->setSubject($this->subject)->setFrom($from)->setTo($this->recipient)->setPriority($this->priority);
            $replyTo = $this->replyToName ? array($this->replyToAddress => $this->replyToName) : array($this->replyToAddress);
            $this->mailMessage->setReplyTo($replyTo);
            $this->mailMessage->getHeaders()->addTextHeader('Organization', $this->organisation);
            if ($valueList['recipient_copy']) {
                $this->mailMessage->setCc($this->parseAddresses($valueList['recipient_copy']));
            }
            $this->mailMessage->setCharset($this->characterSet);
            // Ignore target encoding. This is handled automatically by Swift Mailer and overriding the defaults
            // is not worth the trouble
            // Log dirty header lines
            if ($this->dirtyHeaders) {
                GeneralUtility::sysLog('Possible misuse of DataSubmissionController: see TYPO3 devLog', 'core', GeneralUtility::SYSLOG_SEVERITY_ERROR);
                if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_DLOG']) {
                    GeneralUtility::devLog('DataSubmissionController: ' . GeneralUtility::arrayToLogString($this->dirtyHeaders, '', 200), 'Core', 3);
                }
            }
        }
    }

    /**
     * Checks string for suspicious characters
     *
     * @param string $string String to check
     * @return string Valid or empty string
     */
    protected function sanitizeHeaderString($string)
    {
        $pattern = '/[\\r\\n\\f\\e]/';
        if (preg_match($pattern, $string) > 0) {
            $this->dirtyHeaders[] = $string;
            $string = '';
        }
        return $string;
    }

    /**
     * Parses mailbox headers and turns them into an array.
     *
     * Mailbox headers are a comma separated list of 'name <email@example.org' combinations or plain email addresses (or a mix
     * of these).
     * The resulting array has key-value pairs where the key is either a number (no display name in the mailbox header) and the
     * value is the email address, or the key is the email address and the value is the display name.
     *
     * @param string $rawAddresses Comma separated list of email addresses (optionally with display name)
     * @return array Parsed list of addresses.
     */
    protected function parseAddresses($rawAddresses = '')
    {
        /** @var $addressParser \TYPO3\CMS\Core\Mail\Rfc822AddressesParser */
        $addressParser = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\Rfc822AddressesParser::class, $rawAddresses);
        $addresses = $addressParser->parseAddressList();
        $addressList = array();
        foreach ($addresses as $address) {
            if ($address->personal) {
                // Item with name found ( name <email@example.org> )
                $addressList[$address->mailbox . '@' . $address->host] = $address->personal;
            } else {
                // Item without name found ( email@example.org )
                $addressList[] = $address->mailbox . '@' . $address->host;
            }
        }
        return $addressList;
    }

    /**
     * Sends the actual mail and handles autorespond message
     *
     * @return bool
     */
    public function sendTheMail()
    {
        // Sending the mail requires the recipient and message to be set.
        if (!$this->mailMessage->getTo() || !trim($this->mailMessage->getBody())) {
            return false;
        }
        $this->mailMessage->send();
        // Auto response
        if ($this->autoRespondMessage) {
            $theParts = explode('/', $this->autoRespondMessage, 2);
            $theParts[0] = str_replace('###SUBJECT###', $this->subject, $theParts[0]);
            $theParts[1] = str_replace(
                array('/', '###MESSAGE###'),
                array(LF, $this->plainContent),
                $theParts[1]
            );
            /** @var $autoRespondMail \TYPO3\CMS\Core\Mail\MailMessage */
            $autoRespondMail = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);
            $autoRespondMail->setTo($this->fromAddress)->setSubject($theParts[0])->setFrom($this->recipient)->setBody($theParts[1]);
            $autoRespondMail->send();
        }
        return $this->mailMessage->isSent();
    }

    /**
     * Do some cleanup at the end (deleting attachment files)
     */
    public function __destruct()
    {
        foreach ($this->temporaryFiles as $file) {
            if (GeneralUtility::isAllowedAbsPath($file) && GeneralUtility::isFirstPartOfStr($file, PATH_site . 'typo3temp/upload_temp_')) {
                GeneralUtility::unlink_tempfile($file);
            }
        }
    }
}

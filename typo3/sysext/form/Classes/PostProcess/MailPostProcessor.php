<?php
namespace TYPO3\CMS\Form\PostProcess;

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

use TYPO3\CMS\Core\Mail\Rfc822AddressesParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MailUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Form\Utility\FormUtility;

/**
 * The mail post processor
 */
class MailPostProcessor extends AbstractPostProcessor implements PostProcessorInterface
{
    /**
     * Constant for localization
     *
     * @var string
     */
    const LOCALISATION_OBJECT_NAME = 'tx_form_view_mail';

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Form\Utility\SessionUtility
     */
    protected $sessionUtility;

    /**
     * @var FormUtility
     */
    protected $formUtility;

    /**
     * @var \TYPO3\CMS\Form\Domain\Model\Element
     */
    protected $form;

    /**
     * @var array
     */
    protected $typoScript;

    /**
     * @var \TYPO3\CMS\Core\Mail\MailMessage
     */
    protected $mailMessage;

    /**
     * @var string
     */
    protected $htmlMailTemplatePath = 'Html';

    /**
     * @var string
     */
    protected $plaintextMailTemplatePath = 'Plain';

    /**
     * @var array
     */
    protected $dirtyHeaders = [];

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
     * @return void
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Form\Utility\SessionUtility $sessionUtility
     * @return void
     */
    public function injectSessionUtility(\TYPO3\CMS\Form\Utility\SessionUtility $sessionUtility)
    {
        $this->sessionUtility = $sessionUtility;
    }

    /**
     * Constructor
     *
     * @param \TYPO3\CMS\Form\Domain\Model\Element $form Form domain model
     * @param array $typoScript Post processor TypoScript settings
     */
    public function __construct(\TYPO3\CMS\Form\Domain\Model\Element $form, array $typoScript)
    {
        $this->form = $form;
        $this->typoScript = $typoScript;
        $this->mailMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);
        $this->setTemplatePaths();
    }

    /**
     * The main method called by the post processor
     *
     * Configures the mail message
     *
     * @return string HTML message from this processor
     */
    public function process()
    {
        $this->formUtility = FormUtility::create($this->controllerContext->getConfiguration());
        $this->setSubject();
        $this->setFrom();
        $this->setTo();
        $this->setCc();
        $this->setReplyTo();
        $this->setPriority();
        $this->setOrganization();
        $this->setHtmlContent();
        $this->setPlainContent();
        $this->addAttachmentsFromSession();
        $this->send();
        return $this->render();
    }

    /**
     * Sets the subject of the mail message
     *
     * If not configured, it will use a default setting
     *
     * @return void
     */
    protected function setSubject()
    {
        if (isset($this->typoScript['subject'])) {
            $subject = $this->formUtility->renderItem(
                $this->typoScript['subject.'],
                $this->typoScript['subject']
            );
        } elseif ($this->getTypoScriptValueFromIncomingData('subjectField') !== null) {
            $subject = $this->getTypoScriptValueFromIncomingData('subjectField');
        } else {
            $subject = 'Formmail on ' . GeneralUtility::getIndpEnv('HTTP_HOST');
        }

        $subject = $this->sanitizeHeaderString($subject);
        $this->mailMessage->setSubject($subject);
    }

    /**
     * Sets the sender of the mail message
     *
     * Mostly the sender is a combination of the name and the email address
     *
     * @return void
     */
    protected function setFrom()
    {
        if (isset($this->typoScript['senderEmail'])) {
            $fromEmail = $this->formUtility->renderItem(
                $this->typoScript['senderEmail.'],
                $this->typoScript['senderEmail']
            );
        } elseif ($this->getTypoScriptValueFromIncomingData('senderEmailField') !== null) {
            $fromEmail = $this->getTypoScriptValueFromIncomingData('senderEmailField');
        } else {
            $fromEmail = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
        }
        if (!GeneralUtility::validEmail($fromEmail)) {
            $fromEmail = MailUtility::getSystemFromAddress();
        }
        if (isset($this->typoScript['senderName'])) {
            $fromName = $this->formUtility->renderItem(
                $this->typoScript['senderName.'],
                $this->typoScript['senderName']
            );
        } elseif ($this->getTypoScriptValueFromIncomingData('senderNameField') !== null) {
            $fromName = $this->getTypoScriptValueFromIncomingData('senderNameField');
        } else {
            $fromName = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'];
        }
        $fromName = $this->sanitizeHeaderString($fromName);
        if (!empty($fromName)) {
            $from = [$fromEmail => $fromName];
        } else {
            $from = $fromEmail;
        }
        $this->mailMessage->setFrom($from);
    }

    /**
     * Filter input-string for valid email addresses
     *
     * @param string $emails If this is a string, it will be checked for one or more valid email addresses.
     * @return array List of valid email addresses
     */
    protected function filterValidEmails($emails)
    {
        if (!is_string($emails)) {
            // No valid addresses - empty list
            return [];
        }

        /** @var $addressParser Rfc822AddressesParser */
        $addressParser = GeneralUtility::makeInstance(Rfc822AddressesParser::class, $emails);
        $addresses = $addressParser->parseAddressList();

        $validEmails = [];
        foreach ($addresses as $address) {
            $fullAddress = $address->mailbox . '@' . $address->host;
            if (GeneralUtility::validEmail($fullAddress)) {
                if ($address->personal) {
                    $validEmails[$fullAddress] = $address->personal;
                } else {
                    $validEmails[] = $fullAddress;
                }
            }
        }
        return $validEmails;
    }

    /**
     * Adds the receiver of the mail message when configured
     *
     * Checks the address if it is a valid email address
     *
     * @return void
     */
    protected function setTo()
    {
        $emails = $this->formUtility->renderItem(
            $this->typoScript['recipientEmail.'],
            $this->typoScript['recipientEmail']
        );
        $validEmails = $this->filterValidEmails($emails);
        if (!empty($validEmails)) {
            $this->mailMessage->setTo($validEmails);
        }
    }

    /**
     * Adds the carbon copy receiver of the mail message when configured
     *
     * Checks the address if it is a valid email address
     *
     * @return void
     */
    protected function setCc()
    {
        $emails = $this->formUtility->renderItem(
            $this->typoScript['ccEmail.'],
            $this->typoScript['ccEmail']
        );
        $validEmails = $this->filterValidEmails($emails);
        if (!empty($validEmails)) {
            $this->mailMessage->setCc($validEmails);
        }
    }

    /**
     * Adds the reply to header of the mail message when configured
     *
     * Checks the address if it is a valid email address
     *
     * @return void
     */
    protected function setReplyTo()
    {
        if (isset($this->typoScript['replyToEmail'])) {
            $emails = $this->formUtility->renderItem(
                $this->typoScript['replyToEmail.'],
                $this->typoScript['replyToEmail']
            );
        } elseif ($this->getTypoScriptValueFromIncomingData('replyToEmailField') !== null) {
            $emails = $this->getTypoScriptValueFromIncomingData('replyToEmailField');
        }
        $validEmails = $this->filterValidEmails($emails);
        if (!empty($validEmails)) {
            $this->mailMessage->setReplyTo($validEmails);
        }
    }

    /**
     * Set the priority of the mail message
     *
     * When not in settings, the value will be 3. If the priority is configured,
     * but too big, it will be set to 5, which means very low.
     *
     * @return void
     */
    protected function setPriority()
    {
        $priority = 3;
        if (isset($this->typoScript['priority'])) {
            $priorityFromTs = $this->formUtility->renderItem(
                $this->typoScript['priority.'],
                $this->typoScript['priority']
            );
        }
        if (!empty($priorityFromTs)) {
            $priority = MathUtility::forceIntegerInRange($priorityFromTs, 1, 5);
        }
        $this->mailMessage->setPriority($priority);
    }

    /**
     * Add a text header to the mail header of the type Organization
     *
     * Sanitizes the header string when necessary
     *
     * @return void
     */
    protected function setOrganization()
    {
        if (isset($this->typoScript['organization'])) {
            $organization = $this->formUtility->renderItem(
                $this->typoScript['organization.'],
                $this->typoScript['organization']
            );
        }
        if (!empty($organization)) {
            $organization = $this->sanitizeHeaderString($organization);
            $this->mailMessage->getHeaders()->addTextHeader('Organization', $organization);
        }
    }

    /**
     * Set the default character set used
     *
     * Respect formMailCharset if it was set, otherwise use metaCharset for mail
     * if different from renderCharset
     *
     * @return void
     */
    protected function setCharacterSet()
    {
        $characterSet = null;
        if ($GLOBALS['TSFE']->config['config']['formMailCharset']) {
            $characterSet = $GLOBALS['TSFE']->csConvObj->parse_charset($GLOBALS['TSFE']->config['config']['formMailCharset']);
        } elseif ($GLOBALS['TSFE']->metaCharset != $GLOBALS['TSFE']->renderCharset) {
            $characterSet = $GLOBALS['TSFE']->metaCharset;
        }
        if ($characterSet) {
            $this->mailMessage->setCharset($characterSet);
        }
    }

    /**
     * Add the HTML content
     *
     * Add a MimePart of the type text/html to the message.
     *
     * @return void
     */
    protected function setHtmlContent()
    {
        $htmlContent = $this->getView($this->htmlMailTemplatePath)->render();
        $this->mailMessage->setBody($htmlContent, 'text/html');
    }

    /**
     * Add the plain content
     *
     * Add a MimePart of the type text/plain to the message.
     *
     * @return void
     */
    protected function setPlainContent()
    {
        $plainContent = $this->getView($this->plaintextMailTemplatePath, 'Plain')->render();
        $this->mailMessage->addPart($plainContent, 'text/plain');
    }

    /**
     * Sends the mail.
     * Sending the mail requires the recipient and message to be set.
     *
     * @return void
     */
    protected function send()
    {
        if ($this->mailMessage->getTo() && $this->mailMessage->getBody()) {
            $this->mailMessage->send();
        }
    }

    /**
     * Render the message after trying to send the mail
     *
     * @return string HTML message from the mail view
     */
    protected function render()
    {
        if ($this->mailMessage->isSent()) {
            $output = $this->renderMessage('success');
        } else {
            $output = $this->renderMessage('error');
        }
        return $output;
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
     * Loop through all elements of the session and attach the file
     * if its a uploaded file
     *
     * @return void
     */
    protected function addAttachmentsFromSession()
    {
        $sessionData = $this->sessionUtility->getSessionData();
        if (is_array($sessionData)) {
            foreach ($sessionData as $fieldName => $values) {
                if (is_array($values)) {
                    foreach ($values as $file) {
                        if (isset($file['tempFilename'])) {
                            if (
                                is_file($file['tempFilename'])
                                && GeneralUtility::isAllowedAbsPath($file['tempFilename'])
                            ) {
                                $this->mailMessage->attach(\Swift_Attachment::fromPath($file['tempFilename'])->setFilename($file['name']));
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Set the html and plaintext templates
     *
     * @return void
     */
    protected function setTemplatePaths()
    {
        if (
            isset($this->typoScript['htmlMailTemplatePath'])
            && $this->typoScript['htmlMailTemplatePath'] !== ''
        ) {
            $this->htmlMailTemplatePath = $this->typoScript['htmlMailTemplatePath'];
        }

        if (
            isset($this->typoScript['plaintextMailTemplatePath'])
            && $this->typoScript['plaintextMailTemplatePath'] !== ''
        ) {
            $this->plaintextMailTemplatePath = $this->typoScript['plaintextMailTemplatePath'];
        }
    }

    /**
     * Make fluid view instance
     *
     * @param string $templateName
     * @param string $scope
     * @return \TYPO3\CMS\Fluid\View\StandaloneView
     */
    protected function getView($templateName, $scope = 'Html')
    {
        $configurationManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::class);
        $typoScript = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $view */
        $view = $this->objectManager->get(\TYPO3\CMS\Fluid\View\StandaloneView::class);

        $viewParts = [
            'templateRootPaths' => $typoScript['view']['templateRootPaths'],
            'partialRootPaths' => $typoScript['view']['partialRootPaths'],
        ];
        /* Extend all template root paths to $templateRootPaths/PostProcessor/Mail/$themeName  */
        foreach ($typoScript['view']['templateRootPaths'] as &$path) {
            if (substr($path, -1) !== '/') {
                $path .= '/';
            }
            $path .= 'PostProcessor/Mail/' . $this->controllerContext->getConfiguration()->getThemeName();
        }
        /* Extend all partial root paths to $partialRootPaths/$themeName/PostProcessor/Mail/$scope/  */
        foreach ($typoScript['view']['partialRootPaths'] as &$path) {
            if (substr($path, -1) !== '/') {
                $path .= '/';
            }
            $path .=  $this->controllerContext->getConfiguration()->getThemeName() . '/PostProcessor/Mail/' . $scope . '/';
        }
        $view->setLayoutRootPaths($typoScript['view']['layoutRootPaths']);
        $view->setTemplateRootPaths($typoScript['view']['templateRootPaths']);
        $view->setPartialRootPaths($typoScript['view']['partialRootPaths']);
        $view->setTemplate($templateName);
        $view->assignMultiple([
            'model' => $this->form
        ]);
        return $view;
    }

    /**
     * Render the processor message
     *
     * @param string $messageType
     * @return string
     */
    protected function renderMessage($messageType)
    {
        return $this->formUtility->renderItem(
            $this->typoScript['messages.'][$messageType . '.'],
            $this->typoScript['messages.'][$messageType],
            $this->getLocalLanguageLabel($messageType)
        );
    }

    /**
     * Get the local language label(s) for the message
     * In some cases this method will be override by rule class
     *
     * @param string $type The type
     * @return string The local language message label
     */
    protected function getLocalLanguageLabel($type = '')
    {
        $label = static::LOCALISATION_OBJECT_NAME . '.' . $type;
        $message = LocalizationUtility::translate($label, 'form');
        return $message;
    }

    /**
     * Determines user submitted data from a field
     * that has been defined as TypoScript property.
     *
     * @param string $propertyName
     * @return NULL|mixed
     */
    protected function getTypoScriptValueFromIncomingData($propertyName)
    {
        if (empty($this->typoScript[$propertyName])) {
            return null;
        }

        $propertyValue = $this->typoScript[$propertyName];
        $incomingData = $this->controllerContext->getValidationElement();
        if (!$incomingData->hasIncomingField($propertyValue)) {
            return null;
        }

        return $incomingData->getIncomingField($propertyValue);
    }
}

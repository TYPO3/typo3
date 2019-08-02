<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Finishers;

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

use Symfony\Component\Mime\NamedAddress;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\CMS\Form\ViewHelpers\RenderRenderableViewHelper;

/**
 * This finisher sends an email to one recipient
 *
 * Options:
 *
 * - templatePathAndFilename (mandatory): Template path and filename for the mail body
 * - layoutRootPath: root path for the layouts
 * - partialRootPath: root path for the partials
 * - variables: associative array of variables which are available inside the Fluid template
 *
 * The following options control the mail sending. In all of them, placeholders in the form
 * of {...} are replaced with the corresponding form value; i.e. {email} as senderAddress
 * makes the recipient address configurable.
 *
 * - subject (mandatory): Subject of the email
 * - recipients (mandatory): Email addresses and human-readable names of the recipients
 * - senderAddress (mandatory): Email address of the sender
 * - senderName: Human-readable name of the sender
 * - replyToRecipients: Email addresses and human-readable names of the reply-to recipients
 * - carbonCopyRecipients: Email addresses and human-readable names of the copy recipients
 * - blindCarbonCopyRecipients: Email addresses and human-readable names of the blind copy recipients
 * - format: Format of the email (one of the FORMAT_* constants). By default mails are sent as HTML.
 *
 * Scope: frontend
 */
class EmailFinisher extends AbstractFinisher
{
    const FORMAT_PLAINTEXT = 'plaintext';
    const FORMAT_HTML = 'html';

    /**
     * @var array
     */
    protected $defaultOptions = [
        'recipientName' => '',
        'senderName' => '',
        'addHtmlPart' => true,
        'attachUploads' => true,
    ];

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     *
     * @throws FinisherException
     */
    protected function executeInternal()
    {
        // Flexform overrides write strings instead of integers so
        // we need to cast the string '0' to false.
        if (
            isset($this->options['addHtmlPart'])
            && $this->options['addHtmlPart'] === '0'
        ) {
            $this->options['addHtmlPart'] = false;
        }

        $subject = $this->parseOption('subject');
        $recipients = $this->getRecipients('recipients', 'recipientAddress', 'recipientName');
        $senderAddress = $this->parseOption('senderAddress');
        $senderName = $this->parseOption('senderName');
        $replyToRecipients = $this->getRecipients('replyToRecipients', 'replyToAddress');
        $carbonCopyRecipients = $this->getRecipients('carbonCopyRecipients', 'carbonCopyAddress');
        $blindCarbonCopyRecipients = $this->getRecipients('blindCarbonCopyRecipients', 'blindCarbonCopyAddress');
        $addHtmlPart = $this->isHtmlPartAdded();
        $attachUploads = $this->parseOption('attachUploads');

        if (empty($subject)) {
            throw new FinisherException('The option "subject" must be set for the EmailFinisher.', 1327060320);
        }
        if (empty($recipients)) {
            throw new FinisherException('The option "recipients" must be set for the EmailFinisher.', 1327060200);
        }
        if (empty($senderAddress)) {
            throw new FinisherException('The option "senderAddress" must be set for the EmailFinisher.', 1327060210);
        }

        $mail = $this->objectManager->get(MailMessage::class);

        $mail->from(new NamedAddress($senderAddress, $senderName))
            ->to(...$recipients)
            ->subject($subject);

        if (!empty($replyToRecipients)) {
            $mail->replyTo(...$replyToRecipients);
        }

        if (!empty($carbonCopyRecipients)) {
            $mail->cc(...$carbonCopyRecipients);
        }

        if (!empty($blindCarbonCopyRecipients)) {
            $mail->bcc(...$blindCarbonCopyRecipients);
        }

        $formRuntime = $this->finisherContext->getFormRuntime();

        $translationService = TranslationService::getInstance();
        if (isset($this->options['translation']['language']) && !empty($this->options['translation']['language'])) {
            $languageBackup = $translationService->getLanguage();
            $translationService->setLanguage($this->options['translation']['language']);
        }

        $parts = [
            [
                'format' => 'Plaintext',
                'contentType' => 'text/plain',
            ],
        ];

        if ($addHtmlPart) {
            $parts[] = [
                'format' => 'Html',
                'contentType' => 'text/html',
            ];
        }

        foreach ($parts as $i => $part) {
            $standaloneView = $this->initializeStandaloneView($formRuntime, $part['format']);
            $message = $standaloneView->render();

            if ($part['contentType'] === 'text/plain') {
                $mail->text($message);
            } else {
                $mail->html($message);
            }
        }

        if (!empty($languageBackup)) {
            $translationService->setLanguage($languageBackup);
        }

        $elements = $formRuntime->getFormDefinition()->getRenderablesRecursively();

        if ($attachUploads) {
            foreach ($elements as $element) {
                if (!$element instanceof FileUpload) {
                    continue;
                }
                $file = $formRuntime[$element->getIdentifier()];
                if ($file) {
                    if ($file instanceof FileReference) {
                        $file = $file->getOriginalResource();
                    }

                    $mail->attach($file->getContents(), $file->getName(), $file->getMimeType());
                }
            }
        }

        $mail->send();
    }

    /**
     * @param FormRuntime $formRuntime
     * @param string $format
     * @return StandaloneView
     * @throws FinisherException
     */
    protected function initializeStandaloneView(FormRuntime $formRuntime, string $format): StandaloneView
    {
        $standaloneView = $this->objectManager->get(StandaloneView::class);

        if (isset($this->options['templatePathAndFilename'])) {
            $this->options['templatePathAndFilename'] = strtr($this->options['templatePathAndFilename'], [
                '{@format}' => $format
            ]);
            $standaloneView->setTemplatePathAndFilename($this->options['templatePathAndFilename']);
        } else {
            if (!isset($this->options['templateName'])) {
                throw new FinisherException('The option "templateName" must be set for the EmailFinisher.', 1327058829);
            }
            // Use local variable instead of augmenting the options to
            // keep the format intact when sending multi-format mails
            $templateName = strtr($this->options['templateName'], [
                '{@format}' => $format
            ]);
            $standaloneView->setTemplate($templateName);
        }

        $standaloneView->assign('finisherVariableProvider', $this->finisherContext->getFinisherVariableProvider());

        if (isset($this->options['templateRootPaths']) && is_array($this->options['templateRootPaths'])) {
            $standaloneView->setTemplateRootPaths($this->options['templateRootPaths']);
        }

        if (isset($this->options['partialRootPaths']) && is_array($this->options['partialRootPaths'])) {
            $standaloneView->setPartialRootPaths($this->options['partialRootPaths']);
        }

        if (isset($this->options['layoutRootPaths']) && is_array($this->options['layoutRootPaths'])) {
            $standaloneView->setLayoutRootPaths($this->options['layoutRootPaths']);
        }

        if (isset($this->options['variables'])) {
            $standaloneView->assignMultiple($this->options['variables']);
        }

        $standaloneView->assign('form', $formRuntime);
        $standaloneView->getRenderingContext()
            ->getViewHelperVariableContainer()
            ->addOrUpdate(RenderRenderableViewHelper::class, 'formRuntime', $formRuntime);

        return $standaloneView;
    }

    /**
     * Get mail recipients
     *
     * @param string $listOption List option name
     * @param string $singleAddressOption Single address option
     * @param string $singleAddressName Single address name
     * @return array
     *
     * @deprecated since TYPO3 v10.0, will be removed in TYPO3 v11.0.
     */
    protected function getRecipients(
        string $listOption,
        string $singleAddressOption,
        string $singleAddressNameOption = null
    ): array {
        $recipients = $this->parseOption($listOption);
        $singleAddress = $this->parseOption($singleAddressOption);
        $singleAddressName = '';

        $recipients = $recipients ?? [];

        if (!empty($singleAddress)) {
            trigger_error(sprintf(
                'EmailFinisher option "%s" is deprecated and will be removed in TYPO3 v11.0. Use "%s" instead.',
                $singleAddressOption,
                $listOption
            ), E_USER_DEPRECATED);

            if (!empty($singleAddressNameOption)) {
                trigger_error(sprintf(
                    'EmailFinisher option "%s" is deprecated and will be removed in TYPO3 v11.0. Use "%s" instead.',
                    $singleAddressNameOption,
                    $listOption
                ), E_USER_DEPRECATED);
                $singleAddressName = $this->parseOption($singleAddressNameOption);
            }

            $recipients[$singleAddress] = $singleAddressName ?: '';
        }

        $addresses = [];
        foreach ($recipients as $address => $name) {
            if (MathUtility::canBeInterpretedAsInteger($address)) {
                $address = $name;
                $name = '';
            }
            // Drop entries without mail address
            if (empty($address)) {
                continue;
            }
            $addresses[] = new NamedAddress($address, $name);
        }
        return $addresses;
    }

    /**
     * Get plaintext preference
     *
     * @return bool
     *
     * @deprecated since TYPO3 v10.0, will be removed in TYPO3 v11.0.
     */
    protected function isHtmlPartAdded(): bool
    {
        $format = $this->parseOption('format');

        if ($format !== null) {
            trigger_error(
                'Usage of format option in form email finisher is deprecated - use addHtmlPart instead.',
                E_USER_DEPRECATED
            );
        }

        // FORMAT_HTML was the default value for "format", so
        // FORMAT_PLAINTEXT must have been set intentionally
        if ($format === self::FORMAT_PLAINTEXT) {
            return false;
        }

        return $this->parseOption('addHtmlPart') ? true : false;
    }
}

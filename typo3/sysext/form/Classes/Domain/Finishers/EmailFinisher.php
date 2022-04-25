<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Form\Domain\Finishers;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\View\TemplatePaths;
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
 * - templatePathAndFilename (mandatory for Mail): Template path and filename for the mail body
 * - templateName (mandatory for FluidEmail): Template name for the mail body
 * - templateRootPaths: root paths for the templates
 * - layoutRootPaths: root paths for the layouts
 * - partialRootPaths: root paths for the partials
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
 *
 * Scope: frontend
 */
class EmailFinisher extends AbstractFinisher
{
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
        $languageBackup = null;
        // Flexform overrides write strings instead of integers so
        // we need to cast the string '0' to false.
        if (
            isset($this->options['addHtmlPart'])
            && $this->options['addHtmlPart'] === '0'
        ) {
            $this->options['addHtmlPart'] = false;
        }

        $subject = $this->parseOption('subject');
        $recipients = $this->getRecipients('recipients');
        $senderAddress = $this->parseOption('senderAddress');
        $senderAddress = is_string($senderAddress) ? $senderAddress : '';
        $senderName = $this->parseOption('senderName');
        $senderName = is_string($senderName) ? $senderName : '';
        $replyToRecipients = $this->getRecipients('replyToRecipients');
        $carbonCopyRecipients = $this->getRecipients('carbonCopyRecipients');
        $blindCarbonCopyRecipients = $this->getRecipients('blindCarbonCopyRecipients');
        $addHtmlPart = $this->parseOption('addHtmlPart') ? true : false;
        $attachUploads = $this->parseOption('attachUploads');
        $useFluidEmail = $this->parseOption('useFluidEmail');
        $title = $this->parseOption('title');
        $title = is_string($title) && $title !== '' ? $title : $subject;

        if (empty($subject)) {
            throw new FinisherException('The option "subject" must be set for the EmailFinisher.', 1327060320);
        }
        if (empty($recipients)) {
            throw new FinisherException('The option "recipients" must be set for the EmailFinisher.', 1327060200);
        }
        if (empty($senderAddress)) {
            throw new FinisherException('The option "senderAddress" must be set for the EmailFinisher.', 1327060210);
        }

        $formRuntime = $this->finisherContext->getFormRuntime();

        $translationService = GeneralUtility::makeInstance(TranslationService::class);
        if (is_string($this->options['translation']['language'] ?? null) && $this->options['translation']['language'] !== '') {
            $languageBackup = $translationService->getLanguage();
            $translationService->setLanguage($this->options['translation']['language']);
        }

        $mail = $useFluidEmail
            ? $this
                ->initializeFluidEmail($formRuntime)
                ->format($addHtmlPart ? FluidEmail::FORMAT_BOTH : FluidEmail::FORMAT_PLAIN)
                ->assign('title', $title)
            : GeneralUtility::makeInstance(MailMessage::class);

        $mail
            ->from(new Address($senderAddress, $senderName))
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

        if (!$useFluidEmail) {
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
        }

        if (!empty($languageBackup)) {
            $translationService->setLanguage($languageBackup);
        }

        if ($attachUploads) {
            foreach ($formRuntime->getFormDefinition()->getRenderablesRecursively() as $element) {
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

        $useFluidEmail ? GeneralUtility::makeInstance(Mailer::class)->send($mail) : $mail->send();
    }

    /**
     * @param FormRuntime $formRuntime
     * @param string $format
     * @return StandaloneView
     * @throws FinisherException
     * @deprecated since v11, will be removed in v12
     */
    protected function initializeStandaloneView(FormRuntime $formRuntime, string $format): StandaloneView
    {
        trigger_error(
            'Using StandaloneView for EmailFinisher \'' . $this->finisherIdentifier . '\' is deprecated and will be removed in v12. Please use FluidEmail in the finishers configuration instead.',
            E_USER_DEPRECATED
        );

        $standaloneView = GeneralUtility::makeInstance(StandaloneView::class);

        if (isset($this->options['templatePathAndFilename'])) {
            trigger_error(
                'The option \'templatePathAndFilename\' for EmailFinisher \'' . $this->finisherIdentifier . '\' is deprecated and will be removed in v12. Please use \'templateName\' and \'templateRootPaths\' in the finishers definition instead.',
                E_USER_DEPRECATED
            );
            // Use local variable instead of augmenting the options to
            // keep the format intact when sending multi-format mails
            $templatePathAndFilename = strtr($this->options['templatePathAndFilename'], [
                '{@format}' => $format,
            ]);
            $standaloneView->setTemplatePathAndFilename($templatePathAndFilename);
        } else {
            if (!isset($this->options['templateName'])) {
                throw new FinisherException('The option "templateName" must be set for the EmailFinisher.', 1327058829);
            }
            // Use local variable instead of augmenting the options to
            // keep the format intact when sending multi-format mails
            $templateName = strtr($this->options['templateName'], [
                '{@format}' => $format,
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

        if (is_array($this->options['variables'] ?? null)) {
            $standaloneView->assignMultiple($this->options['variables']);
        }

        $standaloneView->assign('form', $formRuntime);
        $standaloneView->getRenderingContext()
                       ->getViewHelperVariableContainer()
                       ->addOrUpdate(RenderRenderableViewHelper::class, 'formRuntime', $formRuntime);

        return $standaloneView;
    }

    protected function initializeFluidEmail(FormRuntime $formRuntime): FluidEmail
    {
        $templateConfiguration = $GLOBALS['TYPO3_CONF_VARS']['MAIL'];

        if (is_array($this->options['templateRootPaths'] ?? null)) {
            $templateConfiguration['templateRootPaths'] = array_replace_recursive(
                $templateConfiguration['templateRootPaths'],
                $this->options['templateRootPaths']
            );
            ksort($templateConfiguration['templateRootPaths']);
        }

        if (is_array($this->options['partialRootPaths'] ?? null)) {
            $templateConfiguration['partialRootPaths'] = array_replace_recursive(
                $templateConfiguration['partialRootPaths'],
                $this->options['partialRootPaths']
            );
            ksort($templateConfiguration['partialRootPaths']);
        }

        if (is_array($this->options['layoutRootPaths'] ?? null)) {
            $templateConfiguration['layoutRootPaths'] = array_replace_recursive(
                $templateConfiguration['layoutRootPaths'],
                $this->options['layoutRootPaths']
            );
            ksort($templateConfiguration['layoutRootPaths']);
        }

        $fluidEmail = GeneralUtility::makeInstance(
            FluidEmail::class,
            GeneralUtility::makeInstance(TemplatePaths::class, $templateConfiguration)
        );

        if (!isset($this->options['templateName']) || $this->options['templateName'] === '') {
            throw new FinisherException('The option "templateName" must be set to use FluidEmail.', 1599834020);
        }

        // Migrate old template name to default FluidEmail name
        if ($this->options['templateName'] === '{@format}.html') {
            $this->options['templateName'] = 'Default';
        }

        // Set the PSR-7 request object if available
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface) {
            $fluidEmail->setRequest($GLOBALS['TYPO3_REQUEST']);
        }

        $fluidEmail
            ->setTemplate($this->options['templateName'])
            ->assignMultiple([
                'finisherVariableProvider' => $this->finisherContext->getFinisherVariableProvider(),
                'form' => $formRuntime,
            ]);

        if (is_array($this->options['variables'] ?? null)) {
            $fluidEmail->assignMultiple($this->options['variables']);
        }

        $fluidEmail
            ->getViewHelperVariableContainer()
            ->addOrUpdate(RenderRenderableViewHelper::class, 'formRuntime', $formRuntime);

        return $fluidEmail;
    }

    /**
     * Get mail recipients
     *
     * @param string $listOption List option name
     * @return array
     */
    protected function getRecipients(string $listOption): array
    {
        $recipients = $this->parseOption($listOption) ?? [];
        if (!is_array($recipients) || $recipients === []) {
            return [];
        }

        $addresses = [];
        foreach ($recipients as $address => $name) {
            if (!GeneralUtility::validEmail($address)) {
                // Drop entries without valid address
                continue;
            }
            $addresses[] = new Address($address, $name);
        }
        return $addresses;
    }
}

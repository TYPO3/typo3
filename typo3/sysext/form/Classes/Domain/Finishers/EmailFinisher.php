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

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Mail\TemplatedEmailFactory;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Event\BeforeEmailFinisherInitializedEvent;
use TYPO3\CMS\Form\ViewHelpers\RenderRenderableViewHelper;

/**
 * This finisher sends an email to one recipient
 *
 * Options:
 *
 * - templateName (mandatory): Template name for the mail body
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
 * - title: The title of the email - If not set "subject" is used by default
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

    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly TemplatedEmailFactory $templatedEmailFactory,
        protected readonly MailerInterface $mailer,
    ) {}

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     *
     * @throws FinisherException
     */
    protected function executeInternal(): void
    {
        $this->options = $this->eventDispatcher
            ->dispatch(new BeforeEmailFinisherInitializedEvent($this->finisherContext, $this->options))
            ->getOptions();
        // Flexform overrides write strings instead of integers so
        // we need to cast the string '0' to false.
        if (
            isset($this->options['addHtmlPart'])
            && $this->options['addHtmlPart'] === '0'
        ) {
            $this->options['addHtmlPart'] = false;
        }

        $subject = (string)$this->parseOption('subject');
        $recipients = $this->getRecipients('recipients');
        $senderAddress = $this->parseOption('senderAddress');
        $senderAddress = is_string($senderAddress) ? $senderAddress : '';
        $senderName = $this->parseOption('senderName');
        $senderName = is_string($senderName) ? $senderName : '';
        $replyToRecipients = $this->getRecipients('replyToRecipients');
        $carbonCopyRecipients = $this->getRecipients('carbonCopyRecipients');
        $blindCarbonCopyRecipients = $this->getRecipients('blindCarbonCopyRecipients');
        $addHtmlPart = (bool)$this->parseOption('addHtmlPart');
        $attachUploads = $this->parseOption('attachUploads');
        $title = (string)$this->parseOption('title') ?: $subject;

        if ($subject === '') {
            throw new FinisherException('The option "subject" must be set for the EmailFinisher.', 1327060320);
        }
        if (empty($recipients)) {
            throw new FinisherException('The option "recipients" must be set for the EmailFinisher.', 1327060200);
        }
        if (empty($senderAddress)) {
            throw new FinisherException('The option "senderAddress" must be set for the EmailFinisher.', 1327060210);
        }

        $formRuntime = $this->finisherContext->getFormRuntime();

        $mail = $this
            ->initializeFluidEmail($formRuntime)
            ->from(new Address($senderAddress, $senderName))
            ->to(...$recipients)
            ->subject($subject)
            ->format($addHtmlPart ? FluidEmail::FORMAT_BOTH : FluidEmail::FORMAT_PLAIN)
            ->assign('title', $title);

        if (!empty($replyToRecipients)) {
            $mail->replyTo(...$replyToRecipients);
        }

        if (!empty($carbonCopyRecipients)) {
            $mail->cc(...$carbonCopyRecipients);
        }

        if (!empty($blindCarbonCopyRecipients)) {
            $mail->bcc(...$blindCarbonCopyRecipients);
        }

        if (is_string($this->options['translation']['language'] ?? null) && $this->options['translation']['language'] !== '') {
            $mail->assign('languageKey', $this->options['translation']['language']);
        }

        $message = $this->parseOption('message');
        if (is_string($message) && $message !== '') {
            // Remove whitespace between HTML tags to prevent lib.parseFunc_RTE
            // from converting newlines into additional blank lines in the email output
            $message = preg_replace('/>\s+</', '><', $message);
            $placeholderPos = strpos($message, '{formValues}');
            if ($placeholderPos !== false) {
                $mail->assign('messageBefore', substr($message, 0, $placeholderPos));
                $mail->assign('messageAfter', substr($message, $placeholderPos + strlen('{formValues}')));
            } else {
                // No placeholder - show message only, no form values
                $mail->assign('messageBefore', $message);
                $mail->assign('messageAfter', '');
                $mail->assign('hideFormValues', true);
            }
        }

        if ($attachUploads) {
            foreach ($formRuntime->getFormDefinition()->getRenderablesRecursively() as $element) {
                if (!$element instanceof FileUpload) {
                    continue;
                }
                $file = $formRuntime[$element->getIdentifier()];
                if ($file instanceof FileReference) {
                    $file = $file->getOriginalResource();
                }
                if ($file instanceof FileInterface) {
                    $mail->attach($file->getContents(), $file->getName(), $file->getMimeType());
                } elseif ($file instanceof ObjectStorage) {
                    foreach ($file as $singleFile) {
                        if ($singleFile instanceof FileReference) {
                            $singleFile = $singleFile->getOriginalResource();
                        }
                        if ($singleFile instanceof FileInterface) {
                            $mail->attach($singleFile->getContents(), $singleFile->getName(), $singleFile->getMimeType());
                        }
                    }
                }
            }
        }

        try {
            $this->mailer->send($mail);
        } catch (TransportExceptionInterface $e) {
            throw new FinisherException(
                'Failed to send the email: ' . $e->getMessage(),
                1754047320,
                $e
            );
        }
    }

    protected function initializeFluidEmail(FormRuntime $formRuntime): FluidEmail
    {
        $mailMessage = $this->templatedEmailFactory->createWithOverrides(
            templateRootPaths: $this->options['templateRootPaths'] ?? [],
            layoutRootPaths: $this->options['layoutRootPaths'] ?? [],
            partialRootPaths: $this->options['partialRootPaths'] ?? [],
            request: $this->finisherContext->getRequest(),
        );

        if (!isset($this->options['templateName']) || $this->options['templateName'] === '') {
            throw new FinisherException('The option "templateName" must be set to use FluidEmail.', 1599834020);
        }

        // Migrate old template name to default FluidEmail name
        if ($this->options['templateName'] === '{@format}.html') {
            $this->options['templateName'] = 'Default';
        }

        $mailMessage
            ->setTemplate($this->options['templateName'])
            ->assignMultiple([
                'finisherVariableProvider' => $this->finisherContext->getFinisherVariableProvider(),
                'form' => $formRuntime,
            ]);

        if (is_array($this->options['variables'] ?? null)) {
            $mailMessage->assignMultiple($this->options['variables']);
        }

        $mailMessage
            ->getViewHelperVariableContainer()
            ->addOrUpdate(RenderRenderableViewHelper::class, 'formRuntime', $formRuntime);

        return $mailMessage;
    }

    protected function getRecipients(string $listOption): array
    {
        $recipients = $this->parseOption($listOption) ?? [];
        if (!is_array($recipients) || $recipients === []) {
            return [];
        }

        $addresses = [];
        foreach ($recipients as $address => $name) {
            // The if is needed to set address and name with TypoScript
            if (MathUtility::canBeInterpretedAsInteger($address)) {
                if (is_array($name)) {
                    $address = $name[0] ?? '';
                    $name = $name[1] ?? '';
                } else {
                    $address = $name;
                    $name = '';
                }
            }

            $address = trim((string)$address);

            if (!GeneralUtility::validEmail($address)) {
                // Drop entries without a valid address
                continue;
            }
            $addresses[] = new Address($address, $name);
        }
        return $addresses;
    }
}

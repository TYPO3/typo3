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

/*
 * Inspired by and partially taken from the Neos.Form package (www.neos.io)
 */

namespace TYPO3\CMS\Form\Domain\Finishers;

use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Message;
use TYPO3\CMS\Extbase\Error\Notice;
use TYPO3\CMS\Extbase\Error\Warning;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;

/**
 * A simple finisher that adds a message to the FlashMessageContainer
 *
 * Usage:
 * //...
 * $flashMessageFinisher = GeneralUtility::makeInstance(FlashMessageFinisher::class);
 * $flashMessageFinisher->setOptions(
 *   [
 *     'messageBody' => 'Some message body',
 *     'messageTitle' => 'Some message title',
 *     'messageArguments' => ['foo' => 'bar'],
 *     'severity' => \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR
 *   ]
 * );
 * $formDefinition->addFinisher($flashMessageFinisher);
 * // ...
 *
 * Scope: frontend
 */
class FlashMessageFinisher extends AbstractFinisher
{

    /**
     * @var array
     */
    protected $defaultOptions = [
        'messageBody' => null,
        'messageTitle' => '',
        'messageArguments' => [],
        'messageCode' => null,
        'severity' => AbstractMessage::OK,
    ];

    private ExtensionService $extensionService;
    private FlashMessageService $flashMessageService;

    public function injectFlashMessageService(FlashMessageService $flashMessageService): void
    {
        $this->flashMessageService = $flashMessageService;
    }

    public function injectExtensionService(ExtensionService $extensionService): void
    {
        $this->extensionService = $extensionService;
    }

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     *
     * @throws FinisherException
     */
    protected function executeInternal()
    {
        $messageBody = $this->parseOption('messageBody');
        if (!is_string($messageBody)) {
            throw new FinisherException(sprintf('The message body must be of type string, "%s" given.', gettype($messageBody)), 1335980069);
        }
        $messageTitle = $this->parseOption('messageTitle');
        $messageArguments = $this->parseOption('messageArguments');
        $messageCode = $this->parseOption('messageCode');
        $severity = $this->parseOption('severity');
        switch ($severity) {
            case AbstractMessage::NOTICE:
                $message = GeneralUtility::makeInstance(Notice::class, $messageBody, $messageCode, $messageArguments, $messageTitle);
                break;
            case AbstractMessage::WARNING:
                $message = GeneralUtility::makeInstance(Warning::class, $messageBody, $messageCode, $messageArguments, $messageTitle);
                break;
            case AbstractMessage::ERROR:
                $message = GeneralUtility::makeInstance(Error::class, $messageBody, $messageCode, $messageArguments, $messageTitle);
                break;
            default:
                $message = GeneralUtility::makeInstance(Message::class, $messageBody, $messageCode, $messageArguments, $messageTitle);
                break;
        }

        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message->render(),
            $message->getTitle(),
            $severity,
            true
        );

        // todo: this value has to be taken from the request directly in the future
        $pluginNamespace = $this->extensionService->getPluginNamespace(
            $this->finisherContext->getRequest()->getControllerExtensionName(),
            $this->finisherContext->getRequest()->getPluginName()
        );

        $this->flashMessageService->getMessageQueueByIdentifier('extbase.flashmessages.' . $pluginNamespace)->addMessage($flashMessage);
    }
}

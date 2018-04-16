<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Finishers;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It originated from the Neos.Form package (www.neos.io)
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

use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Message;
use TYPO3\CMS\Extbase\Error\Notice;
use TYPO3\CMS\Extbase\Error\Warning;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;

/**
 * A simple finisher that adds a message to the FlashMessageContainer
 *
 * Usage:
 * //...
 * $flashMessageFinisher = $this->objectManager->get(FlashMessageFinisher::class);
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
                $message = $this->objectManager->get(Notice::class, $messageBody, $messageCode, $messageArguments, $messageTitle);
                break;
            case AbstractMessage::WARNING:
                $message = $this->objectManager->get(Warning::class, $messageBody, $messageCode, $messageArguments, $messageTitle);
                break;
            case AbstractMessage::ERROR:
                $message = $this->objectManager->get(Error::class, $messageBody, $messageCode, $messageArguments, $messageTitle);
                break;
            default:
                $message = $this->objectManager->get(Message::class, $messageBody, $messageCode, $messageArguments, $messageTitle);
                break;
        }

        $flashMessage = $this->objectManager->get(
            FlashMessage::class,
            $message->render(),
            $message->getTitle(),
            $severity,
            true
        );

        $this->finisherContext->getControllerContext()->getFlashMessageQueue()->addMessage($flashMessage);
    }
}

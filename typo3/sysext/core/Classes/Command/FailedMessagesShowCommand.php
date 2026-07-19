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

namespace TYPO3\CMS\Core\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\Messenger\Command\FailedMessagesShowCommand as SymfonyFailedMessagesShowCommand;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Contracts\Service\ServiceProviderInterface;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;
use TYPO3\CMS\Core\Messenger\FailureTransport;

/**
 * TYPO3-native registration of the Symfony "messenger:failed:show" command,
 * bound to TYPO3's failure transports and the configured global failure
 * transport ($GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport']).
 *
 * Not offered as a scheduler task: it inspects the failure transport on the
 * console and has no meaning as a recurring background job.
 */
#[AsCommand(name: 'messenger:failed:show', description: 'Show one or more messages from the failure transport')]
#[AsNonSchedulableCommand]
final class FailedMessagesShowCommand extends SymfonyFailedMessagesShowCommand
{
    public function __construct(
        #[AutowireLocator('messenger.failure_transport', indexAttribute: 'identifier')]
        ServiceProviderInterface $failureTransports,
        PhpSerializer $phpSerializer,
        FailureTransport $failureTransport,
    ) {
        parent::__construct($failureTransport->globalName(), $failureTransports, $phpSerializer);
    }
}

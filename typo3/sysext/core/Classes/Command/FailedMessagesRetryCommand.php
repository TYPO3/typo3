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

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Stamp\SentToFailureTransportStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Receiver\ListableReceiverInterface;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;
use TYPO3\CMS\Core\Messenger\FailureTransport;

/**
 * Retries messages parked in a failure transport by handling them again on
 * the message bus, as received from the transport they originally failed on.
 * Handlers that succeeded before are skipped by their HandledStamp. A message
 * that succeeds is removed; a message that fails again is parked anew with
 * the new error and an increased retry count.
 *
 * Not offered as a scheduler task: retrying is a manual, console operation.
 */
#[AsCommand(name: 'messenger:failed:retry', description: 'Retry one or more messages from the failure transport')]
#[AsNonSchedulableCommand]
final class FailedMessagesRetryCommand extends Command
{
    public function __construct(
        #[AutowireLocator('messenger.failure_transport', indexAttribute: 'identifier')]
        private readonly ContainerInterface $failureTransports,
        #[Autowire(service: 'messenger.bus.default')]
        private readonly MessageBusInterface $messageBus,
        private readonly FailureTransport $failureTransport,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::IS_ARRAY, 'Specific message id(s) to retry; all pending messages when omitted')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Retry without confirmation')
            ->addOption('transport', null, InputOption::VALUE_REQUIRED, 'Use a specific failure transport instead of the configured one')
            ->setHelp(
                <<<'EOF2'
The <info>%command.name%</info> command retries messages that were parked in the
failure transport by handling them again:

    <info>php %command.full_name% <id></info>

Pass several ids to retry more than one message, or omit the id to retry every
pending message (requires --force in non-interactive mode):

    <info>php %command.full_name% --force</info>

A message that succeeds is removed from the failure transport. A message that
fails again is parked again with the new error, and the command exits with a
failure code.
EOF2
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $transportName = $input->getOption('transport') ?: $this->failureTransport->globalName();
        if ($transportName === null) {
            $io->error('No failure transport is configured ($GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'messenger\'][\'failure_transport\']).');
            return Command::FAILURE;
        }
        if (!$this->failureTransports->has($transportName)) {
            $io->error(sprintf('The "%s" failure transport does not exist.', $transportName));
            return Command::FAILURE;
        }
        $receiver = $this->failureTransports->get($transportName);
        if (!$receiver instanceof ListableReceiverInterface) {
            $io->error(sprintf('The "%s" failure transport does not support listing messages.', $transportName));
            return Command::FAILURE;
        }

        $ids = $input->getArgument('id');
        $force = (bool)$input->getOption('force');

        if ($ids === []) {
            if (!$input->isInteractive() && !$force) {
                $io->error('Pass one or more message ids, or use --force to retry all failed messages.');
                return Command::FAILURE;
            }
            $envelopes = iterator_to_array($receiver->all(), false);
        } else {
            $envelopes = [];
            foreach ($ids as $id) {
                $envelope = $receiver->find($id);
                if ($envelope === null) {
                    $io->error(sprintf('The message "%s" was not found in the "%s" failure transport.', $id, $transportName));
                    return Command::FAILURE;
                }
                $envelopes[] = $envelope;
            }
        }

        if ($envelopes === []) {
            $io->success('No failed messages to retry.');
            return Command::SUCCESS;
        }

        $retried = 0;
        $failedAgain = 0;
        foreach ($envelopes as $envelope) {
            $id = $envelope->last(TransportMessageIdStamp::class)?->getId();
            if (!$force && !$io->confirm(sprintf('Retry message #%s (%s)?', $id ?? '?', $envelope->getMessage()::class), true)) {
                continue;
            }
            if ($this->retry($receiver, $envelope, $io)) {
                ++$retried;
            } else {
                ++$failedAgain;
            }
        }

        if ($failedAgain > 0) {
            $io->error(sprintf(
                'Retried %d message(s); %d failed again and stayed in the "%s" failure transport.',
                $retried,
                $failedAgain,
                $transportName,
            ));
            return Command::FAILURE;
        }
        $io->success(sprintf('Retried %d message(s).', $retried));
        return Command::SUCCESS;
    }

    private function retry(ListableReceiverInterface $receiver, Envelope $envelope, SymfonyStyle $io): bool
    {
        $originalTransport = $envelope->last(SentToFailureTransportStamp::class)?->getOriginalReceiverName();
        if ($originalTransport === null || $originalTransport === '') {
            $io->warning(sprintf('Message %s does not name the transport it failed on and stays in the failure transport.', $envelope->getMessage()::class));
            return false;
        }
        try {
            $this->messageBus->dispatch($this->withoutTransportStamps($envelope)->with(new ReceivedStamp($originalTransport)));
        } catch (\Throwable $exception) {
            $io->warning(sprintf('Message %s failed again: %s', $envelope->getMessage()::class, $exception->getMessage()));
            if ($receiver instanceof SenderInterface) {
                $failedEnvelope = $exception instanceof HandlerFailedException ? $exception->getEnvelope() : $envelope;
                $receiver->send($this->withoutTransportStamps($failedEnvelope)->with(
                    ErrorDetailsStamp::create($exception),
                    new RedeliveryStamp(($envelope->last(RedeliveryStamp::class)?->getRetryCount() ?? 0) + 1),
                ));
                $receiver->reject($envelope);
            }
            return false;
        }
        $receiver->ack($envelope);
        return true;
    }

    private function withoutTransportStamps(Envelope $envelope): Envelope
    {
        return $envelope
            ->withoutStampsOfType(NonSendableStampInterface::class)
            ->withoutStampsOfType(TransportMessageIdStamp::class);
    }
}

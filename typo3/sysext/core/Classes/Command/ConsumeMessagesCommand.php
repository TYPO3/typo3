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

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Worker;
use TYPO3\CMS\Core\Messenger\EventListener\StopWorkerOnTimeLimitListener;

/**
 * Heavily stripped-down version of the symfony command with the same name.
 */
class ConsumeMessagesCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly ServiceLocator $receiverLocator,
        private readonly StopWorkerOnTimeLimitListener $stopWorkerOnTimeLimitListener,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly array $receiverNames = [],
        private readonly array $busIds = [],
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $defaultReceiverName = count($this->receiverNames) === 1 ? current($this->receiverNames) : null;

        $this
            ->setDefinition(
                [
                    new InputArgument(
                        'receivers',
                        InputArgument::IS_ARRAY,
                        'Names of the receivers/transports to consume in order of priority',
                        $defaultReceiverName ? [$defaultReceiverName] : []
                    ),
                    new InputOption('sleep', null, InputOption::VALUE_REQUIRED, 'Seconds to sleep before asking for new messages after no messages were found', 1),
                    new InputOption('queues', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Limit receivers to only consume from the specified queues'),
                    new InputOption('exit-code-on-limit', null, InputOption::VALUE_REQUIRED, 'Exit code when limits are reached', 0),
                ]
            )
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</info> command consumes messages and dispatches them to the message bus.

    <info>php %command.full_name% <receiver-name></info>

To receive from multiple transports, pass each name:

    <info>php %command.full_name% receiver1 receiver2</info>

Use the --queues option to limit a receiver to only certain queues (only supported by some receivers):

    <info>php %command.full_name% <receiver-name> --queues=fasttrack</info>

EOF
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output);

        if ($this->receiverNames && !$input->getArgument('receivers')) {
            $io->block('Which transports/receivers do you want to consume?', null, 'fg=white;bg=blue', ' ', true);

            $io->writeln('Choose which receivers you want to consume messages from in order of priority.');
            if (count($this->receiverNames) > 1) {
                $io->writeln(sprintf('Hint: to consume from multiple, use a list of their names, e.g. <comment>%s</comment>', implode(', ', $this->receiverNames)));
            }

            $question = new ChoiceQuestion('Select receivers to consume:', $this->receiverNames, 0);
            $question->setMultiselect(true);

            $input->setArgument('receivers', $io->askQuestion($question));
        }

        if (!$input->getArgument('receivers')) {
            throw new RuntimeException('Please pass at least one receiver.', 1605305001);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exitCodeOnLimit = (int)($input->getOption('exit-code-on-limit'));

        $receivers = [];
        $rateLimiters = [];
        $receiverNames = $input->getArgument('receivers');
        foreach ($receiverNames as $receiverName) {
            if (!$this->receiverLocator->has($receiverName)) {
                $message = sprintf('The receiver "%s" does not exist.', $receiverName);
                if ($this->receiverNames) {
                    $message .= sprintf(' Valid receivers are: %s.', implode(', ', $this->receiverNames));
                }

                throw new RuntimeException($message, 1605305002);
            }

            $receivers[$receiverName] = $this->receiverLocator->get($receiverName);
        }

        $io = new SymfonyStyle($input, $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output);
        $io->success(sprintf('Consuming messages from transport%s "%s".', count($receivers) > 1 ? 's' : '', implode(', ', $receiverNames)));
        $io->comment('Quit the worker with CONTROL-C.');

        if ($output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            $io->comment('Re-run the command with a -vv option to see logs about consumed messages.');
        }

        $worker = new Worker($receivers, $this->messageBus, $this->eventDispatcher, new ConsoleLogger($output), $rateLimiters);
        $options = [
            'sleep' => $input->getOption('sleep') * 1000000,
        ];
        $queues = $input->getOption('queues');
        if ($queues) {
            $options['queues'] = $queues;
        }
        $worker->run($options);

        return $this->stopWorkerOnTimeLimitListener->hasStopped() ? $exitCodeOnLimit : 0;
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestArgumentValuesFor('receivers')) {
            $suggestions->suggestValues(array_diff($this->receiverNames, array_diff($input->getArgument('receivers'), [$input->getCompletionValue()])));

            return;
        }

        if ($input->mustSuggestOptionValuesFor('bus')) {
            $suggestions->suggestValues($this->busIds);
        }
    }
}

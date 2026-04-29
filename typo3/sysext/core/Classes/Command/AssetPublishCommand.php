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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Command\Output\MessageRenderer;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Install\Middleware\AssetPublishing;

class AssetPublishCommand extends Command
{
    public function __construct(
        protected readonly BootService $bootService,
        protected readonly PackageManager $packageManager,
        protected readonly MessageRenderer $messageRenderer,
    ) {
        parent::__construct('asset:publish');
    }

    /**
     * Defines the allowed options for this command
     */
    protected function configure(): void
    {
        $this->setDescription('Publish public assets.');
        $this->setHelp(
            'Publishes public assets. '
            . 'Needs to be run after composer install.'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $failsafeContainer = $this->bootService->getFailsafeContainer();
        $failsafeResourcePublisher = $failsafeContainer->has(AssetPublishing::class) ? $failsafeContainer->get(SystemResourcePublisherInterface::class) : null;
        try {
            $container = $this->bootService->loadExtLocalconfDatabaseAndExtTables(false, false);
        } catch (\Throwable $e) {
            if ($output->isVerbose()) {
                throw $e;
            }
            $output->writeln('<error>Can not initialize dependency injection container. Increase verbosity to get the full error message.</error>');
            return self::FAILURE;
        }
        $resourcePublisher = $container->get(SystemResourcePublisherInterface::class);

        $output->getFormatter()->setStyle('bold', new OutputFormatterStyle(null, null, ['bold']));
        $output->writeln('<bold>Publishing assets from extensions…</bold>');

        $exitCode = self::SUCCESS;
        foreach ($this->packageManager->getActivePackages() as $package) {
            $messages = $resourcePublisher->publishResources($package);
            if ($package->isPartOfMinimalUsableSystem()) {
                // Publish resources for install tool, if it is installed
                $failsafeResourcePublisher?->publishResources($package);
            }
            $exitCode = $this->determineExitCode($exitCode, $messages);
            $this->messageRenderer->renderAll($messages, $output);
        }

        $output->writeln('<bold>done.</bold>');
        return $exitCode;
    }

    private function determineExitCode(int $currentCode, FlashMessageQueue $queue): int
    {
        if ($currentCode === self::FAILURE) {
            return self::FAILURE;
        }
        foreach ($queue->getAllMessages() as $message) {
            if ($message->getSeverity() === ContextualFeedbackSeverity::ERROR) {
                return self::FAILURE;
            }
        }
        return $currentCode;
    }
}

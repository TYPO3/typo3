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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\Event\PackageInitializationEvent;
use TYPO3\CMS\Core\Package\Event\PackagesMayHaveChangedEvent;
use TYPO3\CMS\Core\Package\Initialization\CheckForImportRequirements;
use TYPO3\CMS\Core\Package\PackageActivationService;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Command for setting up all extensions via CLI.
 */
class SetupExtensionsCommand extends Command
{
    public function __construct(
        private readonly PackageManager $packageManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly PackageActivationService $packageActivationService,
    ) {
        parent::__construct();
    }

    /**
     * Defines the allowed options for this command
     */
    protected function configure()
    {
        $this
            ->setDescription('Set up extensions')
            ->setHelp(
                <<<'EOD'
Setup all extensions or the given extension by extension key. This must
be performed after new extensions are required via Composer.

The command performs all necessary setup operations, such as database
schema changes, static data import, distribution files import etc.

The given extension keys must be recognized by TYPO3 or will be ignored.
EOD
            )
            ->addOption(
                'extension',
                '-e',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Only set up extensions with given key'
            );
    }

    /**
     * Sets up one or all extensions
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Bootstrap::initializeBackendAuthentication();
        $this->eventDispatcher->dispatch(new PackagesMayHaveChangedEvent());

        $io = new SymfonyStyle($input, $output);
        $extensionKeys = $input->getOption('extension');
        $extensionsToSetUp = $this->packageManager->getActivePackages();
        if (!empty($extensionKeys)) {
            $extensionsToSetUp = array_filter(
                $extensionsToSetUp,
                static function ($extKey) use ($extensionKeys) {
                    return in_array($extKey, $extensionKeys, true);
                },
                ARRAY_FILTER_USE_KEY
            );
        }
        if (empty($extensionsToSetUp)) {
            $io->error('Given extension(s) "' . implode(', ', $extensionKeys) . '" not found in the system.');
            return Command::FAILURE;
        }
        $this->packageActivationService->updateDatabase();
        foreach ($extensionsToSetUp as $extensionKey => $package) {
            $event = $this->eventDispatcher->dispatch(
                new PackageInitializationEvent(extensionKey: $extensionKey, package: $package, emitter: $this)
            );
            if ($event->hasStorageEntry(CheckForImportRequirements::class)) {
                $io->warning(
                    $event->getStorageEntry(CheckForImportRequirements::class)->getResult()['exception']?->getMessage() ?? ''
                );
            }
        }
        $io->success('Extension(s) "' . implode(', ', array_keys($extensionsToSetUp)) . '" successfully set up.');

        return Command::SUCCESS;
    }
}

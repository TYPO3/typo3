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

namespace TYPO3\CMS\Extensionmanager\Command;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\Event\PackagesMayHaveChangedEvent;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

/**
 * Command for setting up all extensions via CLI.
 */
class SetupExtensionsCommand extends Command
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var InstallUtility
     */
    private $installUtility;

    /**
     * @var PackageManager
     */
    private PackageManager $packageManager;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        InstallUtility $installUtility,
        PackageManager $packageManager
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->installUtility = $installUtility;
        $this->packageManager = $packageManager;
        parent::__construct();
    }

    /**
     * Defines the allowed options for this command
     */
    protected function configure()
    {
        $this
            ->setDescription('Set up extensions')
            ->setHelp('The given extension keys must be recognized by TYPO3, or will be ignored otherwise')
            ->addOption(
                'extension',
                '-e',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Only set up extensions with given key'
            );
    }

    /**
     * Sets up one or all extensions
     *
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Bootstrap::initializeBackendAuthentication();
        $this->eventDispatcher->dispatch(new PackagesMayHaveChangedEvent());

        $io = new SymfonyStyle($input, $output);
        $extensionKeys = $input->getOption('extension');
        $extensionKeysToSetUp = array_keys($this->packageManager->getActivePackages());
        if (!empty($extensionKeys)) {
            $extensionKeysToSetUp = array_filter(
                $extensionKeysToSetUp,
                static function ($extKey) use ($extensionKeys) {
                    return in_array($extKey, $extensionKeys, true);
                }
            );
        }
        $this->installUtility->updateDatabase();
        foreach ($extensionKeysToSetUp as $extensionKey) {
            $this->installUtility->processExtensionSetup($extensionKey);
        }
        if (empty($extensionKeysToSetUp)) {
            $io->error('Given extensions "' . implode(', ', $extensionKeys) . '" not found in the system.');
            return 1;
        }
        $io->success('Extension(s) "' . implode(', ', $extensionKeysToSetUp) . '" successfully set up.');

        return 0;
    }
}

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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Command for listing all extensions known to the system.
 *
 * If the command is called with the verbose option, also shows the description of the package.
 */
class ExtensionListCommand extends Command
{
    /**
     * @var PackageManager
     */
    private $packageManager;

    public function __construct(PackageManager $packageManager)
    {
        $this->packageManager = $packageManager;
        parent::__construct();
    }

    /**
     * Defines the allowed options for this command
     */
    protected function configure()
    {
        $this
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Also display currently inactive/uninstalled extensions.'
            )
            ->addOption(
                'inactive',
                'i',
                InputOption::VALUE_NONE,
                'Only show inactive/uninstalled extensions available for installation.'
            );
    }

    /**
     * Shows the list of all extensions
     *
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $onlyShowInactiveExtensions = $input->getOption('inactive');
        $showAlsoInactiveExtensions = $input->getOption('all');
        if ($onlyShowInactiveExtensions) {
            $packages = $this->packageManager->getAvailablePackages();
            $io->title('All inactive/currently uninstalled extensions');
        } elseif ($showAlsoInactiveExtensions) {
            $packages = $this->packageManager->getAvailablePackages();
            $io->title('All installed (= active) and available (= inactive/currently uninstalled) extensions');
        } else {
            $packages = $this->packageManager->getActivePackages();
            $io->title('All installed (= active) extensions');
        }

        $table = new Table($output);
        $table->setHeaders([
            'Extension Key',
            'Version',
            'Type',
            'Status',
        ]);
        $table->setColumnWidths([30, 10, 8, 6]);

        $formatter = $this->getHelper('formatter');
        foreach ($packages as $package) {
            $isActivePackage = $this->packageManager->isPackageActive($package->getPackageKey());
            if (!$package->getPackageMetaData()->isExtensionType()) {
                continue;
            }
            // Do not show the package if it is active but we only want to see inactive packages
            if ($onlyShowInactiveExtensions && $isActivePackage) {
                continue;
            }
            $type = $package->getPackageMetaData()->isFrameworkType() ? 'System' : 'Local';
            // Ensure that the inactive extensions are shown as well
            if ($onlyShowInactiveExtensions || ($showAlsoInactiveExtensions && !$isActivePackage)) {
                $status = '<comment>inactive</comment>';
            } else {
                $status = '<info>active</info>';
            }

            $table->addRow([$package->getPackageKey(), $package->getPackageMetaData()->getVersion(), $type, $status]);

            // Also show the title of the extension, if verbose option is set
            if ($output->isVerbose()) {
                $title = (string)$package->getPackageMetaData()->getTitle();
                $table->addRow([new TableCell('    ' . $formatter->truncate($title, 80) . "\n\n", ['colspan' => 4])]);
            }
        }
        $table->render();
        return 0;
    }
}

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

namespace TYPO3\CMS\Impexp\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Import;

/**
 * Command for importing T3D/XML data files
 */
class ImportCommand extends Command
{
    protected Import $import;

    public function __construct(Import $import)
    {
        $this->import = $import;
        parent::__construct();
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'The file path to import from (.t3d or .xml).'
            )
            ->addArgument(
                'pid',
                InputArgument::OPTIONAL,
                'The page to import to.',
                0
            )
            ->addOption(
                'update-records',
                null,
                InputOption::VALUE_NONE,
                'If set, existing records with the same UID will be updated instead of inserted.'
            )
            ->addOption(
                'ignore-pid',
                null,
                InputOption::VALUE_NONE,
                'If set, page IDs of updated records are not corrected (only works in conjunction with --update-records).'
            )
            ->addOption(
                'force-uid',
                null,
                InputOption::VALUE_NONE,
                'If set, UIDs from file will be forced.'
            )
            ->addOption(
                'import-mode',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                sprintf(
                    'Set the import mode of this specific record. ' . PHP_EOL .
                        'Pattern is "{table}:{record}={mode}". ' . PHP_EOL .
                        'Available modes for new records are "%1$s" and "%3$s" ' .
                        'and for existing records "%2$s", "%4$s", "%5$s" and "%3$s".' . PHP_EOL .
                        'Examples are "pages:987=%1$s", "tt_content:1=%2$s", etc.',
                    Import::IMPORT_MODE_FORCE_UID,
                    Import::IMPORT_MODE_AS_NEW,
                    Import::IMPORT_MODE_EXCLUDE,
                    Import::IMPORT_MODE_IGNORE_PID,
                    Import::IMPORT_MODE_RESPECT_PID
                )
            )
            ->addOption(
                'enable-log',
                null,
                InputOption::VALUE_NONE,
                'If set, all database actions are logged.'
            )
            // @deprecated since v11, will be removed in v12. Drop all options below and look for other fallbacks in the class.
            ->addOption(
                'updateRecords',
                null,
                InputOption::VALUE_NONE,
                'Deprecated. Use --update-records instead.'
            )
            ->addOption(
                'ignorePid',
                null,
                InputOption::VALUE_NONE,
                'Deprecated. Use --ignore-pid instead.'
            )
            ->addOption(
                'forceUid',
                null,
                InputOption::VALUE_NONE,
                'Deprecated. Use --force-uid instead.'
            )
            ->addOption(
                'importMode',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Deprecated. Use --import-mode instead.'
            )
            ->addOption(
                'enableLog',
                null,
                InputOption::VALUE_NONE,
                'Deprecated. Use --enable-log instead.'
            )
        ;
    }

    /**
     * Executes the command for importing a t3d/xml file into the TYPO3 system
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // @deprecated since v11, will be removed in v12. lowerCameCased options. Also look for other fallbacks in the class.
        $deprecatedOptions = [
            '--updateRecords' => '--update-records',
            '--ignorePid' => '--ignore-pid',
            '--forceUid' => '--force-uid',
            '--importMode' => '--import-mode',
            '--enableLog' => '--enable-log',
        ];
        foreach ($deprecatedOptions as $deprecatedName => $actualName) {
            if ($input->hasParameterOption($deprecatedName, true)) {
                $this->triggerCommandOptionDeprecation($deprecatedName, $actualName);
            }
        }

        // Ensure the _cli_ user is authenticated
        Bootstrap::initializeBackendAuthentication();

        $io = new SymfonyStyle($input, $output);

        try {
            $this->import->setPid((int)$input->getArgument('pid'));
            $this->import->setUpdate(
                $input->getOption('updateRecords') ||
                $input->getOption('update-records')
            );
            $this->import->setGlobalIgnorePid(
                $input->getOption('ignorePid') ||
                $input->getOption('ignore-pid')
            );
            $this->import->setForceAllUids(
                $input->getOption('forceUid') ||
                $input->getOption('force-uid')
            );
            $this->import->setEnableLogging(
                $input->getOption('enableLog') ||
                $input->getOption('enable-log')
            );
            $this->import->setImportMode(
                array_merge(
                    $this->parseAssociativeArray($input, 'importMode', '='),
                    $this->parseAssociativeArray($input, 'import-mode', '='),
                )
            );
            $this->import->loadFile((string)$input->getArgument('file'), true);
            $this->import->checkImportPrerequisites();
            $this->import->importData();
            $io->success('Importing ' . $input->getArgument('file') . ' to page ' . $input->getArgument('pid') . ' succeeded.');
            return 0;
        } catch (\Exception $e) {
            // Since impexp triggers core and DataHandler with potential hooks, and exception could come from "everywhere".
            $io->error('Importing ' . $input->getArgument('file') . ' to page ' . $input->getArgument('pid') . ' failed.');
            if ($io->isVerbose()) {
                $io->writeln($e->getMessage());
                $io->writeln($this->import->getErrorLog());
            }
            return 1;
        }
    }

    /**
     * @deprecated since v11, will be removed in v12. Drop all options below and look for other fallbacks in the class.
     */
    protected function triggerCommandOptionDeprecation(string $deprecatedName, string $actualName): void
    {
        trigger_error(
            sprintf(
                'Command option "impexp:import %s" is deprecated and will be removed in v12. Use "%s" instead.',
                $deprecatedName,
                $actualName
            ),
            E_USER_DEPRECATED
        );
    }

    /**
     * Parse a basic commandline option array into an associative array by splitting each entry into a key part and
     * a value part using a specific separator.
     *
     * @param InputInterface $input
     * @param string $optionName
     * @param string $separator
     * @return array
     */
    protected function parseAssociativeArray(InputInterface &$input, string $optionName, string $separator): array
    {
        $array = [];

        foreach ($input->getOption($optionName) as &$value) {
            $parts = GeneralUtility::trimExplode($separator, $value, true, 2);
            if (count($parts) === 2) {
                $array[$parts[0]] = $parts[1];
            } else {
                throw new \InvalidArgumentException(
                    sprintf('Command line option "%s" has invalid entry "%s".', $optionName, $value),
                    1610464090
                );
            }
        }

        return $array;
    }
}

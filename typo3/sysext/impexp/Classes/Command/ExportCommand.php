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
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Impexp\Export;

/**
 * Command for exporting T3D/XML data files
 */
class ExportCommand extends Command
{
    protected Export $export;

    public function __construct(Export $export)
    {
        $this->export = $export;
        parent::__construct();
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                'filename',
                InputArgument::OPTIONAL,
                'The filename to export to (without file extension).'
            )
            ->addOption(
                'type',
                null,
                InputOption::VALUE_OPTIONAL,
                'The file type (xml, t3d, t3d_compressed).',
                $this->export->getExportFileType()
            )
            ->addOption(
                'pid',
                null,
                InputOption::VALUE_OPTIONAL,
                'The root page of the exported page tree.',
                $this->export->getPid()
            )
            ->addOption(
                'levels',
                null,
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    'The depth of the exported page tree. ' .
                    '"%d": "Records on this page", ' .
                    '"%d": "Expanded tree", ' .
                    '"0": "This page", ' .
                    '"1": "1 level down", ' .
                    '.. ' .
                    '"%d": "Infinite levels".',
                    Export::LEVELS_RECORDS_ON_THIS_PAGE,
                    Export::LEVELS_EXPANDED_TREE,
                    Export::LEVELS_INFINITE
                ),
                $this->export->getLevels()
            )
            ->addOption(
                'table',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Include all records of this table. Examples: "_ALL", "tt_content", "sys_file_reference", etc.'
            )
            ->addOption(
                'record',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Include this specific record. Pattern is "{table}:{record}". Examples: "tt_content:12", etc.'
            )
            ->addOption(
                'list',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Include the records of this table and this page. Pattern is "{table}:{pid}". Examples: "sys_language:0", etc.'
            )
            ->addOption(
                'include-related',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Include record relations to this table, including the related record. Examples: "_ALL", "sys_category", etc.'
            )
            ->addOption(
                'include-static',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Include record relations to this table, excluding the related record. Examples: "_ALL", "sys_language", etc.'
            )
            ->addOption(
                'exclude',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Exclude this specific record. Pattern is "{table}:{record}". Examples: "fe_users:3", etc.'
            )
            ->addOption(
                'exclude-disabled-records',
                null,
                InputOption::VALUE_NONE,
                'Exclude records which are handled as disabled by their TCA configuration, e.g. by fields "disabled", "starttime" or "endtime".'
            )
            ->addOption(
                'exclude-html-css',
                null,
                InputOption::VALUE_NONE,
                'Exclude referenced HTML and CSS files.'
            )
            ->addOption(
                'title',
                null,
                InputOption::VALUE_OPTIONAL,
                'The meta title of the export.'
            )
            ->addOption(
                'description',
                null,
                InputOption::VALUE_OPTIONAL,
                'The meta description of the export.'
            )
            ->addOption(
                'notes',
                null,
                InputOption::VALUE_OPTIONAL,
                'The meta notes of the export.'
            )
            ->addOption(
                'dependency',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'This TYPO3 extension is required for the exported records. Examples: "news", "powermail", etc.'
            )
            ->addOption(
                'save-files-outside-export-file',
                null,
                InputOption::VALUE_NONE,
                'Save files into separate folder instead of including them into the common export file. Folder name pattern is "{filename}.files".'
            )
            // @deprecated since v11, will be removed in v12. Drop all options below and look for other fallbacks in the class.
            ->addOption(
                'includeRelated',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Deprecated. Use --include-related instead.'
            )
            ->addOption(
                'includeStatic',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Deprecated. Use --include-static instead.'
            )
            ->addOption(
                'excludeDisabledRecords',
                null,
                InputOption::VALUE_NONE,
                'Deprecated. Use --exclude-disabled-records instead.'
            )
            ->addOption(
                'excludeHtmlCss',
                null,
                InputOption::VALUE_NONE,
                'Deprecated. Use --exclude-html-css instead.'
            )
            ->addOption(
                'saveFilesOutsideExportFile',
                null,
                InputOption::VALUE_NONE,
                'Deprecated. Use --save-files-outside-export-file instead.'
            )
        ;
    }

    /**
     * Executes the command for exporting a t3d/xml file from the TYPO3 system
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // @deprecated since v11, will be removed in v12. lowerCameCased options. Also look for other fallbacks in the class.
        $deprecatedOptions = [
            '--includeRelated' => '--include-related',
            '--includeStatic' => '--include-static',
            '--excludeDisabledRecords' => '--exclude-disabled-records',
            '--excludeHtmlCss' => '--exclude-html-css',
            '--saveFilesOutsideExportFile' => '--save-files-outside-export-file',
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
            $this->export->setExportFileName(PathUtility::basename((string)$input->getArgument('filename')));
            $this->export->setExportFileType((string)$input->getOption('type'));
            $this->export->setPid((int)$input->getOption('pid'));
            $this->export->setLevels((int)$input->getOption('levels'));
            $this->export->setTables($input->getOption('table'));
            $this->export->setRecord($input->getOption('record'));
            $this->export->setList($input->getOption('list'));
            $this->export->setRelOnlyTables(
                array_merge(
                    $input->getOption('includeRelated'),
                    $input->getOption('include-related')
                )
            );
            $this->export->setRelStaticTables(
                array_merge(
                    $input->getOption('includeStatic'),
                    $input->getOption('include-static')
                )
            );
            $this->export->setExcludeMap($input->getOption('exclude'));
            $this->export->setExcludeDisabledRecords(
                $input->getOption('excludeDisabledRecords') ||
                $input->getOption('exclude-disabled-records')
            );
            $this->export->setIncludeExtFileResources(!(
                $input->getOption('excludeHtmlCss') ||
                $input->getOption('exclude-html-css')
            ));
            $this->export->setTitle((string)$input->getOption('title'));
            $this->export->setDescription((string)$input->getOption('description'));
            $this->export->setNotes((string)$input->getOption('notes'));
            $this->export->setExtensionDependencies($input->getOption('dependency'));
            $this->export->setSaveFilesOutsideExportFile(
                $input->getOption('saveFilesOutsideExportFile') ||
                $input->getOption('save-files-outside-export-file')
            );
            $this->export->process();
            $saveFile = $this->export->saveToFile();
            $io->success('Exporting to ' . $saveFile->getPublicUrl() . ' succeeded.');
            return 0;
        } catch (\Exception $e) {
            $saveFolder = $this->export->getOrCreateDefaultImportExportFolder();
            $io->error('Exporting to ' . $saveFolder->getPublicUrl() . ' failed.');
            if ($io->isVerbose()) {
                $io->writeln($e->getMessage());
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
                'Command option "impexp:export %s" is deprecated and will be removed in v12. Use "%s" instead.',
                $deprecatedName,
                $actualName
            ),
            E_USER_DEPRECATED
        );
    }
}

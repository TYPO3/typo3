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

namespace TYPO3\CMS\Backend\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Backend\Module\ModuleFactory;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleRegistry;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Command for showing all backend modules and their associated labels
 * @internal only for development purposes
 */
#[AsCommand('debug:backend:modules', 'Debugging: Show a list of the backend module tree (only for development purpose)')]
#[AsNonSchedulableCommand]
class DebugBackendModulesCommand extends Command
{
    private LanguageService $languageService;

    public function __construct(
        private readonly ContainerInterface $failsafeContainer,
        private readonly BootService $bootService,
        private readonly ModuleFactory $moduleFactory,
        private readonly LanguageServiceFactory $languageServiceFactory,
    ) {
        $this->languageService = $GLOBALS['LANG'] = $this->languageServiceFactory->create('en');
        // Note: We cannot directly use autowire of 'backend.modules' because that
        // would only give us the final constructed registry, without access to data
        // like "packageName" and "labels".
        // @todo We should expose this data in our registry.
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'csv-export',
                'x',
                InputOption::VALUE_NONE,
                'Dump data as CSV (instead of CLI table)'
            )
            ->addOption(
                'core-only',
                'c',
                InputOption::VALUE_NONE,
                'Only show core extensions'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $degraded = false;
        try {
            $container = $this->bootService->getContainer();
        } catch (\Throwable $e) {
            $container = $this->failsafeContainer;
            $degraded = true;
        }

        $coreOnly = $input->getOption('core-only');

        $title = 'Backend Modules';
        if ($coreOnly) {
            $title .= ' - Core';
        }
        if ($degraded) {
            $title .= ' (failsafe)';
        }

        // We need this low-level access because 'packageName' and 'labels' cannot be retrieved by the Module Registry API (yet)
        $modulesFromPackages = $container->get('backend.modules')->getArrayCopy();
        $modulesFromPackages = $this->moduleFactory->adaptAliasMappingFromModuleConfiguration($modulesFromPackages);

        $initializedModulesFromPackages = [];
        foreach ($modulesFromPackages as $identifier => $configuration) {
            if (!$coreOnly || str_starts_with($configuration['packageName'], 'typo3/cms-')) {
                $initializedModulesFromPackages[$identifier] = $this->moduleFactory->createModule($identifier, $configuration);
            }
        }

        $registry = GeneralUtility::makeInstance(ModuleRegistry::class, $initializedModulesFromPackages);
        $modules = $registry->getModules();

        $linearTree = [];
        $headers = [
            'Pkg',
            'Main level',
            'Second level',
            'Third level',
            'Position',
            'Labels',
            'Path',
        ];
        $this->walkTree($modules, $linearTree, $modulesFromPackages);

        if ($input->getOption('csv-export')) {
            $out = fopen('php://output', 'w');
            $separator = ';';
            $enclosure = '"';
            $escape = '\\';
            $eol = PHP_EOL;

            fputcsv($out, $headers, $separator, $enclosure, $escape, $eol);
            foreach ($linearTree as $data) {
                if ($data instanceof TableSeparator) {
                    $blankOutput = [];
                    foreach ($headers as $ignored) {
                        $blankOutput[] = '';
                    }
                    fputcsv($out, $blankOutput, $separator, $enclosure, $escape, $eol);
                } else {
                    fputcsv($out, $data, $separator, $enclosure, $escape, $eol);
                }
            }
            fclose($out);
        } else {
            $io->title($title);
            $table = new Table($output);
            $table->setHeaders($headers);

            foreach ($linearTree as $data) {
                $table->addRow($data);
            }
            $table->render();
        }

        return Command::SUCCESS;
    }

    /**
     * @param ModuleInterface[] $modules
     */
    private function walkTree(array $modules, array &$linearTree, array $modulesFromPackages, int $level = 1, array $parentStack = []): void
    {
        foreach ($modules as $module) {
            // Main menus have no "parent". We only iterate these elements on the first level.
            if ($level === 1 && $module->getParentIdentifier() !== '') {
                continue;
            }

            $outputStack = $parentStack;
            $outputStack[] = $module->getIdentifier();

            $linearTree[] = [
                $modulesFromPackages[$module->getIdentifier()]['packageName'],

                $outputStack[0] ?? '',
                $outputStack[1] ?? '',
                $outputStack[2] ?? '',

                ($module->getPosition() !== [] ? json_encode($module->getPosition()) : ''),
                $this->languageService->sL($module->getTitle()) . ' [' . $this->parseLabels($modulesFromPackages[$module->getIdentifier()]['labels']) . ']',
                $module->getPath(),
            ];

            // Next level
            if ($module->hasSubModules()) {
                $this->walkTree($module->getSubModules(), $linearTree, $modulesFromPackages, $level + 1, [...$parentStack, $module->getIdentifier()]);
            }

            if ($level === 1) {
                $linearTree[] = new TableSeparator();
            }
        }

        if ($level === 1) {
            // Remove last separator
            array_pop($linearTree);
        }
    }

    private function parseLabels(array|string $labels): string
    {
        if (is_string($labels)) {
            return $labels;
        }

        $out = "\n";
        $out .= '  title: ' . ($labels['title'] ?? '-') . "\n";
        $out .= '  shortDescription: ' . ($labels['shortDescription'] ?? '-') . "\n";
        $out .= '  description: ' . ($labels['description'] ?? '-') . "\n";
        return $out;
    }
}

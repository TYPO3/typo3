<?php

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

namespace TYPO3\CMS\Fluid\Command;

use Composer\Autoload\ClassLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Schema\SchemaGenerator;
use TYPO3Fluid\Fluid\Schema\ViewHelperFinder;

/**
 * Generate schema files from fluid view helpers
 *
 * @internal: Specific command implementation, not API itself.
 */
#[AsCommand('fluid:schema:generate', 'Generate XSD schema files for all available ViewHelpers in var/transient/')]
final class SchemaCommand extends Command
{
    public function __construct(private readonly ClassLoader $classLoader)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $viewHelperFinder = new ViewHelperFinder();
        $allViewHelpers = $viewHelperFinder->findViewHelpersInComposerProject($this->classLoader);
        $errors = $viewHelperFinder->getLastErrors();

        // Group ViewHelpers by xml namespace to split them into xsd files later
        $xsdFiles = $groupedByNamespace = [];
        foreach ($allViewHelpers as $viewHelper) {
            $xsdFiles[$viewHelper->xmlNamespace] ??= [];
            $xsdFiles[$viewHelper->xmlNamespace][] = $viewHelper;

            $groupedByNamespace[$viewHelper->namespace] ??= [];
            $groupedByNamespace[$viewHelper->namespace][] = $viewHelper;
        }

        // Special handling of TYPO3's global ViewHelper namespaces which allows
        // merging of several PHP namespaces into one Fluid namespace. If a configured
        // global Fluid namespace has more than one PHP namespace, ViewHelpers can be
        // overridden by subsequent namespaces if they are defined with the same name.
        // For example, both Fluid Standalone and EXT:fluid define <f:render>,
        // but EXT:fluid is the higher item in the namespace array, so it will be part
        // of the xsd file, while the <f:render> from Fluid Standalone will be omitted.
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces'] ?? [] as $mergedNamespace) {
            // If a global namespace has only one item, it is already covered by the
            // default handling above
            if (count($mergedNamespace) < 2) {
                continue;
            }

            // Last PHP namespace defines the xml namespace
            $targetNamespace = end($mergedNamespace);
            if (!isset($groupedByNamespace[$targetNamespace])) {
                continue;
            }
            $xmlNamespace = $groupedByNamespace[$targetNamespace][0]->xmlNamespace;

            // Combine PHP namespaces into one XML namespace
            // Subsequent ViewHelpers with the same name can override
            $xsdFiles[$xmlNamespace] = [];
            foreach ($mergedNamespace as $namespace) {
                foreach ($groupedByNamespace[$namespace] ?? [] as $viewHelper) {
                    $xsdFiles[$xmlNamespace][$viewHelper->name] = $viewHelper;
                }
            }
            $xsdFiles[$xmlNamespace] = array_values($xsdFiles[$xmlNamespace]);
        }

        // Create transient folder if necessary
        $temporaryPath = Environment::getVarPath() . '/transient/';
        if (!is_dir($temporaryPath)) {
            GeneralUtility::mkdir_deep($temporaryPath);
        }

        // Remove existing schema files in transient folder
        $existingSchemaFiles = GeneralUtility::getFilesInDir($temporaryPath, 'xsd');
        foreach ($existingSchemaFiles as $file) {
            if (str_starts_with($file, 'schema_')) {
                unlink($temporaryPath . $file);
            }
        }

        // Write schema files to transient folder
        foreach ($xsdFiles as $xmlNamespace => $viewHelpers) {
            $schema = (new SchemaGenerator())->generate($xmlNamespace, $viewHelpers);
            $fileName = str_replace('http://typo3.org/ns/', '', $xmlNamespace);
            $fileName = str_replace('/', '_', $fileName);
            $fileName = preg_replace('#[^0-9a-zA-Z_]#', '', $fileName);
            GeneralUtility::writeFile($temporaryPath . 'schema_' . $fileName . '.xsd', $schema->asXml());
        }

        if ($errors !== []) {
            $output->writeln('Reported errors:');
            $table = new Table($output);
            $table->setHeaders(['Class', 'Message']);
            foreach ($errors as $error) {
                $table->addRow([
                    $error->getFile(),
                    $error->getMessage(),
                ]);
            }
            $table->render();
            $output->writeln('Successfully generated all schemas except those listed with errors.');
        }

        return Command::SUCCESS;
    }
}

#!/usr/bin/env php
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

use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Util\XliffUtils;

require __DIR__ . '/../../vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    die('Script must be called from command line.' . chr(10));
}

final readonly class CheckIntegrityXliff
{
    private const expectedXliffDeprecations = [
        'mlang_labels_tablabel',
        'mlang_labels_tabdescr',
        'mlang_tabs_tab',
    ];
    private const xliffModuleRegularExpression = '@Language/(module\.xlf|Modules/.+\.xlf)$@i';
    private const xliffModuleRequiredKeys = [
        'title',
        'description',
        'short_description',
    ];
    private const XliffDeprecationKey = 'x-unused-since';
    public function execute(array $argv = []): int
    {
        $isVerbose = in_array('-v', $argv, true) || in_array('--verbose', $argv, true);

        $filesToProcess = $this->findXliff();
        $output = new ConsoleOutput();
        $output->setFormatter(new OutputFormatter(true));

        $testResults = [];
        $errors = [];

        /** @var \SplFileInfo $labelFile */
        foreach ($filesToProcess as $labelFile) {
            $fullFilePath = $labelFile->getRealPath();
            $result = $this->checkValidLabels($fullFilePath);
            if (isset($result['error'])) {
                $errors['EXT:' . $result['extensionKey'] . ':' . $result['shortLabelFile']] = $result['error'];
            }
            $testResults[] = $result;
        }

        if ($testResults === []) {
            return 1;
        }

        // Only show full table output if verbose is on
        if ($isVerbose) {
            $table = new Table($output);
            $table->setHeaders(['EXT', 'File', 'Status', 'Errorcode']);
            foreach ($testResults as $result) {
                $table->addRow([
                    $result['extensionKey'],
                    $result['shortLabelFile'],
                    (!isset($result['error']) ? "\xF0\x9F\x91\x8C" : "\xF0\x9F\x92\x80"),
                    $result['errorcode'] ?? '',
                ]);
            }
            $table->setFooterTitle(count($testResults) . ' files, ' . count($errors) . ' Errors');
            $table->render();
        } else {
            // Non-verbose: just show a summary line
            if ($errors !== []) {
                $output->writeln('<error>' . count($errors) . ' error(s) found in ' . count($testResults) . ' files.</error>');
            }
        }

        if ($errors === []) {
            return 0;
        }

        // Only show detailed error table if verbose
        if ($isVerbose) {
            $output->writeln('');
            $table = new Table($output);
            $table->setHeaders(['File', 'Error']);
            foreach ($errors as $file => $errorMessage) {
                $table->addRow([$file, $errorMessage]);
                $table->addRow([new TableSeparator(), new TableSeparator()]);
            }
            $table->setColumnMaxWidth(0, 40);
            $table->setColumnMaxWidth(1, 80);
            $table->render();
        } else {
            // Compact error summary
            foreach ($errors as $file => $message) {
                $output->writeln("<error>$file:</error> $message");
            }
        }

        return 1;
    }

    private function findXliff(): Finder
    {
        $finder = new Finder();
        return $finder
            ->files()
            ->in(__DIR__ . '/../../typo3/sysext/*/Resources/Private/Language/')
            ->name('*.xlf');
    }

    private function checkValidLabels(string $labelFile): array
    {
        $extensionKey = 'N/A';
        $shortLabelFile = basename($labelFile);
        if (preg_match('@sysext/(.+)/Resources/Private/Language/(.+)$@imsU', $labelFile, $matches)) {
            $extensionKey = $matches[1];
            $shortLabelFile = $matches[2];
        }

        $result = [
            'shortLabelFile' => $shortLabelFile,
            'extensionKey' => $extensionKey,
        ];

        $xml = simplexml_load_file($labelFile);
        if ($xml === false) {
            $result['error'] = 'XML not parsable';
            $result['errorcode'] = 'XML';
            return $result;
        }

        $attributes = (array)$xml->attributes();
        $version = $attributes['@attributes']['version'] ?? '';
        $supportedVersions = ['1.2', '2.0'];
        if (!in_array($version, $supportedVersions, true)) {
            $result['error'] = 'Incompatible version: ' . $version . ' (expected: ' . implode(', ', $supportedVersions) . ')';
            $result['errorcode'] = 'XLF version';
            return $result;
        }

        $dom = XmlUtils::loadFile($labelFile, null);
        $errors = XliffUtils::validateSchema($dom);
        if ($errors) {
            $result['error'] = sprintf('File %s has errors: ', $labelFile);
            foreach ($errors as $error) {
                $result['error'] .= ($error['message'] ?? '') . ' ';
            }
            $result['errorcode'] = 'XLF linting';
            return $result;
        }

        $fileAttributes = (array)$xml->file->attributes();
        if ($version === '1.2') {
            $namespaces = $xml->getNamespaces(true);
            if (isset($namespaces[''])) {
                // Normalize empty namespace to "xml"
                $namespaces['xml'] = $namespaces[''];
                unset($namespaces['']);
            }
            $ns = 'urn:oasis:names:tc:xliff:document:1.2';
            if ($namespaces !== ['xml' => $ns]) {
                $result['error'] = 'Invalid XLIFF namespace: ' . json_encode($namespaces) . ' (expected: ' . $ns . ')';
                $result['errorcode'] = 'XML-NS';
                return $result;
            }
            $xml->registerXPathNamespace('x', $ns);

            $sourceLanguage = $fileAttributes['@attributes']['source-language'] ?? '';
            $datatype = $fileAttributes['@attributes']['datatype'] ?? '';
            $original = $fileAttributes['@attributes']['original'] ?? '';
            $date = $fileAttributes['@attributes']['date'] ?? '';

            $isIso = ($extensionKey === 'core' && str_starts_with($shortLabelFile, 'Iso/'));

            if ($sourceLanguage !== 'en') {
                $result['error'] = 'Invalid source-language: ' . $sourceLanguage;
                $result['errorcode'] = 'file.source-language';
                return $result;
            }

            if ($datatype !== 'plaintext') {
                $result['error'] = 'Invalid datatype: ' . $datatype;
                $result['errorcode'] = 'file.datatype';
                return $result;
            }

            $expectedOriginals = [
                'EXT:' . $extensionKey . '/Resources/Private/Language/' . $shortLabelFile,
                'messages', // @todo is this right?
            ];

            if ($isIso) {
                $expectedOriginals[] = 'EXT:core/Resources/Private/Language/countries.xlf';
            }
            if (!in_array($original, $expectedOriginals, true)) {
                $result['error'] = 'Invalid original: ' . $original . ' (expected: ' . implode(', ', $expectedOriginals) . ')';
                $result['errorcode'] = 'file.original';
                return $result;
            }

            if (!$isIso && (strtotime($date) === false || strtotime($date) === 0)) {
                $result['error'] = 'Invalid date: ' . $date;
                $result['errorcode'] = 'file.date';
                return $result;
            }

            // verify these are deprecated:
            $transUnits = $xml->xpath('/x:xliff/x:file/x:body/x:trans-unit');
            $seenKeys = [];
            foreach ($transUnits as $unit) {
                $unitAttributes = (array)$unit;
                $unitId = $unitAttributes['@attributes']['id'] ?? '';
                if ($unitId === '') {
                    $result['error'] = 'TransUnit without ID specified.';
                    $result['errorcode'] = 'trans-unit';
                    return $result;
                }
                if (isset($seenKeys[$unitId])) {
                    $result['error'] = 'Duplicate trans-unit id: ' . $unitId;
                    $result['errorcode'] = 'trans-unit.duplicate-id';
                    return $result;
                }

                if (in_array($unitId, self::expectedXliffDeprecations, true)
                    && ($unitAttributes['@attributes'][self::XliffDeprecationKey] ?? '') === ''
                ) {
                    $result['error'] = 'TransUnit ' . $unitId . ' missing ' . self::XliffDeprecationKey . ' attribute.';
                    $result['errorcode'] = 'trans-unit.' . self::XliffDeprecationKey;
                    return $result;
                }
                $seenKeys[$unitId] = $unitId;
            }

            if (preg_match(self::xliffModuleRegularExpression, $labelFile)) {
                // Hit on any "backend module file".
                foreach (self::xliffModuleRequiredKeys as $requiredKey) {
                    if (!isset($seenKeys[$requiredKey])) {
                        $result['error'] = 'Backend module missing label ' . $requiredKey . '.';
                        $result['errorcode'] = 'missing ' . $requiredKey;
                        return $result;
                    }
                }
            }
        } else {
            $fileId = $fileAttributes['@attributes']['id'] ?? '';
            if ($fileId === '') {
                $result['error'] = 'Missing file.id';
                $result['errorcode'] = 'file.id';
                return $result;
            }

            $ns = 'urn:oasis:names:tc:xliff:document:2.0';
            $xml->registerXPathNamespace('x', $ns);

            // In XLIFF 2.0, translatable content is in <unit id="...">
            $units = $xml->xpath('/x:xliff/x:file/x:unit');
            $seenUnitIds = [];

            foreach ($units as $unit) {
                $attrs = $unit->attributes();
                $unitId = isset($attrs['id']) ? (string)$attrs['id'] : '';

                if ($unitId === '') {
                    $result['error'] = 'Unit without ID specified.';
                    $result['errorcode'] = 'unit';
                    return $result;
                }

                if (isset($seenUnitIds[$unitId])) {
                    $result['error'] = 'Duplicate unit id: ' . $unitId;
                    $result['errorcode'] = 'unit.duplicate-id';
                    return $result;
                }

                $seenUnitIds[$unitId] = true;
            }

            // XLIFF 2.0 has no deprecation syntax check yet.
        }

        return $result;
    }
}

exit((new CheckIntegrityXliff())->execute($argv));

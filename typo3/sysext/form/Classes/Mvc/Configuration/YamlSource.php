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

/*
 * Inspired by and partially taken from the Neos.Form package (www.neos.io)
 */

namespace TYPO3\CMS\Form\Mvc\Configuration;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\FileWriteException;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\ParseErrorException;
use TYPO3\CMS\Form\Slot\FilePersistenceSlot;

/**
 * Configuration source based on YAML files
 *
 * Scope: frontend / backend
 * @internal
 */
readonly class YamlSource
{
    public function __construct(
        private FilePersistenceSlot $filePersistenceSlot,
        private YamlFileLoader $yamlFileLoader,
    ) {}

    /**
     * Loads the specified configuration files and returns its merged content
     * as an array.
     *
     * @internal
     */
    public function load(array $filesToLoad): array
    {
        $configuration = [];
        foreach ($filesToLoad as $fileToLoad) {
            if ($fileToLoad instanceof File) {
                $loadedConfiguration = $this->loadFromFile($fileToLoad);
            } else {
                $loadedConfiguration = $this->loadFromFilePath($fileToLoad);
                if (isset($loadedConfiguration['TYPO3']['CMS']['Form'])) {
                    $namespacedConfiguration = $loadedConfiguration['TYPO3']['CMS']['Form'];
                    unset($loadedConfiguration['TYPO3']);
                    $loadedConfiguration = array_replace_recursive($namespacedConfiguration, $loadedConfiguration);
                }
            }
            $configuration = array_replace_recursive($configuration, $loadedConfiguration);
        }
        return ArrayUtility::convertBooleanStringsToBooleanRecursive($configuration);
    }

    /**
     * Save the specified configuration array to the given file in YAML format.
     *
     * @param File|string $fileToSave The file to write to.
     * @param array $configuration The configuration to save
     * @throws FileWriteException if the file could not be written
     * @internal
     */
    public function save(File|string $fileToSave, array $configuration): void
    {
        try {
            $header = $this->getHeaderFromFile($fileToSave);
        } catch (InsufficientFileAccessPermissionsException $e) {
            throw new FileWriteException($e->getMessage(), 1512584488, $e);
        }

        $yaml = Yaml::dump($configuration, 99, 2);

        if ($fileToSave instanceof File) {
            try {
                $this->filePersistenceSlot->allowInvocation(
                    FilePersistenceSlot::COMMAND_FILE_SET_CONTENTS,
                    $this->buildCombinedIdentifier(
                        $fileToSave->getParentFolder(),
                        $fileToSave->getName()
                    ),
                    $this->filePersistenceSlot->getContentSignature(
                        $header . LF . $yaml
                    )
                );
                $fileToSave->setContents($header . LF . $yaml);
            } catch (InsufficientFileAccessPermissionsException $e) {
                throw new FileWriteException($e->getMessage(), 1512582753, $e);
            }
        } else {
            $byteCount = @file_put_contents($fileToSave, $header . LF . $yaml);
            if ($byteCount === false) {
                $error = error_get_last();
                $errorMessage = $error['message'] ?? 'Check that the file exists and can be written.';
                throw new FileWriteException($errorMessage, 1512582929);
            }
        }
    }

    /**
     * Load YAML configuration from a local file path
     *
     * @throws ParseErrorException
     */
    protected function loadFromFilePath(string $filePath): array
    {
        try {
            $loadedConfiguration = $this->yamlFileLoader->load($filePath);
        } catch (\RuntimeException $e) {
            throw new ParseErrorException(
                sprintf('An error occurred while parsing file "%s": %s', $filePath, $e->getMessage()),
                1480195405,
                $e
            );
        }
        return $loadedConfiguration;
    }

    /**
     * Load YAML configuration from a FAL file
     *
     * @throws ParseErrorException
     */
    protected function loadFromFile(File $file): array
    {
        $fileIdentifier = $file->getIdentifier();
        $rawYamlContent = $file->getContents();
        try {
            $loadedConfiguration = Yaml::parse($rawYamlContent);
        } catch (ParseException $e) {
            throw new ParseErrorException(
                sprintf('An error occurred while parsing file "%s": %s', $fileIdentifier, $e->getMessage()),
                1574422322,
                $e
            );
        }
        return $loadedConfiguration;
    }

    /**
     * Read the header part from the given file. That means, every line
     * until the first non comment line is found.
     *
     * @return string The header of the given YAML file
     */
    protected function getHeaderFromFile(File|string $file): string
    {
        $header = '';
        if ($file instanceof File) {
            $fileLines = explode(LF, $file->getContents());
        } elseif (is_file($file)) {
            $fileLines = file($file);
        } else {
            return '';
        }
        foreach ($fileLines as $line) {
            if (str_starts_with($line, '#')) {
                $header .= $line;
            } else {
                break;
            }
        }
        return $header;
    }

    protected function buildCombinedIdentifier(FolderInterface $folder, string $fileName): string
    {
        return sprintf(
            '%d:%s%s',
            $folder->getStorage()->getUid(),
            $folder->getIdentifier(),
            $fileName
        );
    }
}

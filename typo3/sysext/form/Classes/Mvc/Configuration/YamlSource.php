<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Mvc\Configuration;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It originated from the Neos.Form package (www.neos.io)
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

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\FileWriteException;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\NoSuchFileException;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\ParseErrorException;
use TYPO3\CMS\Form\Slot\FilePersistenceSlot;

/**
 * Configuration source based on YAML files
 *
 * Scope: frontend / backend
 * @internal
 */
class YamlSource
{
    /**
     * @var FilePersistenceSlot
     */
    protected $filePersistenceSlot;

    /**
     * @param FilePersistenceSlot $filePersistenceSlot
     */
    public function injectFilePersistenceSlot(FilePersistenceSlot $filePersistenceSlot)
    {
        $this->filePersistenceSlot = $filePersistenceSlot;
    }

    /**
     * Loads the specified configuration files and returns its merged content
     * as an array.
     *
     * @param array $filesToLoad
     * @return array
     * @throws ParseErrorException
     * @throws NoSuchFileException
     * @internal
     */
    public function load(array $filesToLoad): array
    {
        $configuration = [];
        foreach ($filesToLoad as $fileToLoad) {
            if ($fileToLoad instanceof File) {
                $fileIdentifier = $fileToLoad->getIdentifier();
                $rawYamlContent = $fileToLoad->getContents();
                if ($rawYamlContent === false) {
                    throw new NoSuchFileException(
                        'The file "' . $fileIdentifier . '" does not exist.',
                        1498802253
                    );
                }
            } else {
                $fileIdentifier = $fileToLoad;
                $fileToLoad = GeneralUtility::getFileAbsFileName($fileToLoad);
                if (is_file($fileToLoad)) {
                    $rawYamlContent = file_get_contents($fileToLoad);
                } else {
                    throw new NoSuchFileException(
                        'The file "' . $fileToLoad . '" does not exist.',
                        1471473378
                    );
                }
            }

            try {
                $loadedConfiguration = Yaml::parse($rawYamlContent);

                if (is_array($loadedConfiguration)) {
                    $configuration = array_replace_recursive($configuration, $loadedConfiguration);
                }
            } catch (ParseException $exception) {
                throw new ParseErrorException(
                    'An error occurred while parsing file "' . $fileIdentifier . '": ' . $exception->getMessage(),
                    1480195405
                );
            }
        }

        $configuration = ArrayUtility::convertBooleanStringsToBooleanRecursive($configuration);
        return $configuration;
    }

    /**
     * Save the specified configuration array to the given file in YAML format.
     *
     * @param File|string $fileToSave The file to write to.
     * @param array $configuration The configuration to save
     * @return mixed
     * @throws FileWriteException if the file could not be written
     * @internal
     */
    public function save($fileToSave, array $configuration)
    {
        try {
            $header = $this->getHeaderFromFile($fileToSave);
        } catch (InsufficientFileAccessPermissionsException  $e) {
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
                throw new FileWriteException($error['message'], 1512582929);
            }
        }

        return $return;
    }

    /**
     * Read the header part from the given file. That means, every line
     * until the first non comment line is found.
     *
     * @param File|string $file
     * @return string The header of the given YAML file
     */
    protected function getHeaderFromFile($file): string
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
            if (preg_match('/^#/', $line)) {
                $header .= $line;
            } else {
                break;
            }
        }
        return $header;
    }

    /**
     * @param FolderInterface $folder
     * @param string $fileName
     * @return string
     */
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

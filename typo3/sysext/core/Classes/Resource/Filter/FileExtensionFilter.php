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

namespace TYPO3\CMS\Core\Resource\Filter;

use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Utility methods for filtering filenames
 */
class FileExtensionFilter
{
    /**
     * Allowed file extensions. If NULL, all extensions are allowed.
     *
     * @var string[]|null
     */
    protected ?array $allowedFileExtensions = null;

    /**
     * Disallowed file extensions. If NULL, no extension is disallowed (i.e. all are allowed).
     *
     * @var string[]|null
     */
    protected ?array $disallowedFileExtensions = null;

    public function filter(
        array $references,
        string $allowedFileExtensions,
        string $disallowedFileExtensions,
        DataHandler|DatabaseRecordList $dataHandler
    ): array {
        if ($allowedFileExtensions !== '') {
            $this->setAllowedFileExtensions($allowedFileExtensions);
        }
        if ($disallowedFileExtensions !== '') {
            $this->setDisallowedFileExtensions($disallowedFileExtensions);
        }

        $cleanReferences = [];
        foreach ($references as $reference) {
            if (empty($reference)) {
                continue;
            }
            $parts = GeneralUtility::revExplode('_', (string)$reference, 2);
            $fileReferenceUid = $parts[count($parts) - 1];
            try {
                $fileReference = GeneralUtility::makeInstance(ResourceFactory::class)->getFileReferenceObject($fileReferenceUid);
                $file = $fileReference->getOriginalFile();
                if ($this->isAllowed($file->getExtension())) {
                    $cleanReferences[] = $reference;
                } else {
                    // Remove the erroneously created reference record again
                    $dataHandler->deleteAction('sys_file_reference', $fileReferenceUid);
                }
            } catch (ResourceDoesNotExistException $e) {
                // do nothing
            }
        }
        return $cleanReferences;
    }

    /**
     * Entry method for use as DataHandler "inline" field filter
     *
     * @deprecated Will be removed in TYPO3 v13. Use filterFileReferences() directly instead.
     */
    public function filterInlineChildren(array $parameters, DataHandler|DatabaseRecordList $dataHandler): array
    {
        trigger_error(
            'FileExtensionFilter->filterInlineChildren() will be removed in TYPO3 v13.0. Use FileExtensionFilter->filter() instead.',
            E_USER_DEPRECATED
        );

        $references = $parameters['values'] ?? [];
        if (!is_array($references)) {
            $references = [];
        }
        return $this->filter(
            $references,
            (string)($parameters['allowedFileExtensions'] ?? ''),
            (string)($parameters['disallowedFileExtensions'] ?? ''),
            $dataHandler
        );
    }

    /**
     * Entry method for use as filelist filter.
     *
     * We use -1 as the "don't include“ return value, for historic reasons,
     * as call_user_func() used to return FALSE if calling the method failed.
     *
     * @param string $itemName
     * @param string $itemIdentifier
     * @param string $parentIdentifier
     * @param array $additionalInformation Additional information about the inspected item
     * @param DriverInterface $driver
     * @return bool|int -1 if the file should not be included in a listing
     */
    public function filterFileList($itemName, $itemIdentifier, $parentIdentifier, array $additionalInformation, DriverInterface $driver)
    {
        $returnCode = true;
        // Early return in case no file filters are set at all
        if ($this->allowedFileExtensions === null && $this->disallowedFileExtensions === null) {
            return $returnCode;
        }
        // Check that this is a file and not a folder
        if ($driver->fileExists($itemIdentifier)) {
            try {
                $fileInfo = $driver->getFileInfoByIdentifier($itemIdentifier, ['extension']);
            } catch (\InvalidArgumentException $e) {
                $fileInfo = [];
            }
            if (!$this->isAllowed((string)($fileInfo['extension'] ?? ''))) {
                $returnCode = -1;
            }
        }
        return $returnCode;
    }

    /**
     * Checks whether a file is allowed according to the criteria defined in the class variables ($this->allowedFileExtensions etc.)
     *
     * @internal this is used internally for TYPO3 core only
     */
    public function isAllowed(string $fileExtension): bool
    {
        $fileExtension = strtolower($fileExtension);
        $result = true;
        // Check allowed file extensions
        if (!empty($this->allowedFileExtensions) && !in_array($fileExtension, $this->allowedFileExtensions, true)) {
            $result = false;
        }
        // Check disallowed file extensions
        if (!empty($this->disallowedFileExtensions) && in_array($fileExtension, $this->disallowedFileExtensions, true)) {
            $result = false;
        }
        return $result;
    }

    /**
     * Set allowed file extensions
     *
     * @param mixed $allowedFileExtensions Comma-separated list or array, of allowed file extensions
     */
    public function setAllowedFileExtensions(mixed $allowedFileExtensions): void
    {
        $this->allowedFileExtensions = $this->convertToLowercaseArray($allowedFileExtensions);
    }

    public function getAllowedFileExtensions(): ?array
    {
        return $this->allowedFileExtensions;
    }

    /**
     * Set disallowed file extensions
     *
     * @param mixed $disallowedFileExtensions Comma-separated list or array, of allowed file extensions
     */
    public function setDisallowedFileExtensions(mixed $disallowedFileExtensions): void
    {
        $this->disallowedFileExtensions = $this->convertToLowercaseArray($disallowedFileExtensions);
    }

    public function getDisallowedFileExtensions(): ?array
    {
        return $this->disallowedFileExtensions;
    }

    /**
     * Compared the current allowed and disallowed lists and returns
     * a filtered list either as allow or as disallow list. The "mode"
     * is indicated by the array key, which is either "allowedFileExtensions"
     * or "disallowedFileExtensions".
     */
    public function getFilteredFileExtensions(): array
    {
        if ($this->disallowedFileExtensions === null) {
            return ['allowedFileExtensions' => $this->allowedFileExtensions ?? ['*']];
        }

        if ($this->allowedFileExtensions === null) {
            return ['disallowedFileExtensions' => $this->disallowedFileExtensions];
        }

        return ['allowedFileExtensions' => array_filter($this->allowedFileExtensions, function ($fileExtension) {
            return !in_array($fileExtension, $this->disallowedFileExtensions, true);
        })];
    }

    /**
     * Converts mixed (string or array) input arguments into an array, NULL if empty.
     *
     * All array values will be converted to lower case.
     */
    protected function convertToLowercaseArray(mixed $inputArgument): ?array
    {
        $returnValue = null;
        if (is_array($inputArgument)) {
            $returnValue = $inputArgument;
        } elseif ((string)$inputArgument !== '') {
            $returnValue = GeneralUtility::trimExplode(',', $inputArgument);
        }

        if (is_array($returnValue)) {
            $returnValue = array_map('strtolower', $returnValue);
        }

        return $returnValue;
    }
}

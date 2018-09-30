<?php
namespace TYPO3\CMS\Core\Resource\Filter;

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

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Utility methods for filtering filenames
 */
class FileExtensionFilter
{
    /**
     * Allowed file extensions. If NULL, all extensions are allowed.
     *
     * @var array
     */
    protected $allowedFileExtensions;

    /**
     * Disallowed file extensions. If NULL, no extension is disallowed (i.e. all are allowed).
     *
     * @var array
     */
    protected $disallowedFileExtensions;

    /**
     * Entry method for use as DataHandler "inline" field filter
     *
     * @param array $parameters
     * @param DataHandler $dataHandler
     * @return array
     */
    public function filterInlineChildren(array $parameters, DataHandler $dataHandler)
    {
        $values = $parameters['values'];
        if ($parameters['allowedFileExtensions']) {
            $this->setAllowedFileExtensions($parameters['allowedFileExtensions']);
        }
        if ($parameters['disallowedFileExtensions']) {
            $this->setDisallowedFileExtensions($parameters['disallowedFileExtensions']);
        }
        $cleanValues = [];
        if (is_array($values)) {
            foreach ($values as $value) {
                if (empty($value)) {
                    continue;
                }
                $parts = GeneralUtility::revExplode('_', $value, 2);
                $fileReferenceUid = $parts[count($parts) - 1];
                try {
                    $fileReference = ResourceFactory::getInstance()->getFileReferenceObject($fileReferenceUid);
                    $file = $fileReference->getOriginalFile();
                    if ($this->isAllowed($file->getExtension())) {
                        $cleanValues[] = $value;
                    } else {
                        // Remove the erroneously created reference record again
                        $dataHandler->deleteAction('sys_file_reference', $fileReferenceUid);
                    }
                } catch (FileDoesNotExistException $e) {
                    // do nothing
                }
            }
        }
        return $cleanValues;
    }

    /**
     * Entry method for use as filelist filter.
     *
     * We have to use -1 as the „don't include“ return value, as call_user_func() will return FALSE
     * if calling the method failed and thus we can't use that as a return value.
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
            if (!isset($fileInfo['extension'])) {
                trigger_error('Guessing FAL file extensions will be removed in TYPO3 v10.0. The FAL (' . get_class($driver) . ') driver method getFileInfoByIdentifier() should return the file extension.', E_USER_DEPRECATED);
                $fileInfo['extension'] = PathUtility::pathinfo($itemIdentifier, PATHINFO_EXTENSION);
            }
            if (!$this->isAllowed($fileInfo['extension'])) {
                $returnCode = -1;
            }
        }
        return $returnCode;
    }

    /**
     * Checks whether a file is allowed according to the criteria defined in the class variables ($this->allowedFileExtensions etc.)
     *
     * @param string $fileExt
     * @return bool
     */
    protected function isAllowed($fileExt)
    {
        $fileExt = strtolower($fileExt);
        $result = true;
        // Check allowed file extensions
        if ($this->allowedFileExtensions !== null && !empty($this->allowedFileExtensions) && !in_array($fileExt, $this->allowedFileExtensions)) {
            $result = false;
        }
        // Check disallowed file extensions
        if ($this->disallowedFileExtensions !== null && !empty($this->disallowedFileExtensions) && in_array($fileExt, $this->disallowedFileExtensions)) {
            $result = false;
        }
        return $result;
    }

    /**
     * Set allowed file extensions
     *
     * @param mixed $allowedFileExtensions  Comma-separated list or array, of allowed file extensions
     */
    public function setAllowedFileExtensions($allowedFileExtensions)
    {
        $this->allowedFileExtensions = $this->convertToLowercaseArray($allowedFileExtensions);
    }

    /**
     * Set disallowed file extensions
     *
     * @param mixed $disallowedFileExtensions  Comma-separated list or array, of allowed file extensions
     */
    public function setDisallowedFileExtensions($disallowedFileExtensions)
    {
        $this->disallowedFileExtensions = $this->convertToLowercaseArray($disallowedFileExtensions);
    }

    /**
     * Converts mixed (string or array) input arguments into an array, NULL if empty.
     *
     * All array values will be converted to lower case.
     *
     * @param mixed $inputArgument Comma-separated list or array.
     * @return array
     */
    protected function convertToLowercaseArray($inputArgument)
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

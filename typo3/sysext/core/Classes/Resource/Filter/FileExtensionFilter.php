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
    protected $allowedFileExtensions = null;

    /**
     * Disallowed file extensions. If NULL, no extension is disallowed (i.e. all are allowed).
     *
     * @var array
     */
    protected $disallowedFileExtensions = null;

    /**
     * Entry method for use as TCEMain "inline" field filter
     *
     * @param array $parameters
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $tceMain
     * @return array
     */
    public function filterInlineChildren(array $parameters, \TYPO3\CMS\Core\DataHandling\DataHandler $tceMain)
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
                $parts = \TYPO3\CMS\Core\Utility\GeneralUtility::revExplode('_', $value, 2);
                $fileReferenceUid = $parts[count($parts) - 1];
                $fileReference = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFileReferenceObject($fileReferenceUid);
                $file = $fileReference->getOriginalFile();
                if ($this->isAllowed($file->getName())) {
                    $cleanValues[] = $value;
                } else {
                    // Remove the erroneously created reference record again
                    $tceMain->deleteAction('sys_file_reference', $fileReferenceUid);
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
     * @param \TYPO3\CMS\Core\Resource\Driver\DriverInterface $driver
     * @return bool|int -1 if the file should not be included in a listing
     */
    public function filterFileList($itemName, $itemIdentifier, $parentIdentifier, array $additionalInformation, \TYPO3\CMS\Core\Resource\Driver\DriverInterface $driver)
    {
        $returnCode = true;
        // Early return in case no file filters are set at all
        if ($this->allowedFileExtensions === null && $this->disallowedFileExtensions === null) {
            return $returnCode;
        }
        // Check that this is a file and not a folder
        if ($driver->fileExists($itemIdentifier)) {
            if (!$this->isAllowed($itemName)) {
                $returnCode = -1;
            }
        }
        return $returnCode;
    }

    /**
     * Checks whether a file is allowed according to the criteria defined in the class variables ($this->allowedFileExtensions etc.)
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @return bool
     */
    protected function isAllowed($fileName)
    {
        $result = true;
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
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
            $returnValue = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $inputArgument);
        }

        if (is_array($returnValue)) {
            $returnValue = array_map('strtolower', $returnValue);
        }

        return $returnValue;
    }
}

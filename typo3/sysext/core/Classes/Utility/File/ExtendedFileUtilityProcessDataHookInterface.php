<?php
namespace TYPO3\CMS\Core\Utility\File;

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
 * Interface for classes which hook into extFileFunctions and do additional processData processing.
 */
interface ExtendedFileUtilityProcessDataHookInterface
{
    /**
     * Post-process a file action.
     *
     * @param string $action The action
     * @param array $cmdArr The parameter sent to the action handler
     * @param array $result The results of all calls to the action handler
     * @param \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility $parentObject Parent object
     * @return void
     */
    public function processData_postProcessAction($action, array $cmdArr, array $result, ExtendedFileUtility $parentObject);
}

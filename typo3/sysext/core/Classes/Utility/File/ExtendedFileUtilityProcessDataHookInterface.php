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

namespace TYPO3\CMS\Core\Utility\File;

/**
 * Interface for classes which hook into extFileFunctions and do additional processData processing.
 * @deprecated since TYPO3 v11 LTS, will be removed in TYPO3 v12.0. Use the PSR-14-based AfterFileCommandProcessedEvent instead.
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
     */
    public function processData_postProcessAction($action, array $cmdArr, array $result, ExtendedFileUtility $parentObject);
}

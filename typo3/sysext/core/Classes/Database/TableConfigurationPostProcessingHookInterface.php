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

namespace TYPO3\CMS\Core\Database;

/**
 * interface for classes with hook for postprocessing extTables after loading
 * @deprecated will be removed in TYPO3 v12.0. Use BootCompletedEvent instead.
 */
interface TableConfigurationPostProcessingHookInterface
{
    /**
     * Function which may process data created / registered by extTables
     * scripts (f.e. modifying TCA data of all extensions)
     */
    public function processData();
}

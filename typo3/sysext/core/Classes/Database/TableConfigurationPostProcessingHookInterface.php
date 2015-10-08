<?php
namespace TYPO3\CMS\Core\Database;

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
 * interface for classes with hook for postprocessing extTables after loading
 */
interface TableConfigurationPostProcessingHookInterface
{
    /**
     * Function which may process data created / registered by extTables
     * scripts (f.e. modifying TCA data of all extensions)
     *
     * @return void
     */
    public function processData();
}

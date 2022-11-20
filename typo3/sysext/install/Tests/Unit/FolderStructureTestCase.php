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

namespace TYPO3\CMS\Install\Tests\Unit;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
abstract class FolderStructureTestCase extends UnitTestCase
{
    /**
     * Create a random directory in the file system and return the path.
     * Created directories are registered for deletion upon test ending.
     *
     * @param string $prefix
     */
    protected function getTestDirectory($prefix = 'root_'): string
    {
        $testRoot = Environment::getVarPath() . '/tests/';
        $this->testFilesToDelete[] = $testRoot;
        $path = $testRoot . StringUtility::getUniqueId($prefix);
        GeneralUtility::mkdir_deep($path);
        chmod($testRoot, 02777);
        return $path;
    }

    /**
     * Return a random test filename within a virtual test directory
     *
     * @return non-empty-string
     */
    protected function getTestFilePath($prefix = 'file_'): string
    {
        return $this->getTestDirectory() . '/' . StringUtility::getUniqueId($prefix);
    }
}

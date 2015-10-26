<?php
namespace TYPO3\CMS\Install\Tests\Unit;

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

use org\bovigo\vfs\vfsStream;

/**
 * Test case
 */
abstract class FolderStructureTestCase extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Create a random directory in the virtual file system and return the path.
     *
     * @param string $prefix
     * @return string
     */
    protected function getVirtualTestDir($prefix = 'root_')
    {
        $root = vfsStream::setup();
        $path = $root->url() . '/typo3temp/' . $this->getUniqueId($prefix);
        \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($path);
        return $path;
    }

    /**
     * Return a random test filename within a virtual test directory
     *
     * @param string $prefix
     * @return string
     */
    protected function getVirtualTestFilePath($prefix = 'file_')
    {
        return $this->getVirtualTestDir() . '/' . $this->getUniqueId($prefix);
    }
}

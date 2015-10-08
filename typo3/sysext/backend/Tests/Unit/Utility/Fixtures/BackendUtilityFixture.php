<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Utility\Fixtures;

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
 * BackendUtility fixture
 */
class BackendUtilityFixture extends \TYPO3\CMS\Backend\Utility\BackendUtility
{
    /**
     * @param int $id
     * @param array $rootLine
     * @param bool $returnPartArray
     * @return array
     */
    public static function getPagesTSconfig($id, $rootLine = null, $returnPartArray = false)
    {
        return [];
    }
}

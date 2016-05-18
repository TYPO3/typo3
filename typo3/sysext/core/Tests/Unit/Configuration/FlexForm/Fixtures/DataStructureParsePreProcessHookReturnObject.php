<?php
declare(strict_types=1);
namespace TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm\Fixtures;

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
 * Fixture to test hooks from FlexFormTools
 */
class DataStructureParsePreProcessHookReturnObject
{
    /**
     * Returns an array (invalid)
     *
     * @param array $identifier
     * @return \stdClass
     */
    public function parseDataStructureByIdentifierPreProcess(array $identifier): \stdClass
    {
        return new \stdClass();
    }
}

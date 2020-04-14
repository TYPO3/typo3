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

namespace TYPO3\CMS\Install\Tests\Unit\FolderStructure;

use TYPO3\CMS\Install\FolderStructure\DefaultFactory;
use TYPO3\CMS\Install\FolderStructure\StructureFacadeInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DefaultFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getStructureReturnsInstanceOfStructureFacadeInterface()
    {
        $object = new DefaultFactory();
        self::assertInstanceOf(StructureFacadeInterface::class, $object->getStructure());
    }
}

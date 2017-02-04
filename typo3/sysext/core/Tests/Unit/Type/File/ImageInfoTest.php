<?php
namespace TYPO3\CMS\Core\Tests\Unit\Type\File;

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
 * Test case
 */
class ImageInfoTest extends \TYPO3\Components\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     */
    public function classImageInfoCanBeInstantiated()
    {
        $className = 'TYPO3\CMS\Core\Type\File\ImageInfo';
        $classInstance = new \TYPO3\CMS\Core\Type\File\ImageInfo('FooFileName');
        $this->assertInstanceOf($className, $classInstance);
    }
}

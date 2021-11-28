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

namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Utility;

use TYPO3\CMS\Core\Category\CategoryRegistry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ExtensionManagementUtilityTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected bool $resetSingletonInstances = true;

    /**
     * @test
     */
    public function doesMakeCategorizableCallsTheCategoryRegistryWithDefaultFieldName(): void
    {
        $extensionKey = StringUtility::getUniqueId('extension');
        $tableName = StringUtility::getUniqueId('table');

        $registryMock = $this->getMockBuilder(CategoryRegistry::class)->getMock();
        $registryMock->expects(self::once())->method('add')->with($extensionKey, $tableName, 'categories', []);
        GeneralUtility::setSingletonInstance(CategoryRegistry::class, $registryMock);
        ExtensionManagementUtility::makeCategorizable($extensionKey, $tableName);
    }

    /**
     * @test
     */
    public function doesMakeCategorizableCallsTheCategoryRegistryWithFieldName(): void
    {
        $extensionKey = StringUtility::getUniqueId('extension');
        $tableName = StringUtility::getUniqueId('table');
        $fieldName = StringUtility::getUniqueId('field');

        $registryMock = $this->getMockBuilder(CategoryRegistry::class)->getMock();
        $registryMock->expects(self::once())->method('add')->with($extensionKey, $tableName, $fieldName, []);
        GeneralUtility::setSingletonInstance(CategoryRegistry::class, $registryMock);
        ExtensionManagementUtility::makeCategorizable($extensionKey, $tableName, $fieldName);
    }
}

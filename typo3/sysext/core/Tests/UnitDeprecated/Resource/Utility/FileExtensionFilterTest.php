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

namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Resource\Utility;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test suite for filtering files by their extensions.
 */
class FileExtensionFilterTest extends UnitTestCase
{
    /**
     * Cleans up this test suite.
     */
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @return array
     */
    public function invalidInlineChildrenFilterParametersDataProvider(): array
    {
        return [
            [null, null, null],
            ['', '', [0, '', null, false]],
            [null, null, [0, '', null, false]],
        ];
    }

    /**
     * @param array|string $allowed
     * @param array|string $disallowed
     * @param array|string $values
     * @test
     * @dataProvider invalidInlineChildrenFilterParametersDataProvider
     */
    public function areInlineChildrenFilteredWithInvalidParameters($allowed, $disallowed, $values): void
    {
        $parameters = [
            'allowedFileExtensions' => $allowed,
            'disallowedFileExtensions' => $disallowed,
            'values' => $values,
        ];

        $dataHandlerMock = $this->createMock(DataHandler::class);
        $dataHandlerMock->expects(self::never())->method('deleteAction');
        $resourceFactoryMock = $this->createMock(ResourceFactory::class);
        $resourceFactoryMock->expects(self::never())->method('getFileReferenceObject');
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactoryMock);
        (new FileExtensionFilter())->filterInlineChildren($parameters, $dataHandlerMock);
    }
}

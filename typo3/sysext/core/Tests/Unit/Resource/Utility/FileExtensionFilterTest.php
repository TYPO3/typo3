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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Utility;

use Prophecy\PhpUnit\ProphecyTrait;
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
    use ProphecyTrait;

    /**
     * Cleans up this test suite.
     */
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function areInlineChildrenFilteredWithInvalidParameters(): void
    {
        $dataHandlerProphecy = $this->prophesize(DataHandler::class);
        $dataHandlerProphecy->deleteAction()->shouldNotBeCalled();
        $resourceFactoryProphecy = $this->prophesize(ResourceFactory::class);
        $resourceFactoryProphecy->getFileReferenceObject()->shouldNotBeCalled();
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactoryProphecy->reveal());
        (new FileExtensionFilter())->filter([0, '', null, false], '', '', $dataHandlerProphecy->reveal());
    }

    /**
     * @return array
     */
    public function extensionFilterIgnoresCaseInAllowedExtensionCheckDataProvider(): array
    {
        return [
            'Allowed extensions' => [
                'ext1', 'EXT1', '', true,
            ],
            'Allowed extensions, lower and upper case mix' => [
                'ext1', 'ext2, ExT1, Ext3', '', true,
            ],
            'Disallowed extensions' => [
                'ext1', '', 'EXT1', false,
            ],
            'Disallowed extensions, lower and upper case mix' => [
                'ext1', '', 'ext2, ExT1, Ext3', false,
            ],
            'Combine allowed / disallowed extensions' => [
                'ext1', 'EXT1', 'EXT1', false,
            ],
        ];
    }

    /**
     * @param string $fileExtension
     * @param array|string $allowedExtensions
     * @param array|string $disallowedExtensions
     * @param bool $isAllowed
     * @test
     * @dataProvider extensionFilterIgnoresCaseInAllowedExtensionCheckDataProvider
     */
    public function extensionFilterIgnoresCaseInAllowedExtensionCheck($fileExtension, $allowedExtensions, $disallowedExtensions, $isAllowed): void
    {
        $filter = $this->getAccessibleMock(FileExtensionFilter::class, ['dummy']);
        $filter->setAllowedFileExtensions($allowedExtensions);
        $filter->setDisallowedFileExtensions($disallowedExtensions);
        $result = $filter->_call('isAllowed', $fileExtension);
        self::assertEquals($isAllowed, $result);
    }
}

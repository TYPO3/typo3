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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileExtensionFilterTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function areInlineChildrenFilteredWithInvalidParameters(): void
    {
        $resourceFactoryMock = $this->createMock(ResourceFactory::class);
        $resourceFactoryMock->expects(self::never())->method('getFileReferenceObject');
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactoryMock);
        (new FileExtensionFilter())->filter([0, '', null, false], '', '');
    }

    public static function extensionFilterIgnoresCaseInAllowedExtensionCheckDataProvider(): array
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

    #[DataProvider('extensionFilterIgnoresCaseInAllowedExtensionCheckDataProvider')]
    #[Test]
    public function extensionFilterIgnoresCaseInAllowedExtensionCheck(
        string $fileExtension,
        string $allowedExtensions,
        string $disallowedExtensions,
        bool $isAllowed
    ): void {
        $filter = new FileExtensionFilter();
        $filter->setAllowedFileExtensions($allowedExtensions);
        $filter->setDisallowedFileExtensions($disallowedExtensions);
        $result = $filter->isAllowed($fileExtension);
        self::assertEquals($isAllowed, $result);
    }
}

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

namespace TYPO3\CMS\Linkvalidator\Tests\Unit\Linktype;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;
use TYPO3\CMS\Linkvalidator\Linktype\FileLinktype;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileLinktypeTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    public static function fetchTypeDetectsFileLinksDataProvider(): \Generator
    {
        yield 'link field' => ['file:42', 'file'];
        yield 'rich text field' => ['t3://file?uid=42', 'file'];
        yield 'page link' => ['t3://page?uid=42', ''];
    }

    #[DataProvider('fetchTypeDetectsFileLinksDataProvider')]
    #[Test]
    public function fetchTypeDetectsFileLinks(string $tokenValue, string $expectedType): void
    {
        $subject = new FileLinktype();

        self::assertSame($expectedType, $subject->fetchType(['type' => 'db', 'tokenValue' => $tokenValue], '', 'db'));
    }

    public static function checkLinkResolvesFileByUidDataProvider(): \Generator
    {
        yield 'link field' => ['file:42'];
        yield 'rich text field' => ['t3://file?uid=42'];
        yield 'rich text field with further parameters' => ['t3://file?foo=bar&amp;uid=42'];
    }

    #[DataProvider('checkLinkResolvesFileByUidDataProvider')]
    #[Test]
    public function checkLinkResolvesFileByUid(string $url): void
    {
        $file = self::createStub(File::class);
        $file->method('isMissing')->willReturn(false);
        $resourceFactory = $this->createMock(ResourceFactory::class);
        $resourceFactory->expects($this->once())->method('retrieveFileOrFolderObject')
            ->with(self::logicalOr('42', 'file:42'))
            ->willReturn($file);
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory);
        $subject = new FileLinktype();

        self::assertTrue($subject->checkLink($url, [], self::createStub(LinkAnalyzer::class)));
    }
}

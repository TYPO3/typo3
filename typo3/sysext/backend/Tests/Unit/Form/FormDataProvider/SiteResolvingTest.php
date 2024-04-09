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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataProvider\SiteResolving;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SiteResolvingTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function addDataAddsSiteObjectOfDefaultLanguageRow(): void
    {
        $siteFinderMock = $this->createMock(SiteFinder::class);
        $siteMock = $this->createMock(Site::class);
        $siteFinderMock->method('getSiteByPageId')->with(23)->willReturn($siteMock);
        $input = [
            'defaultLanguagePageRow' => [
                'uid' => 23,
            ],
            'effectivePid' => 42,
            'site' => $siteMock,
        ];
        $expected = $input;
        $expected['site'] = $siteMock;
        self::assertSame($expected, (new SiteResolving($siteFinderMock))->addData($input));
    }

    #[Test]
    public function addDataAddsSiteObjectOfEffectivePid(): void
    {
        $siteFinderMock = $this->createMock(SiteFinder::class);
        $siteMock = $this->createMock(Site::class);
        $siteFinderMock->method('getSiteByPageId')->with(42)->willReturn($siteMock);
        $input = [
            'effectivePid' => 42,
            'site' => $siteMock,
        ];
        $expected = $input;
        $expected['site'] = $siteMock;
        self::assertSame($expected, (new SiteResolving($siteFinderMock))->addData($input));
    }

    #[Test]
    public function addDataAddsSiteObjectOfEffectivePidForNewPage(): void
    {
        $siteFinderMock = $this->createMock(SiteFinder::class);
        $siteMock = $this->createMock(Site::class);
        $siteFinderMock->method('getSiteByPageId')->with(42)->willReturn($siteMock);
        $input = [
            'databaseRow' => [
                'uid' => 'NEW12345678BACDEF',
            ],
            'effectivePid' => 42,
            'site' => $siteMock,
            'tableName' => 'pages',
        ];
        $expected = $input;
        $expected['site'] = $siteMock;
        self::assertSame($expected, (new SiteResolving($siteFinderMock))->addData($input));
    }
}

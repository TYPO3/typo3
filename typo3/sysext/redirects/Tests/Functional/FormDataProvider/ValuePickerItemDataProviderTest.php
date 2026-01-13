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

namespace TYPO3\CMS\Redirects\Tests\Functional\FormDataProvider;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Redirects\FormDataProvider\ValuePickerItemDataProvider;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ValuePickerItemDataProviderTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en-US'],
    ];

    protected array $coreExtensionsToLoad = [
        'redirects',
    ];

    private array $sysRedirectResultSet = [
        'tableName' => 'sys_redirect',
        'processedTca' => [
            'columns' => [
                'source_host' => [
                    'config' => [
                        'valuePicker' => [
                            'items' => [],
                        ],
                    ],
                ],
            ],
        ],
    ];

    private ValuePickerItemDataProvider $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(dirname(__DIR__) . '/Fixtures/be_users.csv');
        $this->importCSVDataSet(dirname(__DIR__) . '/Fixtures/pages.csv');

        $this->setUpBackendUser(1);

        $this->subject = $this->get(ValuePickerItemDataProvider::class);
    }

    #[Test]
    public function addDataDoesNothingIfNoRedirectDataGiven(): void
    {
        $result = [
            'tableName' => 'tt_content',
        ];

        self::assertSame($result, $this->subject->addData($result));
    }

    #[Test]
    public function addDataAddsAllHostsAsKeyAndValueToRedirectValuePickerAsAdmin(): void
    {
        $this->createSites();

        $expected = $this->sysRedirectResultSet;
        $expected['processedTca']['columns']['source_host']['config']['valuePicker']['items'] = [
            ['label' => 'bar.test', 'value' => 'bar.test'],
            ['label' => 'foo.test', 'value' => 'foo.test'],
        ];

        self::assertSame($expected, $this->subject->addData($this->sysRedirectResultSet));
    }

    #[Test]
    public function addDataAddsAllAvailableHostsAsKeyAndValueToRedirectValuePickerAsNonAdmin(): void
    {
        $this->createSites();
        $this->setUpBackendUser(2);

        $expected = $this->sysRedirectResultSet;
        $expected['processedTca']['columns']['source_host']['config']['valuePicker']['items'] = [
            ['label' => 'bar.test', 'value' => 'bar.test'],
        ];

        self::assertSame($expected, $this->subject->addData($this->sysRedirectResultSet));
    }

    #[Test]
    public function addDataDoesNotChangeResultSetIfNoSitesAreFound(): void
    {
        self::assertSame($this->sysRedirectResultSet, $this->subject->addData($this->sysRedirectResultSet));
    }

    private function createSites(): void
    {
        $this->writeSiteConfiguration(
            'bar',
            $this->buildSiteConfiguration(13, 'https://bar.test/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://bar.test/'),
            ],
        );

        $this->writeSiteConfiguration(
            'foo',
            $this->buildSiteConfiguration(14, 'https://foo.test/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://foo.test/'),
            ],
        );
    }
}

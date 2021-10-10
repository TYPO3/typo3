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

namespace TYPO3\CMS\Backend\Tests\Functional\Clipboard;

use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional database test for Clipboard behaviour
 */
class ClipboardTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = ['workspaces'];

    private Clipboard $subject;
    private BackendUserAuthentication $backendUser;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::initializeDatabaseSnapshot();
    }

    public static function tearDownAfterClass(): void
    {
        static::destroyDatabaseSnapshot();
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = GeneralUtility::makeInstance(Clipboard::class);
        $this->backendUser = $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $this->withDatabaseSnapshot(function () {
            $this->setUpDatabase();
        });
    }

    protected function tearDown(): void
    {
        unset($this->subject, $this->backendUser);
        parent::tearDown();
    }

    protected function setUpDatabase(): void
    {
        $scenarioFile = __DIR__ . '/../Fixtures/CommonScenario.yaml';
        $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
        $writer = DataHandlerWriter::withBackendUser($this->backendUser);
        $writer->invokeFactory($factory);
        static::failIfArrayIsNotEmpty(
            $writer->getErrors()
        );
    }

    /**
     * @return array
     */
    public function localizationsAreResolvedDataProvider(): array
    {
        return [
            'live workspace with live & version localizations' => [
                1100,
                0,
                'pages',
                [
                    'FR: Welcome',
                    'FR-CA: Welcome',
                ],
            ],
            'draft workspace with live & version localizations' => [
                1100,
                1,
                'pages',
                [
                    'FR: Welcome',
                    'FR-CA: Welcome',
                    'ES: Bienvenido',
                ],
            ],
            'live workspace with live localizations only' => [
                1400,
                0,
                'pages',
                [
                    'FR: ACME in your Region',
                    'FR-CA: ACME in your Region',
                ],
            ],
            'draft workspace with live localizations only' => [
                1400,
                1,
                'pages',
                [
                    'FR: ACME in your Region',
                    'FR-CA: ACME in your Region',
                ],
            ],
            'live workspace with version localizations only' => [
                1500,
                0,
                'pages',
                [],
            ],
            'draft workspace with version localizations only' => [
                1500,
                1,
                'pages',
                [
                    'FR: Interne',
                ],
            ],
            'Record is not of currently selected table' => [
                1500,
                1,
                '_FILE',
                [
                    '<span class="text-muted">FR: Interne</span>',
                ],
            ],
        ];
    }

    /**
     * @dataProvider localizationsAreResolvedDataProvider
     * @test
     */
    public function localizationsAreResolved(
        int $pageId,
        int $workspaceId,
        string $table,
        array $expectation
    ): void {
        $this->backendUser->workspace = $workspaceId;
        $this->subject->clipData['normal']['el'] = ["pages|$pageId" => 'some value'];
        $this->subject->current = 'normal';
        $normalTab = $this->subject->getClipboardData($table)['tabs'][0];
        array_shift($normalTab['items']);
        $actualTitles = [];
        foreach ($normalTab['items'] as $item) {
            $actualTitles[] = $item['title'];
        }

        self::assertEqualsCanonicalizing($expectation, $actualTitles);
    }
}

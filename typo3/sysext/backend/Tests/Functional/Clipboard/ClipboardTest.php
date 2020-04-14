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

namespace TYPO3\CMS\Backend\Tests\Functional\Clipboard;

use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Utility\BackendUtility;
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

    /**
     * @var Clipboard
     */
    private $subject;

    /**
     * @var BackendUserAuthentication
     */
    private $backendUser;

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

        $this->withDatabaseSnapshot(function () {
            $this->setUpDatabase();
        });
    }

    protected function tearDown(): void
    {
        unset($this->subject, $this->backendUser);
        parent::tearDown();
    }

    protected function setUpDatabase()
    {
        Bootstrap::initializeLanguageObject();

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
                [
                    'FR: Welcome',
                    'FR-CA: Welcome',
                ]
            ],
            'draft workspace with live & version localizations' => [
                1100,
                1,
                [
                    'FR: Welcome',
                    'FR-CA: Welcome',
                    'ES: Bienvenido',
                ]
            ],
            'live workspace with live localizations only' => [
                1400,
                0,
                [
                    'FR: ACME in your Region',
                    'FR-CA: ACME in your Region',
                ]
            ],
            'draft workspace with live localizations only' => [
                1400,
                1,
                [
                    'FR: ACME in your Region',
                    'FR-CA: ACME in your Region',
                ]
            ],
            'live workspace with version localizations only' => [
                1500,
                0,
                []
            ],
            'draft workspace with version localizations only' => [
                1500,
                1,
                [
                    'FR: Interne',
                ]
            ],
        ];
    }

    /**
     * @param int $pageId
     * @param int $workspaceId
     * @param array $expectation
     *
     * @dataProvider localizationsAreResolvedDataProvider
     * @test
     */
    public function localizationsAreResolved(int $pageId, int $workspaceId, array $expectation)
    {
        $this->backendUser->workspace = $workspaceId;
        $record = BackendUtility::getRecordWSOL('pages', $pageId);
        $actualResult = array_column(
            $this->subject->getLocalizations('pages', $record),
            'title'
        );
        self::assertEqualsCanonicalizing($expectation, $actualResult);
    }
}

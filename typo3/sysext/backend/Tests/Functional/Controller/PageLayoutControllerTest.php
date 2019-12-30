<?php
namespace TYPO3\CMS\Backend\Tests\Functional\Controller;

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

use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional database test for PageLayoutController behaviour
 */
class PageLayoutControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = ['workspaces'];

    /**
     * @var PageLayoutController|AccessibleObjectInterface
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

        $this->subject = $this->getAccessibleMock(PageLayoutController::class, ['dummy']);
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
    public function currentPageHasSubPagesDataProvider(): array
    {
        return [
            'live workspace with live & version sub pages' => [
                1000,
                0,
                true
            ],
            'draft workspace with live & version sub pages' => [
                1000,
                1,
                true
            ],
            'live workspace with version sub pages only' => [
                1950,
                0,
                false
            ],
            'draft workspace with version sub pages only' => [
                1950,
                1,
                true
            ],
            'live workspace with live sub pages only' => [
                2000,
                0,
                true
            ],
            'draft workspace with live sub pages only' => [
                2000,
                1,
                true
            ],
        ];
    }

    /**
     * @param int $pageId
     * @param int $workspaceId
     * @param bool $expectation
     *
     * @dataProvider currentPageHasSubPagesDataProvider
     * @test
     */
    public function currentPageHasSubPages(int $pageId, int $workspaceId, bool $expectation)
    {
        $this->backendUser->workspace = $workspaceId;
        $this->subject->_set('id', $pageId);
        $actualResult = $this->subject->_call('currentPageHasSubPages');
        self::assertSame($expectation, $actualResult);
    }
}

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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;

/**
 * Writing in workspaces: what happens to the live record and its version when a record is updated in a workspace.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle (DataHandler, suffix "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair start from the same fixture in Fixtures/WorkspaceWrite/Given/ and assert the blog table against the same result CSV in Fixtures/WorkspaceWrite/. Both run as the same administrator in workspace 1, which the workspace of the backend user (DataHandler) and the workspace aspect of the context (Extbase) select, in a backend request. The extension workspaces is loaded, because DataHandler only looks for the existing version of a record when it is. An Extbase test that differs from core is skipped.
 *
 * The blog table is workspace aware, language aware and has the enable field hidden. Base.csv holds the administrator and the storage page 2. The scenario without a version starts from WithoutVersion.csv, the live blog 1. The scenario with a version starts from WithVersion.csv, the live blog 1 and its version 2 in workspace 1. The result CSVs list uid, pid, t3ver_wsid, t3ver_oid, t3ver_state, title and deleted of every blog.
 */
final class WorkspaceWriteTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceWrite/Given/Base.csv');
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = 1;
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $GLOBALS['EXEC_TIME'] = 1700000000;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(1));
        $this->get(ConfigurationManager::class)->setConfiguration([
            'persistence' => ['storagePid' => 2],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
    }

    /**
     * A record without a version is changed in a workspace. When the backend, in workspace 1, saves a new title for the live blog 1 ("Live blog") through DataHandler, DataHandler creates a version of it: the new row 2 has `t3ver_wsid` 1, `t3ver_oid` 1, `t3ver_state` 0 and the title "Changed in workspace". The live blog 1 keeps its title.
     *
     * @see updatingARecordWithoutAVersionInAWorkspaceByExtbase()
     */
    #[Test]
    public function updatingARecordWithoutAVersionInAWorkspaceByCore(): void
    {
        // Given: blog 1 exists in live and has no version in the workspace
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceWrite/Given/WithoutVersion.csv');

        // When: the title of blog 1 is changed in the workspace
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_blog' => [1 => ['title' => 'Changed in workspace']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the new title is in a version of blog 1
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/WorkspaceWrite/UpdatedRecordWithoutVersion.csv');
    }

    /**
     * Extbase loads blog 1 in workspace 1, `update()` and `persistAll()` write the title "Changed in workspace" to the live row (1) and create no version: the live blog is changed, where the core creates a version (2) and leaves the live blog unchanged.
     *
     * @see updatingARecordWithoutAVersionInAWorkspaceByCore()
     */
    #[Test]
    public function updatingARecordWithoutAVersionInAWorkspaceByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes the new title to the live row (1) and creates no version.');

        // Given: blog 1 exists in live and has no version in the workspace
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceWrite/Given/WithoutVersion.csv');

        // When: blog 1 is loaded in the workspace, its title is changed and it is persisted
        $repository = $this->get(BlogRepository::class);
        $blog = $repository->findByUid(1);
        $blog->setTitle('Changed in workspace');
        $repository->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the new title is in a version of blog 1
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/WorkspaceWrite/UpdatedRecordWithoutVersion.csv');
    }

    /**
     * A record with a version is changed in a workspace. Blog 1 ("Live blog") has the version 2 ("Blog version") in workspace 1. When the backend saves a new title for blog 1 in workspace 1 through DataHandler, DataHandler updates the existing version: row 2 gets the title "Changed in workspace", no further version is created, and the live blog 1 keeps its title.
     *
     * @see updatingARecordWithAVersionInAWorkspaceByExtbase()
     */
    #[Test]
    public function updatingARecordWithAVersionInAWorkspaceByCore(): void
    {
        // Given: blog 1 exists in live and has the version 2 in the workspace
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceWrite/Given/WithVersion.csv');

        // When: the title of blog 1 is changed in the workspace
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_blog' => [1 => ['title' => 'Changed in workspace']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the new title is in version 2
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/WorkspaceWrite/UpdatedRecordWithVersion.csv');
    }

    /**
     * Extbase loads blog 1 in workspace 1, which returns the title of the version 2 ("Blog version"). `update()` and `persistAll()` write the title "Changed in workspace" to the live row (1) and leave the version (2) unchanged: the live blog is changed, where the core updates the version and leaves the live blog unchanged.
     *
     * @see updatingARecordWithAVersionInAWorkspaceByCore()
     */
    #[Test]
    public function updatingARecordWithAVersionInAWorkspaceByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes the new title to the live row (1) and leaves the version (2) unchanged.');

        // Given: blog 1 exists in live and has the version 2 in the workspace
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceWrite/Given/WithVersion.csv');

        // When: blog 1 is loaded in the workspace
        $repository = $this->get(BlogRepository::class);
        $blog = $repository->findByUid(1);

        // Then: it has the title of the version 2
        self::assertSame('Blog version', $blog->getTitle());

        // When: its title is changed and it is persisted
        $blog->setTitle('Changed in workspace');
        $repository->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the new title is in version 2
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/WorkspaceWrite/UpdatedRecordWithVersion.csv');
    }
}

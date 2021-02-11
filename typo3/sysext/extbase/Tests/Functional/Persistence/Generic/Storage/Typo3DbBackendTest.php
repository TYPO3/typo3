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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence\Generic\Storage;

use ExtbaseTeam\BlogExample\Domain\Model\Tag;
use ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository;
use ExtbaseTeam\BlogExample\Domain\Repository\PostRepository;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class Typo3DbBackendTest extends FunctionalTestCase
{
    protected bool $resetSingletonInstances = true;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    /**
     * @test
     */
    public function uidOfAlreadyPersistedValueObjectIsDeterminedCorrectly(): void
    {
        $this->importCSVDataSet('typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/tags.csv');
        $domainObject = new Tag('Tag10');

        /** @var Typo3DbBackend $typo3DbBackend */
        $typo3DbBackend = $this->get(Typo3DbBackend::class);
        $result = $typo3DbBackend->getUidOfAlreadyPersistedValueObject($domainObject);

        self::assertSame(10, $result);
    }

    /**
     * @test
     */
    public function getObjectDataByQueryChangesUidIfInPreview(): void
    {
        $this->importCSVDataSet('typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/blogs.csv');
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);

        /** @var PostRepository $blogRepository */
        $blogRepository = $this->get(BlogRepository::class);
        $context = new Context([
            'workspace' => new WorkspaceAspect(1),
            'language' => new LanguageAspect(0, 0),
        ]);
        GeneralUtility::setSingletonInstance(Context::class, $context);
        $querySettings = new Typo3QuerySettings($context, $this->get(ConfigurationManagerInterface::class));
        $querySettings->setRespectStoragePage(false);
        $query = $blogRepository->createQuery();
        $query->setQuerySettings($querySettings);
        $query->matching($query->equals('uid', 1));

        /** @var Typo3DbBackend $typo3DbBackend */
        $typo3DbBackend = $this->get(Typo3DbBackend::class);
        $objectData = $typo3DbBackend->getObjectDataByQuery($query);

        self::assertCount(1, $objectData);
        self::assertArrayHasKey('_ORIG_uid', $objectData[0]);
        self::assertSame(101, $objectData[0]['_ORIG_uid']);
        self::assertSame('WorkspaceOverlay Blog1', $objectData[0]['title']);
    }
}

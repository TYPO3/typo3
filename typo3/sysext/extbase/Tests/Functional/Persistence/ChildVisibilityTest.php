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
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\Persistence\RecordIdentityMap;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\CMS\Frontend\Page\PageParts;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Model\Tag;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Visibility of the children of a relation: records with a starttime in the future, an endtime in the past or a user
 * group that the visitor does not belong to.
 *
 * Every scenario is tested twice against the same expectation: once with what the core shows in the frontend as oracle
 * (suffix "ByCore"), once with Extbase (suffix "ByExtbase"). Both sides run in a frontend request with the same
 * context: the date aspect fixed at the time 1700000000 and the frontend user aspect of a visitor who belongs to the
 * user group 1. The core side reads the parent as the record API (RecordFactory) reads a record: the parent is selected
 * with ContentObjectRenderer::getRecords(), turned into a resolved record with
 * RecordFactory::createResolvedRecordFromDatabaseRow() and its relation field is read with Record::get(), which checks
 * the enable fields of the children. The Extbase side reads the parent with the repository and the relation as an
 * ObjectStorage. Both sides return the titles of the children in the order of the relation. A test of Extbase that
 * differs from core is skipped.
 *
 * The blog 1 on the storage page 2 (Fixtures/ChildVisibility/Blog.csv) has six posts (inline children, foreign field
 * `blog`) and the post 7 has six tags (MM relation `tags`). Posts and tags carry the same restrictions in the same
 * order: one without restriction, one with a `starttime` in the future (1800000000), one with an `endtime` in the past
 * (1600000000), one for the user group 2, one for the user group 1 and one that runs from 1600000000 to 1800000000.
 */
final class ChildVisibilityTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ChildVisibility/Blog.csv');
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $this->get(ConfigurationManager::class)->setConfiguration([
            'persistence' => ['storagePid' => 2],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
        $this->get(Context::class)->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('@1700000000')));
        $this->get(Context::class)->setAspect('frontend.user', new UserAspect(null, [1]));
    }

    /**
     * The visitor belongs to the user group 1 and the time is 1700000000. Of the six posts of blog 1 the core returns
     * the post without restriction, the post for the user group 1 and the post that runs from 1600000000 to 1800000000,
     * in the order of the relation: "Open post", "Group one post" and "Running post". It drops "Scheduled post"
     * (`starttime` in the future), "Expired post" (`endtime` in the past) and "Group two post" (user group 2).
     *
     * @see inlineChildrenWithEnableFieldsAreReadByExtbase()
     */
    #[Test]
    public function inlineChildrenWithEnableFieldsAreReadByCore(): void
    {
        // When: the posts of blog 1 are read
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($GLOBALS['TYPO3_REQUEST']);
        $row = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['uidInList' => '1', 'pidInList' => '2'])[0];
        $blog = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_blog', $row, $this->get(Context::class), new RecordIdentityMap());
        $titles = array_map(static fn(RecordInterface $post): string => $post->get('title'), iterator_to_array($blog->get('posts'), false));

        // Then: only the visible posts are returned
        self::assertSame(['Open post', 'Group one post', 'Running post'], $titles);
    }

    /**
     * Extbase returns the same three posts of blog 1 as the core, in the same order: "Open post", "Group one post" and
     * "Running post". The post with a `starttime` in the future, the post with an `endtime` in the past and the post
     * for the user group 2 are not part of the ObjectStorage.
     *
     * @see inlineChildrenWithEnableFieldsAreReadByCore()
     */
    #[Test]
    public function inlineChildrenWithEnableFieldsAreReadByExtbase(): void
    {
        // When: the posts of blog 1 are read
        $blog = $this->get(BlogRepository::class)->findByUid(1);
        $titles = array_map(static fn(Post $post): string => $post->getTitle(), $blog->getPosts()->toArray());

        // Then: only the visible posts are returned
        self::assertSame(['Open post', 'Group one post', 'Running post'], $titles);
    }

    /**
     * The visitor belongs to the user group 1 and the time is 1700000000. Of the six tags that post 7 stores in its
     * relation table the core returns the tag without restriction, the tag for the user group 1 and the tag that runs
     * from 1600000000 to 1800000000, in the order of the relation: "Open tag", "Group one tag" and "Running tag". It
     * drops "Scheduled tag" (`starttime` in the future), "Expired tag" (`endtime` in the past) and "Group two tag"
     * (user group 2).
     *
     * @see relatedRecordsWithEnableFieldsAreReadByExtbase()
     */
    #[Test]
    public function relatedRecordsWithEnableFieldsAreReadByCore(): void
    {
        // When: the tags of post 7 are read
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($GLOBALS['TYPO3_REQUEST']);
        $row = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['uidInList' => '7', 'pidInList' => '2'])[0];
        $post = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $row, $this->get(Context::class), new RecordIdentityMap());
        $names = array_map(static fn(RecordInterface $tag): string => $tag->get('name'), iterator_to_array($post->get('tags'), false));

        // Then: only the visible tags are returned
        self::assertSame(['Open tag', 'Group one tag', 'Running tag'], $names);
    }

    /**
     * Extbase returns the same three tags of post 7 as the core, in the same order: "Open tag", "Group one tag" and
     * "Running tag". The tag with a `starttime` in the future, the tag with an `endtime` in the past and the tag for
     * the user group 2 are not part of the ObjectStorage.
     *
     * @see relatedRecordsWithEnableFieldsAreReadByCore()
     */
    #[Test]
    public function relatedRecordsWithEnableFieldsAreReadByExtbase(): void
    {
        // When: the tags of post 7 are read
        $post = $this->get(PostRepository::class)->findByUid(7);
        $names = array_map(static fn(Tag $tag): string => $tag->getName(), $post->getTags()->toArray());

        // Then: only the visible tags are returned
        self::assertSame(['Open tag', 'Group one tag', 'Running tag'], $names);
    }
}

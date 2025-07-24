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
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

final class QueryHiddenTranslatedRecordsTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    private PostRepository $postRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/QueryHiddenTranslatedRecordsTestImport.csv');
        $configuration = [
            'persistence' => [
                'storagePid' => 20,
            ],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);
        $this->postRepository = $this->get(PostRepository::class);

        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }
    #[Test]
    public function translatedRecordIsRenderedInWorkspaceWhenLiveRecordIsHidden(): void
    {
        $context = $this->get(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $context->setAspect('workspace', new WorkspaceAspect(1));

        // Query for the translated post in workspace - should return the translation
        // even though the default language record is hidden. Does not return
        // translations for deleted default records.
        $posts = $this->postRepository->findBy(['pid' => 20]);
        self::assertCount(1, $posts, 'Should find 3 posts in workspace: visible translation and workspace versions');

        // Test first post (uid 100 with workspace translation 201)
        self::assertEquals('Visible DA Translation Post in WS', $posts[0]->getTitle());
        self::assertEquals(100, $posts[0]->getUid());
        self::assertEquals(101, $posts[0]->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID));
        self::assertEquals(1, $posts[0]->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID));
        self::assertEquals(201, $posts[0]->_getProperty(AbstractDomainObject::PROPERTY_VERSIONED_UID));
    }

    #[Test]
    public function hiddenDefaultLanguageRecordIsNotRenderedInDefaultLanguageAndLive(): void
    {
        $context = $this->get(Context::class);
        $context->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON));

        $post = $this->postRepository->findByUid(100);
        self::assertNull($post, 'Hidden default language post should not be found');
    }

    #[Test]
    public function hiddenTranslationIsNotRenderedInDefaultLanguageAndLive(): void
    {
        $context = $this->get(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));

        $post = $this->postRepository->findByUid(100);
        self::assertNull($post, 'Hidden translation post should not be found');
    }

    #[Test]
    public function hiddenTranslationIsNotRenderedWhenDefaultRecordIsDeleted(): void
    {
        $context = $this->get(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));

        $post = $this->postRepository->findByUid(300);
        self::assertNull($post, 'Hidden translation should not be found when default record is deleted');
    }

    #[Test]
    public function visibleTranslationIsNotRenderedWhenDefaultRecordIsDeleted(): void
    {
        $context = $this->get(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));

        $post = $this->postRepository->findByUid(400);
        self::assertNull($post, 'Visible translation should not be found when default record is deleted');
    }

    #[Test]
    public function hiddenTranslationIsNotRenderedInWorkspaceWhenDefaultRecordIsDeleted(): void
    {
        $context = $this->get(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $context->setAspect('workspace', new WorkspaceAspect(1));

        $post = $this->postRepository->findByUid(300);
        self::assertNull($post, 'Hidden translation should not be found in workspace when default record is deleted');
    }

    #[Test]
    public function visibleTranslationIsNotRenderedInWorkspaceWhenDefaultRecordIsDeleted(): void
    {
        $context = $this->get(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $context->setAspect('workspace', new WorkspaceAspect(1));

        $post = $this->postRepository->findByUid(400);
        self::assertNull($post, 'Visible translation should not be found in workspace when default record is deleted');
    }
}

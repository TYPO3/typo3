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
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

final class QueryIgnoreEnableFieldsTranslationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    private PostRepository $postRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/QueryIgnoreEnableFieldsTranslationTestImport.csv');
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration([
            'persistence' => [
                'storagePid' => 20,
            ],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $this->postRepository = $this->get(PostRepository::class);

        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    #[Test]
    public function hiddenTranslationIsNotOverlaidWithEnableFieldsRespected(): void
    {
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));

        $query = $this->postRepository->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = $query->execute();

        self::assertSame(['Post 2 - DA'], $this->getTitles($posts));
        self::assertCount(1, $posts);
    }

    #[Test]
    public function hiddenTranslationIsOverlaidWhenEnableFieldsAreIgnored(): void
    {
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));

        $query = $this->postRepository->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $posts = $query->execute();

        self::assertSame(['Post 1 - DA', 'Post 2 - DA'], $this->getTitles($posts));
        self::assertCount(2, $posts);
    }

    #[Test]
    public function hiddenTranslationIsOverlaidWhenOnlyTheDisabledFieldIsIgnored(): void
    {
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));

        $query = $this->postRepository->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $query->getQuerySettings()->setIgnoreEnableFields(true)->setEnableFieldsToBeIgnored(['disabled']);
        $posts = $query->execute();

        self::assertSame(['Post 1 - DA', 'Post 2 - DA'], $this->getTitles($posts));
    }

    #[Test]
    public function hiddenTranslationFallsBackToDefaultLanguageWithMixedOverlaysAndEnableFieldsRespected(): void
    {
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));

        $query = $this->postRepository->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = $query->execute();

        self::assertSame(['Post 1', 'Post 2 - DA'], $this->getTitles($posts));
    }

    #[Test]
    public function hiddenTranslationIsOverlaidWithMixedOverlaysWhenEnableFieldsAreIgnored(): void
    {
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));

        $query = $this->postRepository->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $posts = $query->execute();

        self::assertSame(['Post 1 - DA', 'Post 2 - DA'], $this->getTitles($posts));
    }

    /**
     * @param iterable<\TYPO3Tests\BlogExample\Domain\Model\Post> $posts
     * @return string[]
     */
    private function getTitles(iterable $posts): array
    {
        $titles = [];
        foreach ($posts as $post) {
            $titles[] = $post->getTitle();
        }
        return $titles;
    }
}

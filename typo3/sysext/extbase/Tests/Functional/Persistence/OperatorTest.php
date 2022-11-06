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

use ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository;
use ExtbaseTeam\BlogExample\Domain\Repository\PostRepository;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class OperatorTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    protected BlogRepository $blogRepository;
    protected PostRepository $postRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/blogs.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/posts.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/tags.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/post-tag-mm.csv');

        $this->blogRepository = $this->get(BlogRepository::class);
        $this->postRepository = $this->get(PostRepository::class);

        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    /**
     * @test
     */
    public function equalsNullIsResolvedCorrectly(): void
    {
        $query = $this->postRepository->createQuery();

        $query->matching(
            $query->equals('title', null)
        );

        self::assertSame(0, $query->count());
    }

    /**
     * @test
     */
    public function equalsCorrectlyHandlesCaseSensitivity(): void
    {
        $query = $this->postRepository->createQuery();

        $query->matching(
            $query->equals('title', 'PoSt1', false)
        );

        self::assertSame(2, $query->count());
    }

    /**
     * @test
     */
    public function betweenSetsBoundariesCorrectly(): void
    {
        $query = $this->postRepository->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        $query->matching(
            $query->between('uid', 3, 5)
        );

        $result = array_map(
            static function ($row) {
                return $row['uid'];
            },
            $query->execute(true)
        );
        self::assertEquals([3, 4, 5], $result);
    }
}

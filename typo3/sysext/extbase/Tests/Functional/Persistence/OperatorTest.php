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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

use ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository;
use ExtbaseTeam\BlogExample\Domain\Repository\PostRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class OperatorTest extends FunctionalTestCase
{
    /**
     * @var \ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository
     */
    protected $blogRepository;

    /**
     * @var \ExtbaseTeam\BlogExample\Domain\Repository\PostRepository
     */
    protected $postRepository;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['extbase', 'fluid'];

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface The object manager
     */
    protected $objectManager;

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/blogs.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/posts.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/tags.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/post-tag-mm.xml');

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->blogRepository = $this->objectManager->get(BlogRepository::class);
        $this->postRepository = $this->objectManager->get(PostRepository::class);
    }

    /**
     * @test
     */
    public function equalsNullIsResolvedCorrectly()
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
    public function equalsCorrectlyHandlesCaseSensitivity()
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
    public function betweenSetsBoundariesCorrectly()
    {
        $query = $this->postRepository->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        $query->matching(
            $query->between('uid', 3, 5)
        );

        $result = array_map(
            function ($row) {
                return $row['uid'];
            },
            $query->execute(true)
        );
        self::assertEquals([3, 4, 5], $result);
    }
}

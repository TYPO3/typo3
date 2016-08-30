<?php
namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class OperatorTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
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
    protected function setUp()
    {
        parent::setUp();

        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/pages.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/blogs.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/posts.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/tags.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/post-tag-mm.xml');

        $this->objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->blogRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository::class);
        $this->postRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\PostRepository::class);
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

        $this->assertSame(0, $query->count());
    }

    /**
     * @test
     */
    public function equalsCorrectlyHandlesCaseSensivity()
    {
        $query = $this->postRepository->createQuery();

        $query->matching(
            $query->equals('title', 'PoSt1', false)
        );

        $this->assertSame(2, $query->count());
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
        $this->assertEquals([3, 4, 5], $result);
    }
}

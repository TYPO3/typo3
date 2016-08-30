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

class AddTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected $persistentManager;

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
     * @var \TYPO3\CMS\Extbase\Persistence\Repository
     */
    protected $blogRepository;

    /**
     * Sets up this test suite.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->persistentManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
        $this->blogRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository::class);
    }

    /**
     * @test
     */
    public function addSimpleObjectTest()
    {
        $newBlogTitle = 'aDi1oogh';
        $newBlog = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Model\Blog::class);
        $newBlog->setTitle($newBlogTitle);

        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository $blogRepository */
        $this->blogRepository->add($newBlog);
        $this->persistentManager->persistAll();

        $newBlogCount = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_domain_model_blog', 'title = \'' . $newBlogTitle . '\'');
        $this->assertSame(1, $newBlogCount);
    }

    /**
     * @test
     */
    public function addObjectSetsDefaultLanguageTest()
    {
        $newBlogTitle = 'aDi1oogh';
        $newBlog = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Model\Blog::class);
        $newBlog->setTitle($newBlogTitle);

        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository $blogRepository */
        $this->blogRepository->add($newBlog);
        $this->persistentManager->persistAll();

        $newBlogRecord = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'tx_blogexample_domain_model_blog', 'title = \'' . $newBlogTitle . '\'');
        $this->assertEquals(0, $newBlogRecord['sys_language_uid']);
    }

    /**
     * @test
     */
    public function addObjectSetsDefinedLanguageTest()
    {
        $newBlogTitle = 'aDi1oogh';
        $newBlog = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Model\Blog::class);
        $newBlog->setTitle($newBlogTitle);
        $newBlog->_setProperty('_languageUid', -1);

        /** @var \ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository $blogRepository */
        $this->blogRepository->add($newBlog);
        $this->persistentManager->persistAll();

        $newBlogRecord = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'tx_blogexample_domain_model_blog', 'title = \'' . $newBlogTitle . '\'');
        $this->assertEquals(-1, $newBlogRecord['sys_language_uid']);
    }
}

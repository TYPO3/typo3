<?php
namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

/**
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
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class AddTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
	 */
	protected $persistentManager;

	/**
	 * @var array
	 */
	protected $testExtensionsToLoad = array('typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example');

	/**
	 * @var array
	 */
	protected $coreExtensionsToLoad = array('extbase', 'fluid');

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
	public function setUp() {
		parent::setUp();

		$this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->persistentManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager');
		$this->blogRepository = $this->objectManager->get('ExtbaseTeam\\BlogExample\\Domain\\Repository\\BlogRepository');
	}

	/**
	 * @test
	 */
	public function addSimpleObjectTest() {
		$newBlogTitle = 'aDi1oogh';
		$newBlog = $this->objectManager->get('ExtbaseTeam\\BlogExample\\Domain\\Model\\Blog');
		$newBlog->setTitle($newBlogTitle);

		/** @var \ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository $blogRepository */
		$this->blogRepository->add($newBlog);
		$this->persistentManager->persistAll();

		$newBlogCount = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_domain_model_blog', 'title = \'' . $newBlogTitle . '\'');
		$this->assertSame(1, $newBlogCount);
	}
}

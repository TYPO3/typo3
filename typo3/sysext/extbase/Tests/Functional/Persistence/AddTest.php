<?php
namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Felix Oertel <typo3@foertel.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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
		$newBlog = $this->objectManager->create('ExtbaseTeam\\BlogExample\\Domain\\Model\\Blog');
		$newBlog->setTitle($newBlogTitle);

		/** @var \ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository $blogRepository */
		$this->blogRepository->add($newBlog);
		$this->persistentManager->persistAll();

		$newBlogCount = $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'tx_blogexample_domain_model_blog', 'title = \'' . $newBlogTitle . '\'');
		$this->assertSame(1, $newBlogCount);
	}
}

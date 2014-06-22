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

class OperatorTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

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
	 * Sets up this test suite.
	 */
	public function setUp() {
		parent::setUp();

		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/pages.xml');
		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/blogs.xml');
		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/posts.xml');
		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/tags.xml');
		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/post-tag-mm.xml');

		$this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->blogRepository = $this->objectManager->get('ExtbaseTeam\\BlogExample\\Domain\\Repository\\BlogRepository');
		$this->postRepository = $this->objectManager->get('ExtbaseTeam\\BlogExample\\Domain\\Repository\\PostRepository');

	}

	/**
	 * @test
	 */
	public function equalsNullIsResolvedCorrectly() {
		$query = $this->postRepository->createQuery();

		$query->matching(
			$query->equals('title', NULL)
		);

		$this->assertSame(0, $query->count());
	}

	/**
	 * @test
	 */
	public function equalsCorrectlyHandlesCaseSensivity() {
		$query = $this->postRepository->createQuery();

		$query->matching(
			$query->equals('title', 'PoSt1', FALSE)
		);

		$this->assertSame(2, $query->count());
	}
}

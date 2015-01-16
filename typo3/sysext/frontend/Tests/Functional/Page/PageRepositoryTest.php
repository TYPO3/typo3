<?php
namespace TYPO3\CMS\Frontend\Tests\Functional\Page;

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

use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Test case
 */
class PageRepositoryTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {
	/**
	 * @var string[]
	 */
	protected $coreExtensionsToLoad = array();

	/**
	 * @test
	 */
	public function getMenuSingleUidRoot() {
		$this->importDataSet(__DIR__ . '/../Fixtures/pages.xml');
		$pageRepository = new PageRepository();
		$pageRepository->init(false);

		$rows = $pageRepository->getMenu(1, 'uid, title');
		$this->assertArrayHasKey(2, $rows);
		$this->assertArrayHasKey(3, $rows);
		$this->assertArrayHasKey(4, $rows);
		$this->assertCount(3, $rows);
	}

	/**
	 * @test
	 */
	public function getMenuSingleUidSubpage() {
		$this->importDataSet(__DIR__ . '/../Fixtures/pages.xml');
		$pageRepository = new PageRepository();
		$pageRepository->init(false);

		$rows = $pageRepository->getMenu(2, 'uid, title');
		$this->assertArrayHasKey(5, $rows);
		$this->assertArrayHasKey(6, $rows);
		$this->assertArrayHasKey(7, $rows);
		$this->assertCount(3, $rows);
	}

	/**
	 * @test
	 */
	public function getMenuMulipleUid() {
		$this->importDataSet(__DIR__ . '/../Fixtures/pages.xml');
		$pageRepository = new PageRepository();
		$pageRepository->init(false);

		$rows = $pageRepository->getMenu(array(2, 3), 'uid, title');
		$this->assertArrayHasKey(5, $rows);
		$this->assertArrayHasKey(6, $rows);
		$this->assertArrayHasKey(7, $rows);
		$this->assertArrayHasKey(8, $rows);
		$this->assertArrayHasKey(9, $rows);
		$this->assertCount(5, $rows);
	}
}

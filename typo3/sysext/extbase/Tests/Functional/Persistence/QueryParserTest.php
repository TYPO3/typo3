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

class QueryParserTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser
	 */
	protected $queryParser;

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

		$this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->queryParser = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Storage\\Typo3DbQueryParser');
		$this->blogRepository = $this->objectManager->get('ExtbaseTeam\\BlogExample\\Domain\\Repository\\BlogRepository');
	}

	/**
	 * @test
	 */
	public function preparseQueryTakesOperatorsIntoHash() {
		$queryWithEquals = $this->blogRepository->createQuery();

		$queryWithEquals->matching(
			$queryWithEquals->equals('uid', 1)
		);

		list($hashWithEquals) = $this->queryParser->preparseQuery($queryWithEquals);

		$queryWithIn = $this->blogRepository->createQuery();

		$queryWithIn->matching(
			$queryWithIn->in('uid', array(1))
		);

		list($hashWithIn) = $this->queryParser->preparseQuery($queryWithIn);

		$this->assertNotSame($hashWithEquals, $hashWithIn);
	}

	/**
	 * @test
	 */
	public function preparseQueryHashDiffersForIsNullOperator() {
		$queryWithIsNull = $this->blogRepository->createQuery();

		$queryWithIsNull->matching(
			$queryWithIsNull->equals('title', NULL)
		);

		list($hashWithIsNull) = $this->queryParser->preparseQuery($queryWithIsNull);

		$queryWithoutIsNull = $this->blogRepository->createQuery();

		$queryWithoutIsNull->matching(
			$queryWithoutIsNull->equals('title', '')
		);

		list($hashWithoutIsNull) = $this->queryParser->preparseQuery($queryWithoutIsNull);

		$this->assertNotSame($hashWithIsNull, $hashWithoutIsNull);
	}


	/**
	 * @test
	 */
	public function preparseQueryHashDiffersForEqualsCaseSensitiveArgument() {
		$queryCaseSensitiveFalse = $this->blogRepository->createQuery();

		$queryCaseSensitiveFalse->matching(
			$queryCaseSensitiveFalse->equals('title', 'PoSt1', FALSE)
		);

		list($hashWithCaseSensitiveFalse) = $this->queryParser->preparseQuery($queryCaseSensitiveFalse);

		$queryCaseSensitiveTrue = $this->blogRepository->createQuery();

		$queryCaseSensitiveTrue->matching(
			$queryCaseSensitiveTrue->equals('title', 'PoSt1', TRUE)
		);

		list($hashWithCaseSensitiveTrue) = $this->queryParser->preparseQuery($queryCaseSensitiveTrue);

		$this->assertNotSame($hashWithCaseSensitiveFalse, $hashWithCaseSensitiveTrue);
	}
}

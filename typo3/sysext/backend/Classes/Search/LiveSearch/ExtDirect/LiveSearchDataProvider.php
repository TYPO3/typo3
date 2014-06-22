<?php
namespace TYPO3\CMS\Backend\Search\LiveSearch\ExtDirect;

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
/**
 * ExtDirect Class for handling backend live search.
 *
 * @author Michael Klapper <michael.klapper@aoemedia.de>
 * @author Jeff Segars <jeff@webempoweredchurch.org>
 */
class LiveSearchDataProvider {

	/**
	 * @var array
	 */
	protected $searchResults = array(
		'pageJump' => '',
		'searchItems' => array()
	);

	/**
	 * @var \TYPO3\CMS\Backend\Search\LiveSearch\LiveSearch
	 */
	protected $liveSearch = NULL;

	/**
	 * @var \TYPO3\CMS\Backend\Search\LiveSearch\QueryParser
	 */
	protected $queryParser = NULL;

	/**
	 * Initialize the live search
	 */
	public function __construct() {
		$this->liveSearch = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Search\\LiveSearch\\LiveSearch');
		$this->queryParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Search\\LiveSearch\\QueryParser');
	}

	/**
	 * @param stdClass $command
	 * @return array
	 */
	public function find($command) {
		$this->liveSearch->setStartCount($command->start);
		$this->liveSearch->setLimitCount($command->limit);
		$this->liveSearch->setQueryString($command->query);
		// Jump & edit - find page and retrieve an edit link (this is only for pages
		if ($this->queryParser->isValidPageJump($command->query)) {
			$this->searchResults['pageJump'] = $this->liveSearch->findPage($command->query);
			$commandQuery = $this->queryParser->getCommandForPageJump($command->query);
			if ($commandQuery) {
				$command->query = $commandQuery;
			}
		}
		// Search through the database and find records who match to the given search string
		$resultArray = $this->liveSearch->find($command->query);
		foreach ($resultArray as $resultFromTable) {
			foreach ($resultFromTable as $item) {
				$this->searchResults['searchItems'][] = $item;
			}
		}
		return $this->searchResults;
	}

}

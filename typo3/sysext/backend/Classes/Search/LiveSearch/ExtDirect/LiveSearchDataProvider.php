<?php
namespace TYPO3\CMS\Backend\Search\LiveSearch\ExtDirect;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Michael Klapper <michael.klapper@aoemedia.de>
 *  (c) 2010-2013 Jeff Segars <jeff@webempoweredchurch.org>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
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


?>
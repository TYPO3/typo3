<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2010 Michael Klapper <michael.klapper@aoemedia.de>
 *  (c) 2010 Jeff Segars <jeff@webempoweredchurch.org>
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
 * @package TYPO3
 * @subpackage t3lib
 */
class extDirect_dataProvider_BackendLiveSearch {

	/**
	 * @var array
	 */
	protected $searchResults = array (
		'pageJump'	  => '',
		'searchItems' => array()
	);

	/**
	 * @var array
	 */
	protected $helpContent = array (
		'title' => 'How to use advanced search tags',
		'text'  => 'Search in certain tables:<br />page:Home will search for all pages with the title "Home"',
		'keys'	=> array(),
	);

	/**
	 * @var t3lib_search_livesearch
	 */
	protected $liveSearch = null;

	/**
	 * @var t3lib_search_livesearch_queryParser
	 */
	protected $queryParser = null;

	/**
	 * Initialize the live search
	 */
	public function __construct() {
		// @todo Use the autoloader for this. Not sure why its not working.
		require_once(PATH_t3lib . 'search/class.t3lib_search_livesearch_queryParser.php');

		$this->liveSearch  = t3lib_div::makeInstance('t3lib_search_livesearch');
		$this->queryParser = t3lib_div::makeInstance('t3lib_search_livesearch_queryParser');
	}

	/**
	 *
	 *
	 * @param stdClass $command
	 *
	 * @return array
	 */
	public function find($command) {
		$this->liveSearch->setStartCount($command->start);
		$this->liveSearch->setLimitCount($command->limit);
		$this->liveSearch->setQueryString($command->query);

			// jump & edit - find page and retrieve an edit link (this is only for pages
		if ($this->queryParser->isValidPageJump($command->query)) {
			$this->searchResults['pageJump'] = $this->liveSearch->findPage($command->query);
			$commandQuery = $this->queryParser->getCommandForPageJump($command->query);
			if ($commandQuery) {
				$command->query = $commandQuery;
			}
		}

			// search through the database and find records who match to the given search string
		$resultArray = $this->liveSearch->find($command->query);

		foreach ($resultArray as $resultFromTable) {
			foreach ($resultFromTable as $item) {
				$this->searchResults['searchItems'][] = $item;
			}
		}

		return $this->searchResults;
	}

	/**
	 * Build up and retrieve the general and custom help text "How can you search"
	 *
	 * @return array
	 */
	public function getHelp() {
		$content = array();
		$this->helpContent['keys'] = $this->getRegisteredHelpContent();

		return $this->helpContent;
	}


	/**
	 * Find all registerd help information.
	 *
	 * @return array All registered help content will collected returned
	 * @todo Doesn't actually return any data
	 */
	public function getRegisteredHelpContent() {
		$helpArray = array();
		$liveSearchConfiguration = ((is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch'])) ? $GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch'] : array());

		foreach ($liveSearchConfiguration as $key => $table) {
			$helpArray[] = '#' . $key;
		}

		return $helpArray;
	}

}
?>

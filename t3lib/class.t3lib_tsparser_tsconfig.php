<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  (c) 2007-2011 Kraft Bernhard (kraftb@kraftb.at)
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
 * A TS-Config parsing class which performs condition evaluation
 *
 * @author	Kraft Bernhard <kraftb@kraftb.at>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

class t3lib_TSparser_TSconfig extends t3lib_TSparser {
	/**
	 * @var	array
	 */
	protected $rootLine = array();

	/**
	 * Parses the passed TS-Config using conditions and caching
	 *
	 * @param	string		$TStext: The TSConfig being parsed
	 * @param	string		$type: The type of TSConfig (either "userTS" or "PAGES")
	 * @param	integer		$id: The uid of the page being handled
	 * @param	array		$rootLine: The rootline of the page being handled
	 * @return	array		Array containing the parsed TSConfig and a flag wheter the content was retrieved from cache
	 * @see t3lib_TSparser
	 */
	public function parseTSconfig($TStext, $type, $id = 0, array $rootLine = array()) {
		$this->type = $type;
		$this->id = $id;
		$this->rootLine = $rootLine;
		$hash = md5($type . ':' . $TStext);
		$cachedContent = t3lib_BEfunc::getHash($hash, 0);

		if ($cachedContent) {
			$storedData = unserialize($cachedContent);
			$storedMD5 = substr($cachedContent, -strlen($hash));
			$storedData['match'] = array();
			$storedData = $this->matching($storedData);
			$checkMD5 = md5(serialize($storedData));

			if ($checkMD5 == $storedMD5) {
				$res = array(
					'TSconfig' => $storedData['TSconfig'],
					'cached' => 1,
				);
			} else {
				$shash = md5($checkMD5 . $hash);
				$cachedSpec = t3lib_BEfunc::getHash($shash, 0);
				if ($cachedSpec) {
					$storedData = unserialize($cachedSpec);
					$res = array(
						'TSconfig' => $storedData['TSconfig'],
						'cached' => 1,
					);
				} else {
					$storeData = $this->parseWithConditions($TStext);
					$serData = serialize($storeData);
					t3lib_BEfunc::storeHash($shash, $serData, $type . '_TSconfig');
					$res = array(
						'TSconfig' => $storeData['TSconfig'],
						'cached' => 0,
					);
				}
			}
		} else {
			$storeData = $this->parseWithConditions($TStext);
			$serData = serialize($storeData);
			$md5 = md5($serData);
			t3lib_BEfunc::storeHash($hash, $serData . $md5, $type . '_TSconfig');
			$res = array(
				'TSconfig' => $storeData['TSconfig'],
				'cached' => 0,
			);
		}

		return $res;
	}

	/**
	 * Does the actual parsing using the parent objects "parse" method. Creates the match-Object
	 *
	 * @param	string		$TSconfig: The TSConfig being parsed
	 * @return	array		Array containing the parsed TSConfig, the encountered sectiosn, the matched sections
	 */
	protected function parseWithConditions($TSconfig) {
		/* @var $matchObj t3lib_matchCondition_backend */
		$matchObj = t3lib_div::makeInstance('t3lib_matchCondition_backend');
		$matchObj->setRootline($this->rootLine);
		$matchObj->setPageId($this->id);

		$this->parse($TSconfig, $matchObj);

		$storeData = array(
			'TSconfig' => $this->setup,
			'sections' => $this->sections,
			'match' => $this->sectionsMatch,
		);

		return $storeData;
	}


	/**
	 * Is just going through an array of conditions to determine which are matching (for getting correct cache entry)
	 *
	 * @param	array		$cc: An array containing the sections to match
	 * @return	array		The input array with matching sections filled into the "match" key
	 */
	protected function matching(array $cc) {
		if (is_array($cc['sections'])) {
			/* @var $matchObj t3lib_matchCondition_backend */
			$matchObj = t3lib_div::makeInstance('t3lib_matchCondition_backend');
			$matchObj->setRootline($this->rootLine);
			$matchObj->setPageId($this->id);

			foreach ($cc['sections'] as $key => $pre) {
				if ($matchObj->match($pre)) {
					$cc['match'][$key] = $pre;
				}
			}
		}

		return $cc;
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tsparser_tsconfig.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tsparser_tsconfig.php']);
}
?>
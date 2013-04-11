<?php
namespace TYPO3\CMS\Backend\Configuration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  (c) 2007-2013 Kraft Bernhard (kraftb@kraftb.at)
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
 * @author Kraft Bernhard <kraftb@kraftb.at>
 */
class TsConfigParser extends \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser {

	/**
	 * @var 	array
	 */
	protected $rootLine = array();

	/**
	 * Parses the passed TS-Config using conditions and caching
	 *
	 * @param string $TStext The TSConfig being parsed
	 * @param string $type The type of TSConfig (either "userTS" or "PAGES")
	 * @param integer $id The uid of the page being handled
	 * @param array $rootLine The rootline of the page being handled
	 * @return array Array containing the parsed TSConfig and a flag whether the content was retrieved from cache
	 */
	public function parseTSconfig($TStext, $type, $id = 0, array $rootLine = array()) {
		$this->type = $type;
		$this->id = $id;
		$this->rootLine = $rootLine;
		$hash = md5($type . ':' . $TStext);
		$cachedContent = \TYPO3\CMS\Backend\Utility\BackendUtility::getHash($hash, 0);
		if ($cachedContent) {
			$storedData = unserialize($cachedContent);
			$storedMD5 = substr($cachedContent, -strlen($hash));
			$storedData['match'] = array();
			$storedData = $this->matching($storedData);
			$checkMD5 = md5(serialize($storedData));
			if ($checkMD5 == $storedMD5) {
				$res = array(
					'TSconfig' => $storedData['TSconfig'],
					'cached' => 1
				);
			} else {
				$shash = md5($checkMD5 . $hash);
				$cachedSpec = \TYPO3\CMS\Backend\Utility\BackendUtility::getHash($shash, 0);
				if ($cachedSpec) {
					$storedData = unserialize($cachedSpec);
					$res = array(
						'TSconfig' => $storedData['TSconfig'],
						'cached' => 1
					);
				} else {
					$storeData = $this->parseWithConditions($TStext);
					$serData = serialize($storeData);
					\TYPO3\CMS\Backend\Utility\BackendUtility::storeHash($shash, $serData, $type . '_TSconfig');
					$res = array(
						'TSconfig' => $storeData['TSconfig'],
						'cached' => 0
					);
				}
			}
		} else {
			$storeData = $this->parseWithConditions($TStext);
			$serData = serialize($storeData);
			$md5 = md5($serData);
			\TYPO3\CMS\Backend\Utility\BackendUtility::storeHash($hash, $serData . $md5, $type . '_TSconfig');
			$res = array(
				'TSconfig' => $storeData['TSconfig'],
				'cached' => 0
			);
		}
		return $res;
	}

	/**
	 * Does the actual parsing using the parent objects "parse" method. Creates the match-Object
	 *
	 * @param string $TSconfig The TSConfig being parsed
	 * @return array Array containing the parsed TSConfig, the encountered sectiosn, the matched sections
	 */
	protected function parseWithConditions($TSconfig) {
		/** @var $matchObj \TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher */
		$matchObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Configuration\\TypoScript\\ConditionMatching\\ConditionMatcher');
		$matchObj->setRootline($this->rootLine);
		$matchObj->setPageId($this->id);
		$this->parse($TSconfig, $matchObj);
		$storeData = array(
			'TSconfig' => $this->setup,
			'sections' => $this->sections,
			'match' => $this->sectionsMatch
		);
		return $storeData;
	}

	/**
	 * Is just going through an array of conditions to determine which are matching (for getting correct cache entry)
	 *
	 * @param array $cc An array containing the sections to match
	 * @return array The input array with matching sections filled into the "match" key
	 */
	protected function matching(array $cc) {
		if (is_array($cc['sections'])) {
			/** @var $matchObj \TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher */
			$matchObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Configuration\\TypoScript\\ConditionMatching\\ConditionMatcher');
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


?>
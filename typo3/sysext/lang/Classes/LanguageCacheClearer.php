<?php
namespace TYPO3\CMS\Lang;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Dominique Feyer <dominique.feyer@reelpeek.net>
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
 * Class to clear temp files of htmlArea RTE
 *
 * @author Dominique Feyer <dominique.feyer@reelpeek.net>
 */
class LanguageCacheClearer {

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\StringFrontend
	 */
	protected $cacheInstance;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->initializeCache();
	}

	/**
	 * Initialize cache instance to be ready to use
	 *
	 * @return void
	 */
	protected function initializeCache() {
		$this->cacheInstance = $GLOBALS['typo3CacheManager']->getCache('t3lib_l10n');
	}

	/**
	 * Flush the l10n cache if the clear cache command "all" or "temp_cached" is given.
	 *
	 * @param array $parameters Parameters as defined in DataHandler
	 * @return void
	 */
	public function clearCache(array $parameters) {
		$isValidCall = (
			!empty($parameters['cacheCmd'])
			&& \TYPO3\CMS\Core\Utility\GeneralUtility::inList('all,temp_cached', $parameters['cacheCmd'])
		);

		if (isset($GLOBALS['BE_USER']) && $isValidCall) {
			$GLOBALS['BE_USER']->writelog(3, 1, 0, 0, '[lang]: User %s has cleared the language cache', array($GLOBALS['BE_USER']->user['username']));
			$this->cacheInstance->flush();
		}
	}

}


?>
<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2011 Dominique Feyer <dominique.feyer@reelpeek.net>
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
 * @author	Dominique Feyer <dominique.feyer@reelpeek.net>
 * @package	TYPO3
 * @subpackage tx_Lang
 */
class tx_lang_clearcache {

	/**
	 * @var t3lib_cache_frontend_StringFrontend
	 */
	protected $cacheInstance;

	public function __construct() {
		$this->initializeCache();
	}

	/**
	 * Initialize cache instance to be ready to use
	 *
	 * @return void
	 */
	protected function initializeCache() {
		$this->cacheInstance = $GLOBALS['typo3CacheManager']->getCache('lang_l10n');
	}

	/**
	 * @return void
	 */
	public function clearCache() {
		$this->cacheInstance->flush();
	}

}

?>
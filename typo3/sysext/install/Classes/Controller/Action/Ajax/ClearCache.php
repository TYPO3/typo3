<?php
namespace TYPO3\CMS\Install\Controller\Action\Ajax;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Susanne Moog <typo3@susanne-moog.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Install\Controller\Action;
use TYPO3\CMS\Core\Utility;

/**
 * Clear Cache
 *
 * This is an ajax wrapper for clearing the cache. Used for example
 * after uninstalling an extension via ajax.
 *
 * @see \TYPO3\CMS\Install\Service\ClearCacheService
 */
class ClearCache extends Action\AbstractAction implements Action\ActionInterface {

	/**
	 * Handle this action
	 *
	 * @return string content
	 */
	public function handle() {
		$this->initializeHandle();
		return $this->clearCache();
	}

	/**
	 * Wraps the cache service
	 *
	 * @return string
	 */
	protected function clearCache() {
		/** @var \TYPO3\CMS\Install\Service\ClearCacheService $clearCacheService */
		$clearCacheService = $this->objectManager->get('TYPO3\\CMS\\Install\\Service\\ClearCacheService');
		$clearCacheService->clearAll();
		return 'OK';
	}
}
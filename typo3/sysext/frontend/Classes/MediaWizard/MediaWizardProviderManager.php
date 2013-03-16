<?php
namespace TYPO3\CMS\Frontend\MediaWizard;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Ernesto Baschny <ernst@cron-it.de>
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
 * Manager to register and call registered media wizard providers
 *
 * @author Ernesto Baschny <ernst@cron-it.de>
 * @static
 */
class MediaWizardProviderManager {

	/**
	 * @var array
	 */
	static protected $providers = array();

	/**
	 * Allows extensions to register themselves as media wizard providers
	 *
	 * @param string $className A class implementing tslib_mediaWizardProvider
	 * @return void
	 */
	static public function registerMediaWizardProvider($className) {
		if (!isset(self::$providers[$className])) {
			$provider = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className);
			if (!$provider instanceof \TYPO3\CMS\Frontend\MediaWizard\MediaWizardProviderInterface) {
				throw new \UnexpectedValueException($className . ' is registered as a mediaWizardProvider, so it must implement interface TYPO3\\CMS\\Frontend\\MediaWizard\\MediaWizardProviderInterface', 1285022360);
			}
			self::$providers[$className] = $provider;
		}
	}

	/**
	 * @param string $url
	 * @return A valid mediaWizardProvider that can handle this URL
	 */
	static public function getValidMediaWizardProvider($url) {
		// Go through registered providers in reverse order (last one registered wins)
		$providers = array_reverse(self::$providers, TRUE);
		foreach (self::$providers as $className => $provider) {
			/** @var $provider \TYPO3\CMS\Frontend\MediaWizard\MediaWizardProviderInterface */
			if ($provider->canHandle($url)) {
				return $provider;
			}
		}
		// No provider found
		return NULL;
	}

}


?>
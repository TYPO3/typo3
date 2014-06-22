<?php
namespace TYPO3\CMS\Frontend\MediaWizard;

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
	 * @param string $className A class implementing MediaWizardProviderInterface
	 * @throws \UnexpectedValueException
	 * @return void
	 */
	static public function registerMediaWizardProvider($className) {
		if (!isset(self::$providers[$className])) {
			$provider = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className);
			if (!$provider instanceof MediaWizardProviderInterface) {
				throw new \UnexpectedValueException($className . ' is registered as a mediaWizardProvider, so it must implement interface TYPO3\\CMS\\Frontend\\MediaWizard\\MediaWizardProviderInterface', 1285022360);
			}
			self::$providers[$className] = $provider;
		}
	}

	/**
	 * @param string $url
	 * @return MediaWizardProviderInterface|NULL A valid mediaWizardProvider that can handle this URL
	 */
	static public function getValidMediaWizardProvider($url) {
		// Go through registered providers in reverse order (last one registered wins)
		$providers = array_reverse(self::$providers, TRUE);
		foreach ($providers as $provider) {
			/** @var $provider MediaWizardProviderInterface */
			if ($provider->canHandle($url)) {
				return $provider;
			}
		}
		// No provider found
		return NULL;
	}

}

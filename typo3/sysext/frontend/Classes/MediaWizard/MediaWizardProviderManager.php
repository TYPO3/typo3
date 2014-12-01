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
	 * @var array All class names of registered providers
	 */
	static protected $providers = array();

	/**
	 * @var array Instances of registered providers, set up on first call of getValidMediaWizardProvider()
	 */
	static protected $providerObjects = array();

	/**
	 * Allows extensions to register themselves as media wizard providers
	 *
	 * @param string $className A class implementing MediaWizardProviderInterface
	 * @throws \UnexpectedValueException
	 * @return void
	 */
	static public function registerMediaWizardProvider($className) {
		self::$providers[] = $className;
	}

	/**
	 * Instantiate all registered media wizard providers
	 *
	 * @throws \UnexpectedValueException
	 */
	static protected function instantiateMediaWizardProviders() {
		$providerClassNames = array_unique(self::$providers);
		foreach ($providerClassNames as $className) {
			if (!isset(self::$providerObjects[$className])) {
				$provider = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className);
				if (!$provider instanceof MediaWizardProviderInterface) {
					throw new \UnexpectedValueException($className . ' is registered as a mediaWizardProvider, so it must implement interface ' . \TYPO3\CMS\Frontend\MediaWizard\MediaWizardProviderInterface::class, 1285022360);
				}
				self::$providerObjects[$className] = $provider;
			}
		}
	}

	/**
	 * Return a media wizard provider that can handle given URL
	 *
	 * @param string $url
	 * @return MediaWizardProviderInterface|NULL A valid mediaWizardProvider that can handle this URL
	 */
	static public function getValidMediaWizardProvider($url) {
		self::instantiateMediaWizardProviders();
		// Go through registered providers in reverse order (last one registered wins)
		$providers = array_reverse(self::$providerObjects, TRUE);
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

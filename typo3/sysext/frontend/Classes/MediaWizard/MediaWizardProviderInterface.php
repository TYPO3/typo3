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
 * Interface for classes which hook into tslib_mediawizard adding additional
 * media wizard providers
 *
 * @author Ernesto Baschny <ernst@cron-it.de>
 */
interface MediaWizardProviderInterface {
	/**
	 * Tells the calling party if we can handle the URL passed to the constructor
	 *
	 * @param string $url URL to be handled
	 * @return boolean
	 */
	public function canHandle($url);

	/**
	 * Rewrites a media provider URL into a canonized form that can be embedded
	 *
	 * @param string $url URL to be handled
	 * @return string Canonized URL that can be used to embed
	 */
	public function rewriteUrl($url);

}

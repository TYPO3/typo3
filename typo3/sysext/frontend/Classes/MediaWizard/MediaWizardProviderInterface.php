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
	 * @return string Canonized URL that can be used to embedd
	 */
	public function rewriteUrl($url);

}

?>
<?php
namespace TYPO3\CMS\Frontend\ContentObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Ingo Schmitt <is@marketing-factory.de>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Interface for classes which hook into getSourceCollection for additional processing
 */
interface ContentObjectOneSourceCollectionHookInterface {

	/**
	 * Renders One Source Collection
	 *
	 * @param array $sourceRenderConfiguration Array with TypoScript Properties for the imgResource
	 * @param array $sourceConfiguration
	 * @param string $oneSourceCollection already prerendered SourceCollection
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parentObject Parent content object
	 * @internal param array $configuration Array with the Source Configuration
	 * @return string HTML Content for oneSourceCollection
	 */
	public function getOneSourceCollection(array $sourceRenderConfiguration, array $sourceConfiguration, $oneSourceCollection, \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer &$parentObject);

}
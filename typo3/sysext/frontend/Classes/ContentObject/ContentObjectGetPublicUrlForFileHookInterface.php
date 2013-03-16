<?php
namespace TYPO3\CMS\Frontend\ContentObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Oliver Hader <oliver.hader@typo3.org>
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
 * Interface for hooks to fetch the public URL of files
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
interface ContentObjectGetPublicUrlForFileHookInterface {
	/**
	 * Post-processes a public URL.
	 *
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parent The current content object (context)
	 * @param array $configuration TypoScript configuration
	 * @param \TYPO3\CMS\Core\Resource\File $file The file object to be used
	 * @param string $pubicUrl Reference to the public URL
	 */
	public function postProcess(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parent, array $configuration, \TYPO3\CMS\Core\Resource\File $file, &$pubicUrl);

}

?>
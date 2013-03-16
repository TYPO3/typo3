<?php
namespace TYPO3\CMS\Core\Tests\Functional\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Andreas Wolf <andreas.wolf@typo3.org>
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

require_once 'vfsStream/vfsStream.php';

/**
 * Basic functional test class for the File Abstraction Layer (FAL).
 *
 * @author Andreas Wolf <andreas.wolf@typo3.org>
 */
class BaseTestCase extends \TYPO3\CMS\Core\Tests\Unit\Resource\BaseTestCase {
	public function getStorageObject() {
		$this->initializeVfs();
		$resourceFactory = new \TYPO3\CMS\Core\Resource\ResourceFactory();
		return $resourceFactory->createStorageObject(array(
			'driver' => 'Local'
		), array('basePath' => $this->getMountRootUrl()));
	}
}

?>
<?php
namespace TYPO3\CMS\Extbase\Property\TypeConverter;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 * Converter which transforms simple types to \TYPO3\CMS\Extbase\Domain\Model\FileCollection.
 *
 * @api experimental! This class is experimental and subject to change!
 */
class StaticFileCollectionConverter extends \TYPO3\CMS\Extbase\Property\TypeConverter\AbstractFileCollectionConverter implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('integer');

	/**
	 * @var string
	 */
	protected $targetType = 'TYPO3\\CMS\\Extbase\\Domain\\Model\\StaticFileCollection';

	/**
	 * @var string
	 */
	protected $expectedObjectType = 'TYPO3\\CMS\\Core\\Resource\\Collection\\StaticFileCollection';

	/**
	 * @param integer $source
	 * @return \TYPO3\CMS\Core\Resource\Collection\StaticFileCollection
	 */
	protected function getObject($source) {
		return $this->fileFactory->getCollectionObject($source);
	}
}

?>
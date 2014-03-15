<?php
namespace TYPO3\CMS\Core\Compatibility;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Thomas Maroschik <tmaroschik@dfau.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class to simulate the "old" extension information array element
 *
 * @internal
 */
class LoadedExtensionArrayElement implements \IteratorAggregate, \ArrayAccess, \Serializable, \Countable {

	/**
	 * @var \TYPO3\Flow\Package\PackageInterface Instance of package manager
	 */
	protected $package;

	/**
	 * @var array List of relevant extension files
	 */
	protected $extensionFilesToCheckFor = array(
		'ext_localconf.php',
		'ext_tables.php',
		'ext_tables.sql',
		'ext_tables_static+adt.sql',
		'ext_typoscript_constants.txt',
		'ext_typoscript_setup.txt'
	);

	/**
	 * @var array Final extension information
	 */
	protected $extensionInformation = array();

	/**
	 * Constructor builds compatibility API
	 *
	 * @param \TYPO3\Flow\Package\PackageInterface $package
	 */
	public function __construct(\TYPO3\Flow\Package\PackageInterface $package) {
		$this->package = $package;
		$this->initializeBasicExtensionInformation();
		$this->initializeExtensionFiles();
		$this->initializeExtensionIcon();
	}

	/**
	 * Create main information
	 *
	 * @return void
	 */
	protected function initializeBasicExtensionInformation() {
		$pathSite = PATH_site;
		$pathSiteLength = strlen($pathSite);
		$absolutePackagePath = $this->package->getPackagePath();
		if (substr($absolutePackagePath, 0, $pathSiteLength) === $pathSite) {
			$relativePackagePathToPathSite = substr($absolutePackagePath, $pathSiteLength);
			$relativePackagePathToPathSiteSegments = explode('/', $relativePackagePathToPathSite);
			$relativePackagePathToPathTypo3 = NULL;
			$packageType = NULL;
			// Determine if extension is installed locally, globally or system (in this order)
			switch (implode('/', array_slice($relativePackagePathToPathSiteSegments, 0, 2))) {
				case 'typo3conf/Packages':
					$packageType = 'C';
					$relativePackagePathToPathTypo3 = '../typo3conf/Packages/' . implode('/', array_slice($relativePackagePathToPathSiteSegments, 2));
					break;
				case 'typo3conf/ext':
					$packageType = 'L';
					$relativePackagePathToPathTypo3 = '../typo3conf/ext/' . implode('/', array_slice($relativePackagePathToPathSiteSegments, 2));
					break;
				case TYPO3_mainDir . 'ext':
					$packageType = 'G';
					$relativePackagePathToPathTypo3 = 'ext/' . implode('/', array_slice($relativePackagePathToPathSiteSegments, 2));
					break;
				case TYPO3_mainDir . 'sysext':
					$packageType = 'S';
					$relativePackagePathToPathTypo3 = 'sysext/' . implode('/', array_slice($relativePackagePathToPathSiteSegments, 2));
					break;
				case 'typo3temp/test_ext':
					$packageType = 'T';
					$relativePackagePathToPathTypo3 = '../typo3temp/test_ext/' . implode('/', array_slice($relativePackagePathToPathSiteSegments, 2));
					break;
			}
			if ($packageType !== NULL && $relativePackagePathToPathSite !== NULL && $relativePackagePathToPathTypo3 !== NULL) {
				$this->extensionInformation['type'] = $packageType;
				$this->extensionInformation['siteRelPath'] = $relativePackagePathToPathSite;
				$this->extensionInformation['typo3RelPath'] = $relativePackagePathToPathTypo3;
			}
		}
	}

	/**
	 * Initialize extension icon
	 *
	 * @return void
	 */
	protected function initializeExtensionIcon() {
		$this->extensionInformation['ext_icon'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionIcon($this->package->getPackagePath());
	}

	/**
	 * Register found files in extension array if extension was found
	 *
	 * @param void
	 */
	protected function initializeExtensionFiles() {
		foreach ($this->extensionFilesToCheckFor as $fileName) {
			$absolutePathToFile = $this->package->getPackagePath() . $fileName;
			if (@file_exists($absolutePathToFile)) {
				$this->extensionInformation[$fileName] = $absolutePathToFile;
			}
		}
	}

	/**
	 * Retrieve an external iterator
	 *
	 * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @return \Traversable An instance of an object implementing Iterator or Traversable
	 */
	public function getIterator() {
		return new \ArrayIterator($this->extensionInformation);
	}

	/**
	 * Whether a offset exists
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 * @param mixed $offset An offset to check for.
	 * @return boolean TRUE on success or FALSE on failure.
	 */
	public function offsetExists($offset) {
		return isset($this->extensionInformation[$offset]);
	}

	/**
	 * Offset to retrieve
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 * @param mixed $offset The offset to retrieve.
	 * @return mixed Can return all value types.
	 */
	public function offsetGet($offset) {
		return $this->extensionInformation[$offset];
	}

	/**
	 * Offset to set
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 * @param mixed $offset The offset to assign the value to.
	 * @param mixed $value The value to set.
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function offsetSet($offset, $value) {
		throw new \InvalidArgumentException('The array $GLOBALS[\'TYPO3_LOADED_EXT\'] may not be modified.', 1361915115);
	}

	/**
	 * Offset to unset
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 * @param mixed $offset The offset to unset.
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function offsetUnset($offset) {
		throw new \InvalidArgumentException('The array $GLOBALS[\'TYPO3_LOADED_EXT\'] may not be modified.', 1361915206);
	}

	/**
	 * String representation of object
	 *
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 */
	public function serialize() {
		return serialize($this->extensionInformation);
	}

	/**
	 * Constructs the object
	 *
	 * @link http://php.net/manual/en/serializable.unserialize.php
	 * @param string $serialized The string representation of the object.
	 * @return mixed the original value unserialized.
	 */
	public function unserialize($serialized) {
		$this->extensionInformation = unserialize($serialized);
	}

	/**
	 * Count elements of an object
	 *
	 * @link http://php.net/manual/en/countable.count.php
	 * @return integer The custom count as an integer. The return value is cast to an integer.
	 */
	public function count() {
		return count($this->extensionInformation);
	}

	/**
	 * @return array
	 */
	public function toArray() {
		return iterator_to_array($this);
	}
}

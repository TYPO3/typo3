<?php
namespace TYPO3\CMS\Core\Compatibility;

/*
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

use TYPO3\CMS\Core\Package\PackageInterface;

/**
 * Class to simulate the "old" extension information array element
 *
 * @internal
 */
class LoadedExtensionArrayElement implements \IteratorAggregate, \ArrayAccess, \Serializable, \Countable
{
    /**
     * @var PackageInterface Instance of package manager
     */
    protected $package;

    /**
     * @var array List of relevant extension files
     */
    protected $extensionFilesToCheckFor = [
        'ext_localconf.php',
        'ext_tables.php',
        'ext_tables.sql',
        'ext_tables_static+adt.sql',
        'ext_typoscript_constants.txt',
        'ext_typoscript_setup.txt'
    ];

    /**
     * @var array Final extension information
     */
    protected $extensionInformation = [];

    /**
     * Constructor builds compatibility API
     *
     * @param PackageInterface $package
     */
    public function __construct(PackageInterface $package)
    {
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
    protected function initializeBasicExtensionInformation()
    {
        $pathSite = PATH_site;
        $pathSiteLength = strlen($pathSite);
        $absolutePackagePath = $this->package->getPackagePath();
        if (substr($absolutePackagePath, 0, $pathSiteLength) === $pathSite) {
            $relativePackagePathToPathSite = substr($absolutePackagePath, $pathSiteLength);
            $relativePackagePathToPathSiteSegments = explode('/', $relativePackagePathToPathSite);
            $relativePackagePathToPathTypo3 = null;
            $packageType = null;
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
            if ($packageType !== null && $relativePackagePathToPathSite !== null && $relativePackagePathToPathTypo3 !== null) {
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
    protected function initializeExtensionIcon()
    {
        $this->extensionInformation['ext_icon'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionIcon($this->package->getPackagePath());
    }

    /**
     * Register found files in extension array if extension was found
     *
     * @param void
     */
    protected function initializeExtensionFiles()
    {
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
    public function getIterator()
    {
        return new \ArrayIterator($this->extensionInformation);
    }

    /**
     * Whether an offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset An offset to check for.
     * @return bool TRUE on success or FALSE on failure.
     */
    public function offsetExists($offset)
    {
        return isset($this->extensionInformation[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset The offset to retrieve.
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
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
    public function offsetSet($offset, $value)
    {
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
    public function offsetUnset($offset)
    {
        throw new \InvalidArgumentException('The array $GLOBALS[\'TYPO3_LOADED_EXT\'] may not be modified.', 1361915206);
    }

    /**
     * String representation of object
     *
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return serialize($this->extensionInformation);
    }

    /**
     * Constructs the object
     *
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized The string representation of the object.
     * @return mixed the original value unserialized.
     */
    public function unserialize($serialized)
    {
        $this->extensionInformation = unserialize($serialized);
    }

    /**
     * Count elements of an object
     *
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer. The return value is cast to an integer.
     */
    public function count()
    {
        return count($this->extensionInformation);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return iterator_to_array($this);
    }
}

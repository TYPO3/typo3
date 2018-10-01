<?php
namespace TYPO3\CMS\Extensionmanager\Domain\Model;

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

/**
 * Main extension model
 * @internal This class is a specific domain model implementation and is not part of the Public TYPO3 API.
 */
class Dependency extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string
     */
    protected $identifier = '';

    /**
     * @var string
     */
    protected $lowestVersion = '';

    /**
     * @var string
     */
    protected $highestVersion = '';

    /**
     * @var string
     */
    protected $type = '';

    /**
     * @var array
     */
    protected static $dependencyTypes = [
        'depends',
        'conflicts',
        'suggests'
    ];

    /**
     * @var array
     */
    public static $specialDependencies = [
        'typo3',
        'php'
    ];

    /**
     * @param string $highestVersion
     */
    public function setHighestVersion($highestVersion)
    {
        $this->highestVersion = $highestVersion;
    }

    /**
     * @return string
     */
    public function getHighestVersion()
    {
        return $this->highestVersion;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $lowestVersion
     */
    public function setLowestVersion($lowestVersion)
    {
        $this->lowestVersion = $lowestVersion;
    }

    /**
     * @return string
     */
    public function getLowestVersion()
    {
        return $this->lowestVersion;
    }

    /**
     * @param string $type
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException if no valid dependency type was given
     */
    public function setType($type)
    {
        if (in_array($type, self::$dependencyTypes)) {
            $this->type = $type;
        } else {
            throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException($type . ' was not a valid dependency type.', 1476122402);
        }
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}

<?php
namespace TYPO3\CMS\Lang\Domain\Model;

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
 * Extension model
 */
class Extension extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string
     */
    protected $key = '';

    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $icon = '';

    /**
     * @var int
     */
    protected $iconWidth = 28;

    /**
     * @var int
     */
    protected $iconHeight = 28;

    /**
     * @var int
     */
    protected $version = '';

    /**
     * @var array
     */
    protected $updateResult = [];

    /**
     * Constructor of the extension model.
     *
     * @param string $key The extension key
     * @param string $title Title of the extension
     * @param string $icon Icon representing the extension
     */
    public function __construct($key = '', $title= '', $icon = '')
    {
        $this->setKey($key);
        $this->setTitle($title);
        $this->setIcon($icon);
    }

    /**
     * Setter for the icon
     *
     * @param string $icon ext_icon path relative to typo3 folder like ../typo3conf/ext/extensionkey/ext_icon.png
     * @return void
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * Getter for the icon
     *
     * @return string ext_icon path relative to typo3 folder
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @return int
     */
    public function getIconWidth()
    {
        return $this->iconWidth;
    }

    /**
     * @param int $iconWidth
     */
    public function setIconWidth($iconWidth)
    {
        $this->iconWidth = $iconWidth;
    }

    /**
     * @return int
     */
    public function getIconHeight()
    {
        return $this->iconHeight;
    }

    /**
     * @param int $iconHeight
     */
    public function setIconHeight($iconHeight)
    {
        $this->iconHeight = $iconHeight;
    }

    /**
     * Setter for the key
     *
     * @param string $key
     * @return void
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Getter for the key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Setter for the title
     *
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Getter for the title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Setter for the version
     *
     * @param int $version Needs to have a valid version format like 1003007
     * @return void
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Setter for the version from string
     *
     * @param string $version Needs to have a format like '1.3.7' and converts it into an integer like 1003007 before setting the version
     * @see \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger
     * @return void
     */
    public function setVersionFromString($version)
    {
        $this->version = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger($version);
    }

    /**
     * Getter for the version
     *
     * @return int interpretation of the extension version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Setter for updateResult
     *
     * @param array $updateResult Needs to be in a structure like array('icon' => '', 'message' => '')
     * @return void
     */
    public function setUpdateResult($updateResult)
    {
        $this->updateResult = (array)$updateResult;
    }

    /**
     * Getter for updateResult
     *
     * @return array returns the update result as an array in the structure like array('icon' => '', 'message' => '')
     */
    public function getUpdateResult()
    {
        return $this->updateResult;
    }

    /**
     * Returns an array represetation of current model
     *
     * @return array The properties
     */
    public function toArray()
    {
        return [
            'key'     => $this->getKey(),
            'title'   => $this->getTitle(),
            'icon'    => $this->getIcon(),
            'version' => $this->getVersion(),
            'width'   => $this->getIconWidth(),
            'height'  => $this->getIconHeight()
        ];
    }
}

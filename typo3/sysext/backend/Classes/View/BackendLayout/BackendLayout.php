<?php
namespace TYPO3\CMS\Backend\View\BackendLayout;

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
 * Class to represent a backend layout.
 */
class BackendLayout
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $iconPath;

    /**
     * @var string
     */
    protected $configuration;

    /**
     * @var array
     */
    protected $data;

    /**
     * @param string $identifier
     * @param string $title
     * @param string $configuration
     * @return BackendLayout
     */
    public static function create($identifier, $title, $configuration)
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            self::class,
            $identifier,
            $title,
            $configuration
        );
    }

    /**
     * @param string $identifier
     * @param string $title
     * @param string $configuration
     */
    public function __construct($identifier, $title, $configuration)
    {
        $this->setIdentifier($identifier);
        $this->setTitle($title);
        $this->setConfiguration($configuration);
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @throws \UnexpectedValueException
     */
    public function setIdentifier($identifier)
    {
        if (strpos($identifier, '__') !== false) {
            throw new \UnexpectedValueException(
                'Identifier "' . $identifier . '" must not contain "__"',
                1381597630
            );
        }

        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getIconPath()
    {
        return $this->iconPath;
    }

    /**
     * @param string $iconPath
     */
    public function setIconPath($iconPath)
    {
        $this->iconPath = $iconPath;
    }

    /**
     * @return string
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param string $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }
}

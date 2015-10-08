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
 * Model for configuration sub categories
 *
 * Configuration options can be structured with categories and sub categories.
 * Categories are usually displayed as tabs and sub categories are used to
 * group configuration items in one tab.
 */
class ConfigurationSubcategory extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var string The sub category label
     */
    protected $label = '';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem>
     */
    protected $items;

    /**
     * Constructs this Category
     */
    public function __construct()
    {
        $this->items = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $items
     * @return void
     */
    public function setItems($items)
    {
        $this->items = $items;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Adds a subcategory
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $item
     * @return void
     */
    public function addItem(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $item)
    {
        $this->items->attach($item);
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set sub category label
     *
     * @param string $label
     * @return void
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Get sub category label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }
}

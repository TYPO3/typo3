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
 * Main model for extension configuration categories
 */
class ConfigurationCategory extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationSubcategory>
     */
    protected $subcategories;

    /**
     * @var string
     */
    protected $highlightText = '';

    /**
     * Constructs this Category
     */
    public function __construct()
    {
        $this->subcategories = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $subcategories
     * @return void
     */
    public function setSubcategories($subcategories)
    {
        $this->subcategories = $subcategories;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getSubcategories()
    {
        return $this->subcategories;
    }

    /**
     * Adds a subcategories
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationSubcategory $subcategory
     * @return void
     */
    public function addSubcategory(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationSubcategory $subcategory)
    {
        $this->subcategories->attach($subcategory);
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
     * @param string $highlightText
     * @return void
     */
    public function setHighlightText($highlightText)
    {
        $this->highlightText = $highlightText;
    }

    /**
     * @return string
     */
    public function getHighlightText()
    {
        return $this->highlightText;
    }
}

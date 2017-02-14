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
 * Model for extension configuration items
 */
class ConfigurationItem extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string
     */
    protected $category = '';

    /**
     * @var string
     */
    protected $subCategory = '';

    /**
     * @var string
     */
    protected $type = '';

    /**
     * @var string
     */
    protected $labelHeadline = '';

    /**
     * @var string
     */
    protected $labelText = '';

    /**
     * @var mixed
     */
    protected $generic = '';

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var string
     */
    protected $value = '';

    /**
     * @var int
     */
    protected $highlight = 0;

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param string $labelHeadline
     */
    public function setLabelHeadline($labelHeadline)
    {
        $this->labelHeadline = $labelHeadline;
    }

    /**
     * @return string
     */
    public function getLabelHeadline()
    {
        return $this->labelHeadline;
    }

    /**
     * @param string $labelText
     */
    public function setLabelText($labelText)
    {
        $this->labelText = $labelText;
    }

    /**
     * @return string
     */
    public function getLabelText()
    {
        return $this->labelText;
    }

    /**
     * @param string $subCategory
     */
    public function setSubCategory($subCategory)
    {
        $this->subCategory = $subCategory;
    }

    /**
     * @return string
     */
    public function getSubCategory()
    {
        return $this->subCategory;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $userFunc
     */
    public function setGeneric($userFunc)
    {
        $this->generic = $userFunc;
    }

    /**
     * @return mixed
     */
    public function getGeneric()
    {
        return $this->generic;
    }

    /**
     * @param string $name
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
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param int $highlight
     */
    public function setHighlight($highlight)
    {
        $this->highlight = $highlight;
    }

    /**
     * @return int
     */
    public function getHighlight()
    {
        return $this->highlight;
    }
}

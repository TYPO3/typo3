<?php
namespace TYPO3\CMS\Extbase\Domain\Model;

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

use TYPO3\CMS\Extbase\Annotation as Extbase;

/**
 * This model represents a category (for anything).
 */
class Category extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string
     * @Extbase\Validate("NotEmpty")
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\Category|null
     * @Extbase\ORM\Lazy
     */
    protected $parent;

    /**
     * Gets the title.
     *
     * @return string the title, might be empty
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title.
     *
     * @param string $title the title to set, may be empty
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Gets the description.
     *
     * @return string the description, might be empty
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the description.
     *
     * @param string $description the description to set, may be empty
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Gets the parent category.
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\Category|null the parent category
     */
    public function getParent()
    {
        if ($this->parent instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {
            $this->parent->_loadRealInstance();
        }
        return $this->parent;
    }

    /**
     * Sets the parent category.
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\Category $parent the parent category
     */
    public function setParent(\TYPO3\CMS\Extbase\Domain\Model\Category $parent)
    {
        $this->parent = $parent;
    }
}

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

/**
 * This model represents a category (for anything).
 *
 * @api
 */
class Category extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string
     * @validate notEmpty
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var string
     */
    protected $icon = '';

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\Category|null
     * @lazy
     */
    protected $parent = null;

    /**
     * Gets the title.
     *
     * @return string the title, might be empty
     * @api
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title.
     *
     * @param string $title the title to set, may be empty
     * @api
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Gets the description.
     *
     * @return string the description, might be empty
     * @api
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the description.
     *
     * @param string $description the description to set, may be empty
     * @api
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Returns the icon
     *
     * @return string $icon
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function getIcon()
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
        return $this->icon;
    }

    /**
     * Sets the icon
     *
     * @param string $icon
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function setIcon($icon)
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
        $this->icon = $icon;
    }

    /**
     * Gets the parent category.
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\Category|null the parent category
     * @api
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
     * @api
     */
    public function setParent(\TYPO3\CMS\Extbase\Domain\Model\Category $parent)
    {
        $this->parent = $parent;
    }
}

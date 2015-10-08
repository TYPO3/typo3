<?php
namespace TYPO3\CMS\Belog\Domain\Model;

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
 * Stub model for sys history - only properties required for belog module are added currently
 */
class HistoryEntry extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * List of changed fields
     *
     * @var string
     */
    protected $fieldlist = '';

    /**
     * Set list of changed fields
     *
     * @param string $fieldlist
     * @return void
     */
    public function setFieldlist($fieldlist)
    {
        // @todo think about exploding this to an array
        $this->fieldlist = $fieldlist;
    }

    /**
     * Get field list
     *
     * @return string
     */
    public function getFieldlist()
    {
        return $this->fieldlist;
    }
}

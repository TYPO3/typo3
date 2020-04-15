<?php

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

namespace TYPO3\CMS\Extbase\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * A file object (File Abstraction Layer)
 *
 * @internal experimental! This class is experimental and subject to change!
 * @deprecated since TYPO3 10.4, will be removed in version 11.0
 */
abstract class AbstractFileCollection extends AbstractEntity
{
    public function __construct()
    {
        trigger_error(
            __CLASS__ . ' is deprecated since TYPO3 10.4 and will be removed in version 11.0',
            E_USER_DEPRECATED
        );
    }

    /**
     * @var \TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection
     */
    protected $object;

    /**
     * @param \TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection $object
     */
    public function setObject(\TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection $object)
    {
        $this->object = $object;
    }

    /**
     * @return \TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection
     */
    public function getObject()
    {
        return $this->object;
    }
}

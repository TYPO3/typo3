<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Database\Query\Restriction;

/**
 * Interface that all restriction collections must implement.
 * It is an extension of the QueryRestrictionInterface, so collections can be treated as single restriction
 */
interface QueryRestrictionContainerInterface extends QueryRestrictionInterface
{
    /**
     * Removes all restrictions stored within this container
     *
     * @return QueryRestrictionContainerInterface
     */
    public function removeAll();

    /**
     * Remove restriction of a given type
     *
     * @param string $restrictionType Class name of the restriction to be removed
     * @return QueryRestrictionContainerInterface
     */
    public function removeByType(string $restrictionType);

    /**
     * Add a new restriction instance to this collection
     *
     * @param QueryRestrictionInterface $restriction
     * @return QueryRestrictionContainerInterface
     */
    public function add(QueryRestrictionInterface $restriction);
}

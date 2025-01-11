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

namespace TYPO3\CMS\Backend\Tree\Repository;

use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * Listeners to this event will be able to modify the search parts, to be used to filter the page tree
 */
final class BeforePageTreeIsFilteredEvent
{
    public function __construct(
        /** @param CompositeExpression $searchParts The search parts to be used for filtering */
        public CompositeExpression $searchParts,
        /** @param int[] $searchUids The UIDs to be used for filtering by a special search part, which is added by Core always after listener evaluation */
        public array $searchUids,
        /** @param string $searchPhrase The complete search phrase, as entered by the user */
        public readonly string $searchPhrase,
        /** @param QueryBuilder $queryBuilder This instance is provided for context and to simplify the creation of the search parts and must not be manipulated by listeners */
        public readonly QueryBuilder $queryBuilder,
    ) {}
}

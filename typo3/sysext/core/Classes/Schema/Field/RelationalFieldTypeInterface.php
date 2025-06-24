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

namespace TYPO3\CMS\Core\Schema\Field;

use TYPO3\CMS\Core\Schema\ActiveRelation;
use TYPO3\CMS\Core\Schema\RelationshipType;

/**
 * Interface for a schema field that has a relation to somewhere else.
 */
interface RelationalFieldTypeInterface
{
    /**
     * @return ActiveRelation[]
     */
    public function getRelations(): array;

    public function getRelationshipType(): RelationshipType;
}

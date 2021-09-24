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
 * This is the container with restrictions, that are added to any doctrine query
 */
class DefaultRestrictionContainer extends AbstractRestrictionContainer
{
    /**
     * Default restriction classes.
     *
     * @var string[]
     */
    protected $defaultRestrictionTypes = [
        DeletedRestriction::class,
        HiddenRestriction::class,
        StartTimeRestriction::class,
        EndTimeRestriction::class,
    ];

    /**
     * Creates instances of the registered default restriction classes
     */
    public function __construct()
    {
        foreach ($this->defaultRestrictionTypes as $restrictionType) {
            $this->add($this->createRestriction($restrictionType));
        }
    }
}

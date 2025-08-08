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

namespace TYPO3\CMS\Extbase\Attribute\ORM;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Cascade
{
    /**
     * @var 'remove'|null
     */
    public readonly ?string $value;

    /**
     * Currently, Extbase does only support "remove".
     *
     * Other possible cascade operations would be: "persist", "merge", "detach", "refresh", "all"
     * @see http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/working-with-associations.html#transitive-persistence-cascade-operations
     *
     * @param 'remove'|array{value?: 'remove'}|null $value
     */
    public function __construct(
        // @todo Convert to ?string and use CPP with TYPO3 v15.0
        string|array|null $value = null,
    ) {
        // @todo Remove with TYPO3 v15.0
        if (is_array($value)) {
            trigger_error(
                'Passing an array of configuration values to Extbase attributes will be removed in TYPO3 v15.0. ' .
                'Use explicit constructor parameters instead.',
                E_USER_DEPRECATED,
            );

            $values = $value;

            $this->value = $values['value'] ?? null;
        } else {
            $this->value = $value;
        }
    }
}

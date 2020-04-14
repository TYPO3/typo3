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

namespace TYPO3\CMS\Extbase\Annotation\ORM;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Cascade
{
    /**
     * @Enum({"remove"})
     *
     * Currently, Extbase does only support "remove".
     *
     * Other possible cascade operations would be: "persist", "merge", "detach", "refresh", "all"
     * @see http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/working-with-associations.html#transitive-persistence-cascade-operations
     */
    public $value;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (isset($values['value'])) {
            $this->value = $values['value'];
        }
    }
}

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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator;

/**
 * Interface for field value generators
 *
 * @internal
 */
interface FieldGeneratorInterface
{
    /**
     * Return true if this FieldGenerator matches
     *
     * @param array $data See RecordData generate() for details on this array
     */
    public function match(array $data): bool;

    /**
     * Returns the generated value to be inserted into DB for this field
     */
    public function generate(array $data): string|int|null|array;
}

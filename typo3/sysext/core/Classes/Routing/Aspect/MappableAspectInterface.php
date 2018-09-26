<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Routing\Aspect;

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
 * Aspects that have a mapping table (either static, or in the database).
 */
interface MappableAspectInterface extends AspectInterface
{
    /**
     * @param string $value
     * @return string|null
     */
    public function generate(string $value): ?string;

    /**
     * @param string $value
     * @return string|null
     */
    public function resolve(string $value): ?string;
}

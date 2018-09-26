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
 * Interface that describes delegations of tasks to different processors
 * when resolving or generating parameters for URLs.
 */
interface DelegateInterface
{
    /**
     * Determines whether the given value can be resolved.
     *
     * @param array $values
     * @return bool
     */
    public function exists(array $values): bool;

    /**
     * Resolves system-internal value of parameter value submitted in URL.
     *
     * @param array $values
     * @return array|null
     */
    public function resolve(array $values): ?array;

    /**
     * Generates URL parameter value from system-internal value.
     *
     * @param array $values
     * @return array|null
     */
    public function generate(array $values): ?array;
}

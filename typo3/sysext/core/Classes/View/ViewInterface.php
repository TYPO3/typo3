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

namespace TYPO3\CMS\Core\View;

/**
 * A generic view interface.
 */
interface ViewInterface
{
    /**
     * Add a variable to the view data collection.
     */
    public function assign(string $key, mixed $value): self;

    /**
     * Add multiple variables to the view data collection.
     *
     * @param array<string, mixed> $values Array of string keys with mixed-type values.
     */
    public function assignMultiple(array $values): self;

    /**
     * Renders the view. Optionally receives a template location.
     */
    public function render(string $templateFileName = ''): string;
}

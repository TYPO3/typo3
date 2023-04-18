<?php

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

namespace TYPO3\CMS\Backend\Form;

/**
 * Interface must be implemented by node resolver classes
 */
interface NodeResolverInterface
{
    /**
     * Retrieve the current data array from NodeFactory.
     *
     * @todo: Enable this interface method in v13.
     */
    // public function setData(array $data): void;

    /**
     * Main resolver method
     *
     * @return string|null New class name or null if this resolver does not change current class name.
     * @todo: Change to "public function resolve(): ?string;" in v13.
     */
    public function resolve();
}

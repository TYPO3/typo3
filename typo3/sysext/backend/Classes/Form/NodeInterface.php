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
 * Interface must be implemented by all container and widget classes
 */
interface NodeInterface
{
    /**
     * Retrieve the current data array from NodeFactory.
     *
     * @todo: Enable this interface method in v13. Enable implementation in AbstractNode.
     *        Also, add a compiler pass to register all classes implementing
     *        NodeInterface as public in v13.
     */
    // public function setData(array $data): void;

    /**
     * Main render method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     * @todo: Change to "public function render(): array;" in v13.
     * @todo: Declare most (if not all) implementing non-abstract core classes final in v13.
     */
    public function render();
}

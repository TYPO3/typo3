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
     * Main data array is received by NodeFactory
     *
     * @param NodeFactory $nodeFactory
     * @param array $data Main data array
     */
    public function __construct(NodeFactory $nodeFactory, array $data);

    /**
     * Main resolver method
     *
     * @return string|void New class name or void if this resolver does not change current class name.
     */
    public function resolve();
}

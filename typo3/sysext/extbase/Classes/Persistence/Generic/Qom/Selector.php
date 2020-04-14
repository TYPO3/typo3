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

namespace TYPO3\CMS\Extbase\Persistence\Generic\Qom;

/**
 * Selects a subset of the nodes in the repository based on node type.
 *
 * A selector selects every node in the repository, subject to access control
 * constraints, that satisfies at least one of the following conditions:
 *
 * the node's primary node type is nodeType, or
 * the node's primary node type is a subtype of nodeType, or
 * the node has a mixin node type that is nodeType, or
 * the node has a mixin node type that is a subtype of nodeType.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class Selector implements SelectorInterface
{
    /**
     * @var string
     */
    protected $nodeTypeName;

    /**
     * @var string
     */
    protected $selectorName;

    /**
     * Constructs the Selector instance
     *
     * @param string $selectorName
     * @param string $nodeTypeName
     */
    public function __construct($selectorName, $nodeTypeName)
    {
        $this->selectorName = $selectorName;
        $this->nodeTypeName = $nodeTypeName;
    }

    /**
     * Gets the name of the required node type.
     *
     * @return string the node type name; non-null
     */
    public function getNodeTypeName()
    {
        return $this->nodeTypeName;
    }

    /**
     * Gets the selector name.
     * A selector's name can be used elsewhere in the query to identify the selector.
     *
     * @return string the selector name; non-null
     */
    public function getSelectorName()
    {
        return $this->selectorName;
    }
}

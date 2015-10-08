<?php
namespace TYPO3\CMS\Install\FolderStructure;

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
 * Structure facade, a facade class in front of root node.
 * This is the main API interface to the node structure and should
 * be the only class used from outside.
 *
 * @api
 */
class StructureFacade implements StructureFacadeInterface
{
    /**
     * @var RootNodeInterface The structure to work on
     */
    protected $structure;

    /**
     * Constructor sets structure to work on
     *
     * @param RootNodeInterface $structure
     */
    public function __construct(RootNodeInterface $structure)
    {
        $this->structure = $structure;
    }

    /**
     * Get status of node tree
     *
     * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
     */
    public function getStatus()
    {
        return $this->structure->getStatus();
    }

    /**
     * Fix structure
     *
     * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
     */
    public function fix()
    {
        return $this->structure->fix();
    }
}

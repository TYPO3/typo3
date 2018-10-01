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

use TYPO3\CMS\Core\Messaging\FlashMessage;

/**
 * Root node of structure
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class RootNode extends DirectoryNode implements RootNodeInterface
{
    /**
     * Implement constructor
     *
     * @param array $structure Given structure
     * @param NodeInterface $parent Must be NULL for RootNode
     * @throws Exception\RootNodeException
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(array $structure, NodeInterface $parent = null)
    {
        if ($parent !== null) {
            throw new Exception\RootNodeException(
                'Root node must not have parent',
                1366140117
            );
        }

        if (!isset($structure['name'])
            || ($this->isWindowsOs() && substr($structure['name'], 1, 2) !== ':/')
            || (!$this->isWindowsOs() && $structure['name'][0] !== '/')
        ) {
            throw new Exception\InvalidArgumentException(
                'Root node expects absolute path as name',
                1366141329
            );
        }
        $this->name = $structure['name'];

        if (isset($structure['targetPermission'])) {
            $this->setTargetPermission($structure['targetPermission']);
        }

        if (array_key_exists('children', $structure)) {
            $this->createChildren($structure['children']);
        }
    }

    /**
     * Get own status and status of child objects - Root node gives error status if not exists
     *
     * @return FlashMessage[]
     */
    public function getStatus(): array
    {
        $result = [];
        if (!$this->exists()) {
            $result[] = new FlashMessage(
                '',
                $this->getAbsolutePath() . ' does not exist',
                FlashMessage::ERROR
            );
        } else {
            $result = $this->getSelfStatus();
        }
        $result = array_merge($result, $this->getChildrenStatus());
        return $result;
    }

    /**
     * Root node does not call parent, but returns own name only
     *
     * @return string Absolute path
     */
    public function getAbsolutePath()
    {
        return $this->name;
    }
}

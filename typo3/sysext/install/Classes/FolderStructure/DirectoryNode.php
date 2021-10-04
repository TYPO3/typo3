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

namespace TYPO3\CMS\Install\FolderStructure;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;

/**
 * A directory
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class DirectoryNode extends AbstractNode implements NodeInterface
{
    /**
     * @var string Default for directories is octal 02775 == decimal 1533
     */
    protected $targetPermission = '2775';

    /**
     * Implement constructor
     *
     * @param array $structure Structure array
     * @param NodeInterface $parent Parent object
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(array $structure, NodeInterface $parent = null)
    {
        if ($parent === null) {
            throw new InvalidArgumentException(
                'Node must have parent',
                1366222203
            );
        }
        $this->parent = $parent;

        // Ensure name is a single segment, but not a path like foo/bar or an absolute path /foo
        if (str_contains($structure['name'], '/')) {
            throw new InvalidArgumentException(
                'Directory name must not contain forward slash',
                1366226639
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
     * Get own status and status of child objects
     *
     * @return FlashMessage[]
     */
    public function getStatus(): array
    {
        $result = [];
        if (!$this->exists()) {
            $status = new FlashMessage(
                'The Install Tool can try to create it',
                'Directory ' . $this->getRelativePathBelowSiteRoot() . ' does not exist',
                FlashMessage::WARNING
            );
            $result[] = $status;
        } else {
            $result = $this->getSelfStatus();
        }
        $result = array_merge($result, $this->getChildrenStatus());
        return $result;
    }

    /**
     * Create a test file and delete again if directory exists
     *
     * @return bool TRUE if test file creation was successful
     */
    public function isWritable()
    {
        $result = true;
        if (!$this->exists()) {
            $result = false;
        } elseif (!$this->canFileBeCreated()) {
            $result = false;
        }
        return $result;
    }

    /**
     * Fix structure
     *
     * If there is nothing to fix, returns an empty array
     *
     * @return FlashMessage[]
     */
    public function fix(): array
    {
        $result = $this->fixSelf();
        foreach ($this->children as $child) {
            /** @var NodeInterface $child */
            $result = array_merge($result, $child->fix());
        }
        return $result;
    }

    /**
     * Fix this directory:
     *
     * - create with correct permissions if it was not existing
     * - if there is no "write" permissions, try to fix it
     * - leave it alone otherwise
     *
     * @return FlashMessage[]
     */
    protected function fixSelf()
    {
        $result = [];
        if (!$this->exists()) {
            $resultCreateDirectory = $this->createDirectory();
            $result[] = $resultCreateDirectory;
            if ($resultCreateDirectory->getSeverity() === FlashMessage::OK &&
                !$this->isPermissionCorrect()
            ) {
                $result[] = $this->fixPermission();
            }
        } elseif (!$this->isWritable()) {
            // If directory is not writable, we might have permissions to fix that
            // Try it:
            $result[] = $this->fixPermission();
        } elseif (!$this->isDirectory()) {
            $fileType = @filetype($this->getAbsolutePath());
            if ($fileType) {
                $messageBody =
                    'The target ' . $this->getRelativePathBelowSiteRoot() . ' should be a directory,' .
                    ' but is of type ' . $fileType . '. This cannot be fixed automatically. Please investigate.'
                ;
            } else {
                $messageBody =
                    'The target ' . $this->getRelativePathBelowSiteRoot() . ' should be a directory,' .
                    ' but is of unknown type, probably because an upper level directory does not exist. Please investigate.'
                ;
            }
            $result[] = new FlashMessage(
                $messageBody,
                'Path ' . $this->getRelativePathBelowSiteRoot() . ' is not a directory',
                FlashMessage::ERROR
            );
        }
        return $result;
    }

    /**
     * Create directory if not exists
     *
     * @throws Exception
     * @return FlashMessage
     */
    protected function createDirectory(): FlashMessage
    {
        if ($this->exists()) {
            throw new Exception(
                'Directory ' . $this->getAbsolutePath() . ' already exists',
                1366740091
            );
        }
        $result = @mkdir($this->getAbsolutePath());
        if ($result === true) {
            return new FlashMessage(
                '',
                'Directory ' . $this->getRelativePathBelowSiteRoot() . ' successfully created.'
            );
        }
        return new FlashMessage(
            'The target directory could not be created. There is probably a'
                . ' group or owner permission problem on the parent directory.',
            'Directory ' . $this->getRelativePathBelowSiteRoot() . ' not created!',
            FlashMessage::ERROR
        );
    }

    /**
     * Get status of directory - used in root and directory node
     *
     * @return FlashMessage[]
     */
    protected function getSelfStatus(): array
    {
        $result = [];
        if (!$this->isDirectory()) {
            $result[] = new FlashMessage(
                'Directory ' . $this->getRelativePathBelowSiteRoot() . ' should be a directory,'
                    . ' but is of type ' . filetype($this->getAbsolutePath()),
                $this->getRelativePathBelowSiteRoot() . ' is not a directory',
                FlashMessage::ERROR
            );
        } elseif (!$this->isWritable()) {
            $result[] = new FlashMessage(
                'Path ' . $this->getAbsolutePath() . ' exists, but no file underneath it'
                    . ' can be created.',
                'Directory ' . $this->getRelativePathBelowSiteRoot() . ' is not writable',
                FlashMessage::ERROR
            );
        } elseif (!$this->isPermissionCorrect()) {
            $result[] = new FlashMessage(
                'Default configured permissions are ' . $this->getTargetPermission()
                    . ' but current permissions are ' . $this->getCurrentPermission(),
                'Directory ' . $this->getRelativePathBelowSiteRoot() . ' permissions mismatch',
                FlashMessage::NOTICE
            );
        } else {
            $result[] = new FlashMessage(
                'Is a directory with the configured permissions of ' . $this->getTargetPermission(),
                'Directory ' . $this->getRelativePathBelowSiteRoot()
            );
        }
        return $result;
    }

    /**
     * Get status of children
     *
     * @return FlashMessage[]
     */
    protected function getChildrenStatus(): array
    {
        $result = [];
        foreach ($this->children as $child) {
            /** @var NodeInterface $child */
            $result = array_merge($result, $child->getStatus());
        }
        return $result;
    }

    /**
     * Create a test file and delete again - helper for isWritable
     *
     * @return bool TRUE if test file creation was successful
     */
    protected function canFileBeCreated()
    {
        $testFileName = StringUtility::getUniqueId('installToolTest_');
        $result = @touch($this->getAbsolutePath() . '/' . $testFileName);
        if ($result === true) {
            unlink($this->getAbsolutePath() . '/' . $testFileName);
        }
        return $result;
    }

    /**
     * Checks if not is a directory
     *
     * @return bool True if node is a directory
     */
    protected function isDirectory()
    {
        $path = $this->getAbsolutePath();
        return !@is_link($path) && @is_dir($path);
    }

    /**
     * Create children nodes - done in directory and root node
     *
     * @param array $structure Array of children
     * @throws Exception\InvalidArgumentException
     */
    protected function createChildren(array $structure)
    {
        foreach ($structure as $child) {
            if (!array_key_exists('type', $child)) {
                throw new InvalidArgumentException(
                    'Child must have type',
                    1366222204
                );
            }
            if (!array_key_exists('name', $child)) {
                throw new InvalidArgumentException(
                    'Child must have name',
                    1366222205
                );
            }
            $name = $child['name'];
            foreach ($this->children as $existingChild) {
                /** @var NodeInterface $existingChild */
                if ($existingChild->getName() === $name) {
                    throw new InvalidArgumentException(
                        'Child name must be unique',
                        1366222206
                    );
                }
            }
            $this->children[] = new $child['type']($child, $this);
        }
    }
}

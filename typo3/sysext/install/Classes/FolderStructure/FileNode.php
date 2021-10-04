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
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;

/**
 * A file
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class FileNode extends AbstractNode implements NodeInterface
{
    /**
     * @var string Default for files is octal 0664 == decimal 436
     */
    protected $targetPermission = '0664';

    /**
     * @var string|null Target content of file. If NULL, target content is ignored
     */
    protected $targetContent;

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
                'File node must have parent',
                1366927513
            );
        }
        $this->parent = $parent;

        // Ensure name is a single segment, but not a path like foo/bar or an absolute path /foo
        if (str_contains($structure['name'], '/')) {
            throw new InvalidArgumentException(
                'File name must not contain forward slash',
                1366222207
            );
        }
        $this->name = $structure['name'];

        if (isset($structure['targetPermission'])) {
            $this->setTargetPermission($structure['targetPermission']);
        }

        if (isset($structure['targetContent']) && isset($structure['targetContentFile'])) {
            throw new InvalidArgumentException(
                'Either targetContent or targetContentFile can be set, but not both',
                1380364361
            );
        }

        if (isset($structure['targetContent'])) {
            $this->targetContent = $structure['targetContent'];
        }
        if (isset($structure['targetContentFile'])) {
            if (!is_readable($structure['targetContentFile'])) {
                throw new InvalidArgumentException(
                    'targetContentFile ' . $structure['targetContentFile'] . ' does not exist or is not readable',
                    1380364362
                );
            }
            $fileContent = file_get_contents($structure['targetContentFile']);
            if ($fileContent === false) {
                throw new InvalidArgumentException(
                    'Error while reading targetContentFile ' . $structure['targetContentFile'],
                    1380364363
                );
            }
            $this->targetContent = $fileContent;
        }
    }

    /**
     * Get own status
     * Returns warning if file not exists
     * Returns error if file exists but content is not as expected (can / shouldn't be fixed)
     *
     * @return FlashMessage[]
     */
    public function getStatus(): array
    {
        $result = [];
        if (!$this->exists()) {
            $result[] = new FlashMessage(
                'By using "Try to fix errors" we can try to create it',
                'File ' . $this->getRelativePathBelowSiteRoot() . ' does not exist',
                FlashMessage::WARNING
            );
        } else {
            $result = $this->getSelfStatus();
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
        return $result;
    }

    /**
     * Fix this node: create if not there, fix permissions
     *
     * @return FlashMessage[]
     */
    protected function fixSelf(): array
    {
        $result = [];
        if (!$this->exists()) {
            $resultCreateFile = $this->createFile();
            $result[] = $resultCreateFile;
            if ($resultCreateFile->getSeverity() === FlashMessage::OK
                && $this->targetContent !== null
            ) {
                $result[] = $this->setContent();
                if (!$this->isPermissionCorrect()) {
                    $result[] = $this->fixPermission();
                }
            }
        } elseif (!$this->isFile()) {
            $fileType = @filetype($this->getAbsolutePath());
            if ($fileType) {
                $messageBody =
                    'The target ' . $this->getRelativePathBelowSiteRoot() . ' should be a file,' .
                    ' but is of type ' . $fileType . '. This cannot be fixed automatically. Please investigate.'
                ;
            } else {
                $messageBody =
                    'The target ' . $this->getRelativePathBelowSiteRoot() . ' should be a file,' .
                    ' but is of unknown type, probably because an upper level directory does not exist. Please investigate.'
                ;
            }
            $result[] = new FlashMessage(
                $messageBody,
                'Path ' . $this->getRelativePathBelowSiteRoot() . ' is not a file',
                FlashMessage::ERROR
            );
        } elseif (!$this->isPermissionCorrect()) {
            $result[] = $this->fixPermission();
        }
        return $result;
    }

    /**
     * Create file if not exists
     *
     * @throws Exception
     * @return FlashMessage
     */
    protected function createFile(): FlashMessage
    {
        if ($this->exists()) {
            throw new Exception(
                'File ' . $this->getRelativePathBelowSiteRoot() . ' already exists',
                1367048077
            );
        }
        $result = @touch($this->getAbsolutePath());
        if ($result === true) {
            return new FlashMessage(
                '',
                'File ' . $this->getRelativePathBelowSiteRoot() . ' successfully created.'
            );
        }
        return new FlashMessage(
            'The target file could not be created. There is probably a'
                . ' group or owner permission problem on the parent directory.',
            'File ' . $this->getRelativePathBelowSiteRoot() . ' not created!',
            FlashMessage::ERROR
        );
    }

    /**
     * Get status of file
     *
     * @return FlashMessage[]
     */
    protected function getSelfStatus(): array
    {
        $result = [];
        if (!$this->isFile()) {
            $result[] = new FlashMessage(
                'Path ' . $this->getAbsolutePath() . ' should be a file,'
                    . ' but is of type ' . filetype($this->getAbsolutePath()),
                $this->getRelativePathBelowSiteRoot() . ' is not a file',
                FlashMessage::ERROR
            );
        } elseif (!$this->isWritable()) {
            $result[] = new FlashMessage(
                'File ' . $this->getRelativePathBelowSiteRoot() . ' exists, but is not writable.',
                'File ' . $this->getRelativePathBelowSiteRoot() . ' is not writable',
                FlashMessage::NOTICE
            );
        } elseif (!$this->isPermissionCorrect()) {
            $result[] = new FlashMessage(
                'Default configured permissions are ' . $this->getTargetPermission()
                    . ' but file permissions are ' . $this->getCurrentPermission(),
                'File ' . $this->getRelativePathBelowSiteRoot() . ' permissions mismatch',
                FlashMessage::NOTICE
            );
        }
        if ($this->isFile() && !$this->isContentCorrect()) {
            $result[] = new FlashMessage(
                'File content is not identical to default content. This file may have been changed manually.'
                    . ' The Install Tool will not overwrite the current version!',
                'File ' . $this->getRelativePathBelowSiteRoot() . ' content differs',
                FlashMessage::NOTICE
            );
        } else {
            $result[] = new FlashMessage(
                'Is a file with the default content and configured permissions of ' . $this->getTargetPermission(),
                'File ' . $this->getRelativePathBelowSiteRoot()
            );
        }
        return $result;
    }

    /**
     * Compare current file content with target file content
     *
     * @throws Exception If file does not exist
     * @return bool TRUE if current and target file content are identical
     */
    protected function isContentCorrect()
    {
        $absolutePath = $this->getAbsolutePath();
        if (is_link($absolutePath) || !is_file($absolutePath)) {
            throw new Exception(
                'File ' . $absolutePath . ' must exist',
                1367056363
            );
        }
        $result = false;
        if ($this->targetContent === null) {
            $result = true;
        } else {
            $targetContentHash = md5($this->targetContent);
            $currentContentHash = md5((string)file_get_contents($absolutePath));
            if ($targetContentHash === $currentContentHash) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * Sets content of file to target content
     *
     * @throws Exception If file does not exist
     * @return FlashMessage
     */
    protected function setContent(): FlashMessage
    {
        $absolutePath = $this->getAbsolutePath();
        if (is_link($absolutePath) || !is_file($absolutePath)) {
            throw new Exception(
                'File ' . $absolutePath . ' must exist',
                1367060201
            );
        }
        if ($this->targetContent === null) {
            throw new Exception(
                'Target content not defined for ' . $absolutePath,
                1367060202
            );
        }
        $result = @file_put_contents($absolutePath, $this->targetContent);
        if ($result !== false) {
            return new FlashMessage(
                '',
                'Set content to ' . $this->getRelativePathBelowSiteRoot()
            );
        }
        return new FlashMessage(
            'Setting content of the file failed for unknown reasons.',
            'Setting content to ' . $this->getRelativePathBelowSiteRoot() . ' failed',
            FlashMessage::ERROR
        );
    }

    /**
     * Checks if not is a file
     *
     * @return bool
     */
    protected function isFile()
    {
        $path = $this->getAbsolutePath();
        return !is_link($path) && is_file($path);
    }
}

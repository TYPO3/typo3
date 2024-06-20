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
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * A directory but a link is ok as well
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class LinkOrDirectoryNode extends DirectoryNode
{
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
                'Directory ' . $this->getRelativePathBelowSiteRoot() . ' should be a directory or link,'
                    . ' but is of type ' . filetype($this->getAbsolutePath()),
                $this->getRelativePathBelowSiteRoot() . ' is not a directory',
                ContextualFeedbackSeverity::ERROR
            );
        } elseif (!$this->isWritable()) {
            $result[] = new FlashMessage(
                'Path ' . $this->getAbsolutePath() . ' exists, but no file underneath it'
                    . ' can be created.',
                'Directory ' . $this->getRelativePathBelowSiteRoot() . ' is not writable',
                ContextualFeedbackSeverity::ERROR
            );
        } elseif (!$this->isPermissionCorrect()) {
            $result[] = new FlashMessage(
                'Default configured permissions are ' . $this->getTargetPermission()
                    . ' but current permissions are ' . $this->getCurrentPermission(),
                'Directory ' . $this->getRelativePathBelowSiteRoot() . ' permissions mismatch',
                ContextualFeedbackSeverity::NOTICE
            );
        } else {
            if ($this->isLink()) {
                $result[] = new FlashMessage(
                    'Is a link to a directory with the configured permissions of ' . $this->getTargetPermission(),
                    'Link ' . $this->getRelativePathBelowSiteRoot()
                );
            } else {
                $result[] = new FlashMessage(
                    'Is a directory with the configured permissions of ' . $this->getTargetPermission(),
                    'Directory ' . $this->getRelativePathBelowSiteRoot()
                );
            }
        }
        return $result;
    }

    /**
     * Checks if node is a directory or link
     *
     * @return bool True if node is a directory
     */
    protected function isDirectory()
    {
        $path = $this->getAbsolutePath();
        return $this->isLink() || @is_dir($path);
    }

    private function isLink(): bool
    {
        $path = $this->getAbsolutePath();
        return @is_link($path) && @is_dir(realpath($path));
    }

}

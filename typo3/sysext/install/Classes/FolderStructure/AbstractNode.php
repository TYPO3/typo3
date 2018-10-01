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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;

/**
 * Abstract node implements common methods
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
abstract class AbstractNode
{
    /**
     * @var string Name
     */
    protected $name = '';

    /**
     * @var string|null Target permissions for unix, eg. '2775' or '0664' (4 characters string)
     */
    protected $targetPermission;

    /**
     * @var NodeInterface|null Parent object of this structure node
     */
    protected $parent;

    /**
     * @var array Directories and root may have children, files and link always empty array
     */
    protected $children = [];

    /**
     * Get name
     *
     * @return string Name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get target permission
     *
     * Make sure to call octdec on the value when passing this to chmod
     *
     * @return string Permissions as a 4 character octal string, i.e. 2775 or 0644
     */
    protected function getTargetPermission()
    {
        return $this->targetPermission;
    }

    /**
     * Set target permission
     *
     * @param string $permission Permissions as a 4 character octal string, i.e. 2775 or 0644
     */
    protected function setTargetPermission($permission)
    {
        // Normalize the permission string to "4 characters", padding with leading "0" if necessary:
        $permission = substr($permission, 0, 4);
        $permission = str_pad($permission, 4, '0', STR_PAD_LEFT);
        $this->targetPermission = $permission;
    }

    /**
     * Get children
     *
     * @return array
     */
    protected function getChildren()
    {
        return $this->children;
    }

    /**
     * Get parent
     *
     * @return NodeInterface|null
     */
    protected function getParent()
    {
        return $this->parent;
    }

    /**
     * Get absolute path of node
     *
     * @return string
     */
    public function getAbsolutePath()
    {
        return $this->getParent()->getAbsolutePath() . '/' . $this->name;
    }

    /**
     * Current node is writable if parent is writable
     *
     * @return bool TRUE if parent is writable
     */
    public function isWritable()
    {
        return $this->getParent()->isWritable();
    }

    /**
     * Checks if node exists.
     * Returns TRUE if it is there, even if it is only a link.
     * Does not check the type!
     *
     * @return bool
     */
    protected function exists()
    {
        if (@is_link($this->getAbsolutePath())) {
            return true;
        }
        return @file_exists($this->getAbsolutePath());
    }

    /**
     * Fix permission if they are not equal to target permission
     *
     * @throws Exception
     * @return FlashMessage
     */
    protected function fixPermission(): FlashMessage
    {
        if ($this->isPermissionCorrect()) {
            throw new Exception(
                'Permission on ' . $this->getAbsolutePath() . ' are already ok',
                1366744035
            );
        }
        $result = @chmod($this->getAbsolutePath(), octdec($this->getTargetPermission()));
        if ($result === true) {
            return new FlashMessage(
                '',
                'Fixed permission on ' . $this->getRelativePathBelowSiteRoot() . '.'
            );
        }
        return new FlashMessage(
            'Permissions could not be changed to ' . $this->getTargetPermission()
                . '. This only is a problem if files and folders within this node cannot be written.',
            'Permission change on ' . $this->getRelativePathBelowSiteRoot() . ' not successful',
            FlashMessage::NOTICE
        );
    }

    /**
     * Checks if current permission are identical to target permission
     *
     * @return bool
     */
    protected function isPermissionCorrect()
    {
        if ($this->isWindowsOs()) {
            return true;
        }
        if ($this->getCurrentPermission() === $this->getTargetPermission()) {
            return true;
        }
        return false;
    }

    /**
     * Get current permission of node
     *
     * @return string, eg. 2775 for dirs, 0664 for files
     */
    protected function getCurrentPermission()
    {
        $permissions = decoct(fileperms($this->getAbsolutePath()));
        return substr($permissions, -4);
    }

    /**
     * Returns TRUE if OS is windows
     *
     * @return bool TRUE on windows
     */
    protected function isWindowsOs()
    {
        return Environment::isWindows();
    }

    /**
     * Cut off public web path from given path
     *
     * @param string $path Given path
     * @return string Relative path, but beginning with /
     * @throws Exception\InvalidArgumentException
     */
    protected function getRelativePathBelowSiteRoot($path = null)
    {
        if ($path === null) {
            $path = $this->getAbsolutePath();
        }
        $publicPath = Environment::getPublicPath();
        if (strpos($path, $publicPath, 0) !== 0) {
            throw new Exception\InvalidArgumentException(
                'Public path is not first part of given path',
                1366398198
            );
        }
        $relativePath = substr($path, strlen($publicPath), strlen($path));
        // Add a forward slash again, so we don't end up with an empty string
        if ($relativePath === '') {
            $relativePath = '/';
        }
        return $relativePath;
    }
}

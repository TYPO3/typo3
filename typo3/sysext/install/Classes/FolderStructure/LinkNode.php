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

use TYPO3\CMS\Install\Status;

/**
 * A link
 */
class LinkNode extends AbstractNode implements NodeInterface
{
    /**
     * @var string Optional link target
     */
    protected $target = '';

    /**
     * Implement constructor
     *
     * @param array $structure Structure array
     * @param NodeInterface $parent Parent object
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(array $structure, NodeInterface $parent = null)
    {
        if (is_null($parent)) {
            throw new Exception\InvalidArgumentException(
                'Link node must have parent',
                1380485700
            );
        }
        $this->parent = $parent;

        // Ensure name is a single segment, but not a path like foo/bar or an absolute path /foo
        if (strstr($structure['name'], '/') !== false) {
            throw new Exception\InvalidArgumentException(
                'File name must not contain forward slash',
                1380546061
            );
        }
        $this->name = $structure['name'];

        if (isset($structure['target']) && $structure['target'] !== '') {
            $this->target = $structure['target'];
        }
    }

    /**
     * Get own status
     * Returns information status if running on Windows
     * Returns OK status if is link and possible target is correct
     * Else returns error (not fixable)
     *
     * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
     */
    public function getStatus()
    {
        if ($this->isWindowsOs()) {
            $status = new Status\InfoStatus();
            $status->setTitle($this->getRelativePathBelowSiteRoot() . ' should be a link, but this support is incomplete for Windows.');
            $status->setMessage(
                'This node is not handled for Windows OS and should be checked manually.'
            );
            return [$status];
        }

        if (!$this->exists()) {
            $status = new Status\ErrorStatus();
            $status->setTitle($this->getRelativePathBelowSiteRoot() . ' should be a link, but it does not exist');
            $status->setMessage('Links cannot be fixed by this system');
            return [$status];
        }

        if (!$this->isLink()) {
            $status = new Status\WarningStatus();
            $status->setTitle('Path ' . $this->getRelativePathBelowSiteRoot() . ' is not a link');
            $type = @filetype($this->getAbsolutePath());
            if ($type) {
                $status->setMessage(
                    'The target ' . $this->getRelativePathBelowSiteRoot() . ' should be a link,' .
                    ' but is of type ' . $type . '. This cannot be fixed automatically. Please investigate.'
                );
            } else {
                $status->setMessage(
                    'The target ' . $this->getRelativePathBelowSiteRoot() . ' should be a file,' .
                    ' but is of unknown type, probably because an upper level directory does not exist. Please investigate.'
                );
            }
            return [$status];
        }

        if (!$this->isTargetCorrect()) {
            $status = new Status\ErrorStatus();
            $status->setTitle($this->getRelativePathBelowSiteRoot() . ' is a link, but link target is not as specified');
            $status->setMessage(
                'Link target should be ' . $this->getTarget() . ' but is ' . $this->getCurrentTarget()
            );
            return [$status];
        }

        $status = new Status\OkStatus();
        $message = 'Is a link';
        if ($this->getTarget() !== '') {
            $message .= ' and correctly points to target ' . $this->getTarget();
        }
        $status->setTitle($this->getRelativePathBelowSiteRoot());
        $status->setMessage($message);
        return [$status];
    }

    /**
     * Fix structure
     *
     * If there is nothing to fix, returns an empty array
     *
     * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
     */
    public function fix()
    {
        return [];
    }

    /**
     * Get link target
     *
     * @return string Link target
     */
    protected function getTarget()
    {
        return $this->target;
    }

    /**
     * Find out if node is a link
     *
     * @throws Exception\InvalidArgumentException
     * @return bool TRUE if node is a link
     */
    protected function isLink()
    {
        if (!$this->exists()) {
            throw new Exception\InvalidArgumentException(
                'Link does not exist',
                1380556246
            );
        }
        return @is_link($this->getAbsolutePath());
    }

    /**
     * Checks if the real link target is identical to given target
     *
     * @throws Exception\InvalidArgumentException
     * @return bool TRUE if target is correct
     */
    protected function isTargetCorrect()
    {
        if (!$this->exists()) {
            throw new Exception\InvalidArgumentException(
                'Link does not exist',
                1380556245
            );
        }
        if (!$this->isLink()) {
            throw new Exception\InvalidArgumentException(
                'Node is not a link',
                1380556247
            );
        }

        $result = false;
        $expectedTarget = $this->getTarget();
        if (empty($expectedTarget)) {
            $result = true;
        } else {
            $actualTarget = $this->getCurrentTarget();
            if ($expectedTarget === rtrim($actualTarget, '/')) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * Return current target of link
     *
     * @return string target
     */
    protected function getCurrentTarget()
    {
        return readlink($this->getAbsolutePath());
    }
}

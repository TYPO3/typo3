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
 * A link
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
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
        if ($parent === null) {
            throw new InvalidArgumentException(
                'Link node must have parent',
                1380485700
            );
        }
        $this->parent = $parent;

        // Ensure name is a single segment, but not a path like foo/bar or an absolute path /foo
        if (str_contains($structure['name'], '/')) {
            throw new InvalidArgumentException(
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
     * @return FlashMessage[]
     */
    public function getStatus(): array
    {
        if ($this->isWindowsOs()) {
            return [
                new FlashMessage(
                    'This node is not handled for Windows OS and should be checked manually.',
                    $this->getRelativePathBelowSiteRoot() . ' should be a link, but this support is incomplete for Windows.',
                    FlashMessage::INFO
                ),
            ];
        }

        if (!$this->exists()) {
            return [
                new FlashMessage(
                    'Links cannot be fixed by this system',
                    $this->getRelativePathBelowSiteRoot() . ' should be a link, but it does not exist',
                    FlashMessage::ERROR
                ),
            ];
        }

        if (!$this->isLink()) {
            $type = @filetype($this->getAbsolutePath());
            if ($type) {
                $messageBody =
                    'The target ' . $this->getRelativePathBelowSiteRoot() . ' should be a link,' .
                    ' but is of type ' . $type . '. This cannot be fixed automatically. Please investigate.'
                ;
            } else {
                $messageBody =
                    'The target ' . $this->getRelativePathBelowSiteRoot() . ' should be a file,' .
                    ' but is of unknown type, probably because an upper level directory does not exist. Please investigate.'
                ;
            }
            return [
                new FlashMessage(
                    $messageBody,
                    'Path ' . $this->getRelativePathBelowSiteRoot() . ' is not a link',
                    FlashMessage::WARNING
                ),
            ];
        }

        if (!$this->isTargetCorrect()) {
            return [
                new FlashMessage(
                    'Link target should be ' . $this->getTarget() . ' but is ' . $this->getCurrentTarget(),
                    $this->getRelativePathBelowSiteRoot() . ' is a link, but link target is not as specified',
                    FlashMessage::ERROR
                ),
            ];
        }
        $message = 'Is a link';
        if ($this->getTarget() !== '') {
            $message .= ' and correctly points to target ' . $this->getTarget();
        }
        return [
            new FlashMessage(
                $message,
                $this->getRelativePathBelowSiteRoot()
            ),
        ];
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
            throw new InvalidArgumentException(
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
            throw new InvalidArgumentException(
                'Link does not exist',
                1380556245
            );
        }
        if (!$this->isLink()) {
            throw new InvalidArgumentException(
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
            if ($expectedTarget === rtrim((string)$actualTarget, '/')) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * Return current target of link
     *
     * @return false|string target
     */
    protected function getCurrentTarget()
    {
        return readlink($this->getAbsolutePath());
    }
}

<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\DataHandling\Model;

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
 * An EntityPointerLink is used to connect EntityPointer instances
 */
class EntityPointerLink
{
    /**
     * @var EntityPointer
     */
    protected $subject;

    /**
     * @var EntityPointerLink|null
     */
    protected $ancestor;

    /**
     * @param EntityPointer $subject
     */
    public function __construct(EntityPointer $subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return EntityPointer
     */
    public function getSubject(): EntityPointer
    {
        return $this->subject;
    }

    /**
     * @return EntityPointerLink
     */
    public function getHead(): EntityPointerLink
    {
        $head = $this;
        while ($head->ancestor !== null) {
            $head = $head->ancestor;
        }
        return $head;
    }

    /**
     * @return EntityPointerLink|null
     */
    public function getAncestor(): ?EntityPointerLink
    {
        return $this->ancestor;
    }

    /**
     * @param EntityPointerLink $ancestor
     * @return EntityPointerLink
     */
    public function withAncestor(EntityPointerLink $ancestor): self
    {
        if ($this->ancestor === $ancestor) {
            return $this;
        }
        $target = clone $this;
        $target->ancestor = $ancestor;
        return $target;
    }
}

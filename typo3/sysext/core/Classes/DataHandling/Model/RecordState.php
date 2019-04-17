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

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * A RecordState is an abstract description of a record that consists of
 *
 * - an EntityContext describing the "variant" of a record
 * - an EntityPointer that describes the node where the record is stored
 * - an EntityUidPointer of the record the RecordState instance represents
 *
 * Instances of this class are created by the RecordStateFactory.
 */
class RecordState
{
    /**
     * @var EntityContext
     */
    protected $context;

    /**
     * @var EntityPointer
     */
    protected $node;

    /**
     * @var EntityUidPointer
     */
    protected $subject;

    /**
     * @var EntityPointerLink
     */
    protected $languageLink;

    /**
     * @var EntityPointerLink
     */
    protected $versionLink;

    /**
     * @param EntityContext $context
     * @param EntityPointer $node
     * @param EntityUidPointer $subject
     */
    public function __construct(EntityContext $context, EntityPointer $node, EntityUidPointer $subject)
    {
        $this->context = $context;
        $this->node = $node;
        $this->subject = $subject;
    }

    /**
     * @return EntityContext
     */
    public function getContext(): EntityContext
    {
        return $this->context;
    }

    /**
     * @return EntityPointer
     */
    public function getNode(): EntityPointer
    {
        return $this->node;
    }

    /**
     * @return EntityUidPointer
     */
    public function getSubject(): EntityUidPointer
    {
        return $this->subject;
    }

    /**
     * @return EntityPointerLink
     */
    public function getLanguageLink(): ?EntityPointerLink
    {
        return $this->languageLink;
    }

    /**
     * @param EntityPointerLink|null $languageLink
     * @return static
     */
    public function withLanguageLink(?EntityPointerLink $languageLink): self
    {
        if ($this->languageLink === $languageLink) {
            return $this;
        }
        $target = clone $this;
        $target->languageLink = $languageLink;
        return $target;
    }

    /**
     * @return EntityPointerLink
     */
    public function getVersionLink(): ?EntityPointerLink
    {
        return $this->versionLink;
    }

    /**
     * @param EntityPointerLink|null $versionLink
     * @return static
     */
    public function withVersionLink(?EntityPointerLink $versionLink): self
    {
        if ($this->versionLink === $versionLink) {
            return $this;
        }
        $target = clone $this;
        $target->versionLink = $versionLink;
        return $target;
    }

    /**
     * @return bool
     */
    public function isNew(): bool
    {
        return !MathUtility::canBeInterpretedAsInteger(
            $this->subject->getIdentifier()
        );
    }

    /**
     * Resolves node identifier (`pid`) of current subject. For translated pages
     * that would result in the `uid` of the outer-most language parent page
     * otherwise it's the `pid` of the current subject.
     *
     * Example:
     * + pages: uid: 10, pid: 5, sys_language_uid: 0, l10n_parent: 0  -> returns 5
     * + pages: uid: 11, pid: 5, sys_language_uid: 1, l10n_parent: 10 -> returns 10
     * + other: uid: 12, pid: 10 -> returns 10
     *
     * @return string
     */
    public function resolveNodeIdentifier(): string
    {
        if ($this->subject->isNode()
            && $this->context->getLanguageId() > 0
            && $this->languageLink !== null
        ) {
            return $this->languageLink->getHead()->getSubject()->getIdentifier();
        }
        return $this->node->getIdentifier();
    }

    /**
     * Resolves node identifier used as aggregate for current subject. For translated
     * pages that would result in the `uid` of the outer-most language parent page,
     * for pages it's the identifier of the current subject, otherwise it's
     * the `pid` of the current subject.
     *
     * Example:
     * + pages: uid: 10, pid: 5, sys_language_uid: 0, l10n_parent: 0  -> returns 10
     * + pages: uid: 11, pid: 5, sys_language_uid: 1, l10n_parent: 10 -> returns 10
     * + pages in version, return online page ID
     * + other: uid: 12, pid: 10 -> returns 10
     *
     * @return string
     */
    public function resolveNodeAggregateIdentifier(): string
    {
        if ($this->subject->isNode()
            && $this->context->getLanguageId() > 0
            && $this->languageLink !== null
        ) {
            return $this->languageLink->getHead()->getSubject()->getIdentifier();
        }
        if ($this->subject->isNode() && $this->versionLink) {
            return $this->versionLink->getHead()->getSubject()->getIdentifier();
        }
        if ($this->subject->isNode()) {
            return $this->subject->getIdentifier();
        }
        return $this->node->getIdentifier();
    }
}

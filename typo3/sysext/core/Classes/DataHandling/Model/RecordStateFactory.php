<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\DataHandling\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Factory class that creates a record state
 */
class RecordStateFactory
{
    protected string $name;

    public static function forName(string $name): self
    {
        return GeneralUtility::makeInstance(static::class, $name);
    }

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param int|string|null $pageId
     * @param int|string|null $recordId
     */
    public function fromArray(array $data, $pageId = null, $recordId = null): RecordState
    {
        $pageId = $pageId ?? $data['pid'] ?? null;
        $recordId = $recordId ?? $data['uid'] ?? null;

        $aspectFieldValues = $this->resolveAspectFieldValues($data);

        $context = GeneralUtility::makeInstance(EntityContext::class)
            ->withWorkspaceId($aspectFieldValues['workspace'])
            ->withLanguageId($aspectFieldValues['language']);
        $node = $this->createEntityPointer($pageId, 'pages');
        $subject = $this->createEntityPointer($recordId);

        $target = GeneralUtility::makeInstance(
            RecordState::class,
            $context,
            $node,
            $subject
        );
        return $target
            ->withLanguageLink($this->resolveLanguageLink($aspectFieldValues))
            ->withVersionLink($this->resolveVersionLink($aspectFieldValues));
    }

    /**
     * @return array<string, string|null>
     */
    protected function resolveAspectFieldNames(): array
    {
        return [
            'workspace' => 't3ver_wsid',
            'versionParent' => 't3ver_oid',
            'language' => $GLOBALS['TCA'][$this->name]['ctrl']['languageField'] ?? null,
            'languageParent' => $GLOBALS['TCA'][$this->name]['ctrl']['transOrigPointerField'] ?? null,
            'languageSource' => $GLOBALS['TCA'][$this->name]['ctrl']['translationSource'] ?? null,
        ];
    }

    protected function resolveAspectFieldValues(array $data): array
    {
        return array_map(
            static function (?string $aspectFieldName) use ($data): int {
                return (int)($data[$aspectFieldName ?? ''] ?? 0);
            },
            $this->resolveAspectFieldNames()
        );
    }

    protected function resolveLanguageLink(array $aspectFieldNames): ?EntityPointerLink
    {
        $languageSourceLink = null;
        $languageParentLink = null;
        if (!empty($aspectFieldNames['languageSource'])) {
            $languageSourceLink = GeneralUtility::makeInstance(
                EntityPointerLink::class,
                $this->createEntityPointer($aspectFieldNames['languageSource'])
            );
        }

        if (!empty($aspectFieldNames['languageParent'])) {
            $languageParentLink = GeneralUtility::makeInstance(
                EntityPointerLink::class,
                $this->createEntityPointer($aspectFieldNames['languageParent'])
            );
        }

        if (empty($languageSourceLink) || empty($languageParentLink)
            || $languageSourceLink->getSubject()->isEqualTo(
                $languageParentLink->getSubject()
            )
        ) {
            return $languageSourceLink ?? $languageParentLink ?? null;
        }
        return $languageSourceLink->withAncestor($languageParentLink);
    }

    protected function resolveVersionLink(array $aspectFieldNames): ?EntityPointerLink
    {
        if (!empty($aspectFieldNames['versionParent'])) {
            return GeneralUtility::makeInstance(
                EntityPointerLink::class,
                $this->createEntityPointer($aspectFieldNames['versionParent'])
            );
        }
        return null;
    }

    /**
     * @param string|int|null $identifier
     * @param string|null $name
     * @throws \LogicException
     */
    protected function createEntityPointer($identifier, ?string $name = null): EntityPointer
    {
        if ($identifier === null) {
            throw new \LogicException(
                'Cannot create null pointer',
                1536407967
            );
        }

        $identifier = (string)$identifier;

        return GeneralUtility::makeInstance(
            EntityUidPointer::class,
            $name ?? $this->name,
            $identifier
        );
    }
}

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

namespace TYPO3\CMS\Frontend\Content;

use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Page\ContentArea;
use TYPO3\CMS\Core\Page\ContentAreaClosure;
use TYPO3\CMS\Core\Page\ContentSlideMode;
use TYPO3\CMS\Core\Page\ResolveContentAreasEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

final readonly class ContentAreaResolver
{
    public function __construct(private RecordCollector $recordCollector) {}

    #[AsEventListener]
    public function __invoke(ResolveContentAreasEvent $event): void
    {
        $layout = $event->getBackendLayout();
        $fullStructure = $layout->getStructure()['__config'];
        $contentAreas = $this->collectContentAreasRecursive($fullStructure, $layout);
        $event->setContentAreas($contentAreas);
    }

    /**
     * Find all arrays recursively from where one of the columns within the array is called "colPos"
     *
     * @param array<mixed>|array{
     *     colPos: string|int,
     *     name?: string|int,
     *     slideMode?: string,
     *     identifier?: string|int,
     *     allowedContentTypes?: string,
     *     disallowedContentTypes?: string,
     * } $structure
     * @param array<string, ContentAreaClosure> $contentAreas
     * @return array<string, ContentAreaClosure>
     */
    private function collectContentAreasRecursive(array $structure, BackendLayout $layout, array $contentAreas = []): array
    {
        if (isset($structure['colPos'])) {
            $name = (string)($structure['name'] ?? '');
            $colPos = (int)$structure['colPos'];
            $slideMode = ContentSlideMode::tryFrom($structure['slideMode'] ?? null);
            $allowedContentTypes = GeneralUtility::trimExplode(',', $structure['allowedContentTypes'] ?? '', true);
            $disallowedContentTypes = GeneralUtility::trimExplode(',', $structure['disallowedContentTypes'] ?? '', true);
            $identifier = (string)($structure['identifier'] ?? '');
            if ($identifier === '') {
                // @deprecated Identifier has to be set -> throw exception in v15
                trigger_error(
                    'No identifier given for column with colPos "' . $colPos . '" in page layout "' . $layout->getIdentifier() . '". Setting an identifier will be mandatory in TYPO3 v15',
                    E_USER_DEPRECATED
                );
                $identifier = md5($layout->getIdentifier() . $colPos);
            }
            $contentAreas[$identifier] = new ContentAreaClosure(
                function () use ($identifier, $name, $colPos, $slideMode, $allowedContentTypes, $disallowedContentTypes, $structure): ContentArea {
                    $records = $this->recordCollector->collect(
                        'tt_content',
                        [
                            'where' => '{#colPos}=' . $colPos,
                            'orderBy' => 'sorting',
                        ],
                        $slideMode,
                        GeneralUtility::makeInstance(ContentObjectRenderer::class)
                    );
                    return new ContentArea(
                        identifier: $identifier,
                        name: $name,
                        colPos: $colPos,
                        slideMode: $slideMode,
                        allowedContentTypes: $allowedContentTypes,
                        disallowedContentTypes: $disallowedContentTypes,
                        configuration: $structure,
                        records: $records
                    );
                }
            );
            // Content Areas cannot be nested. Bubble up and find further areas next to this.
            return $contentAreas;
        }
        foreach ($structure as $value) {
            if (is_array($value)) {
                $contentAreas = $this->collectContentAreasRecursive($value, $layout, $contentAreas);
            }
        }
        return $contentAreas;
    }
}

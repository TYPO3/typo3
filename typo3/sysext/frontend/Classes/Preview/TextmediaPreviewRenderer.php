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

namespace TYPO3\CMS\Frontend\Preview;

use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;

/**
 * Contains a preview rendering for the page module of CType="textmedia"
 * @internal this is a concrete TYPO3 hook implementation and solely used for EXT:frontend and not part of TYPO3's Core API.
 */
class TextmediaPreviewRenderer extends StandardContentPreviewRenderer
{
    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $content = '';
        $row = $item->getRecord();
        if ($row['bodytext']) {
            $content = $this->linkEditContent($this->renderText($row['bodytext']), $row) . '<br />';
        }

        if ($row['assets']) {
            $content .= $this->linkEditContent(BackendUtility::thumbCode($row, 'tt_content', 'assets', '', '', null, 0, '', '', false), $row);

            $fileReferences = BackendUtility::resolveFileReferences('tt_content', 'assets', $row);

            if (!empty($fileReferences)) {
                $linkedContent = '';

                foreach ($fileReferences as $fileReference) {
                    $description = $fileReference->getDescription();
                    if ($description !== null && $description !== '') {
                        $linkedContent .= htmlspecialchars($description) . '<br />';
                    }
                }

                $content .= $this->linkEditContent($linkedContent, $row);
            }
        }
        return $content;
    }
}

<?php
namespace TYPO3\CMS\Frontend\Hooks\PageLayoutView;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface;

/**
 * Contains a preview rendering for the page module of CType="textmedia"
 */
class TextmediaPreviewRenderer implements PageLayoutViewDrawItemHookInterface
{
    /**
     * Preprocesses the preview rendering of a content element of type "textmedia"
     *
     * @param \TYPO3\CMS\Backend\View\PageLayoutView $parentObject Calling parent object
     * @param bool $drawItem Whether to draw the item using the default functionality
     * @param string $headerContent Header content
     * @param string $itemContent Item content
     * @param array $row Record row of tt_content
     */
    public function preProcess(
        PageLayoutView &$parentObject,
        &$drawItem,
        &$headerContent,
        &$itemContent,
        array &$row
    ) {
        if ($row['CType'] === 'textmedia') {
            if ($row['bodytext']) {
                $itemContent .= $parentObject->linkEditContent($parentObject->renderText($row['bodytext']), $row) . '<br />';
            }

            if ($row['assets']) {
                $itemContent .= $parentObject->linkEditContent($parentObject->getThumbCodeUnlinked($row, 'tt_content', 'assets'), $row) . '<br />';

                $fileReferences = BackendUtility::resolveFileReferences('tt_content', 'assets', $row);

                if (!empty($fileReferences)) {
                    $linkedContent = '';

                    foreach ($fileReferences as $fileReference) {
                        $description = $fileReference->getDescription();
                        if ($description !== null && $description !== '') {
                            $linkedContent .= htmlspecialchars($description) . '<br />';
                        }
                    }

                    $itemContent .= $parentObject->linkEditContent($linkedContent, $row);

                    unset($linkedContent);
                }
            }

            $drawItem = false;
        }
    }
}

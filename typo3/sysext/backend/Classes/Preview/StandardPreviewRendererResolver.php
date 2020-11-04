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

namespace TYPO3\CMS\Backend\Preview;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class StandardPreviewRendererResolver
 *
 * Default implementation of PreviewRendererResolverInterface.
 * Scans TCA configuration to detect:
 *
 * - TCA.$table.types.$typeFromTypeField.previewRenderer
 * - TCA.$table.ctrl.previewRenderer
 *
 * Depending on which one is defined and checking the first, type-specific
 * variant first.
 */
class StandardPreviewRendererResolver implements PreviewRendererResolverInterface
{
    /**
     * @param string $table The name of the table the returned PreviewRenderer must work with
     * @param array $row A record from $table which will be previewed - allows returning a different PreviewRenderer based on record attributes
     * @param int $pageUid The UID of the page on which the preview will be rendered - allows returning a different PreviewRenderer based on for example pageTSconfig
     * @return PreviewRendererInterface
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     */
    public function resolveRendererFor(string $table, array $row, int $pageUid): PreviewRendererInterface
    {
        $tca = $GLOBALS['TCA'][$table];
        $tcaTypeField = $tca['ctrl']['type'] ?? null;
        $previewRendererClassName = null;
        if ($tcaTypeField) {
            $tcaTypeOfRow = $row[$tcaTypeField];
            $typeConfiguration = $tca['types'][$tcaTypeOfRow] ?? [];

            $subTypeValueField = $typeConfiguration['subtype_value_field'] ?? null;
            if (!empty($typeConfiguration['previewRenderer'])) {
                if (!empty($subTypeValueField) && is_array($typeConfiguration['previewRenderer'])) {
                    // An array of subtype_value_field indexed preview renderers was defined, look up the right
                    // class to use for the sub-type defined in this $row.
                    $previewRendererClassName = $typeConfiguration['previewRenderer'][$row[$subTypeValueField] ?? ''] ?? null;
                }

                // If no class was found in the subtype_value_field
                if (!$previewRendererClassName && !is_array($typeConfiguration['previewRenderer'])) {
                    // A type-specific preview renderer was configured for the TCA type (and one was not detected
                    // based on the higher-priority lookups above).
                    $previewRendererClassName = $typeConfiguration['previewRenderer'];
                }
            }
        }

        if (!$previewRendererClassName) {

            // Table either has no type field or no custom preview renderer was defined for the type.
            // Use table's standard renderer if any is defined.
            $previewRendererClassName = $tca['ctrl']['previewRenderer'] ?? null;
        }

        if (!empty($previewRendererClassName)) {
            /** @var string $previewRendererClassName */
            if (!is_a($previewRendererClassName, PreviewRendererInterface::class, true)) {
                throw new \UnexpectedValueException(
                    sprintf(
                        'Class %s must implement %s',
                        $previewRendererClassName,
                        PreviewRendererInterface::class
                    ),
                    1477512798
                );
            }
            return GeneralUtility::makeInstance($previewRendererClassName);
        }
        throw new \RuntimeException(sprintf('No Preview renderer registered for table %s', $table), 1477520356);
    }
}

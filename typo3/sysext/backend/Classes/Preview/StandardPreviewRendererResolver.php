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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Scans TCA configuration to detect:
 *
 * - TCA.$table.types.$typeFromTypeField.previewRenderer
 * - TCA.$table.ctrl.previewRenderer
 *
 * Depending on which one is defined and checking the first, type-specific
 * variant first.
 */
#[Autoconfigure(public: true)]
class StandardPreviewRendererResolver
{
    public function __construct(
        protected readonly TcaSchemaFactory $tcaSchemaFactory
    ) {}

    /**
     * @param string $table The name of the table the returned PreviewRenderer must work with
     * @param array $row A record from $table which will be previewed - allows returning a different PreviewRenderer based on record attributes
     * @param int $pageUid The UID of the page on which the preview will be rendered - allows returning a different PreviewRenderer based on for example pageTSconfig
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     */
    public function resolveRendererFor(string $table, array $row, int $pageUid): PreviewRendererInterface
    {
        $schema = $this->tcaSchemaFactory->get($table);
        $previewRendererClassName = null;
        if ($schema->getSubSchemaDivisorField()) {
            $tcaTypeOfRow = $row[$schema->getSubSchemaDivisorField()->getName()];
            if ($schema->hasSubSchema($tcaTypeOfRow)) {
                // Outdated subschemas may still be present in the database fields, this must not block backend rendering and utilize fallback.
                $subSchema = $schema->getSubSchema($tcaTypeOfRow);
                if (is_string($subSchema->getRawConfiguration()['previewRenderer'] ?? false) && $subSchema->getRawConfiguration()['previewRenderer'] !== '') {
                    // A type-specific preview renderer was configured for the TCA type
                    $previewRendererClassName = $subSchema->getRawConfiguration()['previewRenderer'];
                }
            }
        }

        if (!$previewRendererClassName) {
            // Table either has no type field or no custom preview renderer was defined for the type.
            // Use table's standard renderer if any is defined.
            $previewRendererClassName = $schema->getRawConfiguration()['previewRenderer'] ?? null;
        }

        if (is_string($previewRendererClassName) && $previewRendererClassName !== '') {
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

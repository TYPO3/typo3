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

namespace TYPO3\CMS\Backend\Breadcrumb;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Dto\Breadcrumb\BreadcrumbNode;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Factory for creating breadcrumb contexts from controller actions.
 *
 * This factory centralizes the logic for determining what context to show
 * in breadcrumbs based on different controller actions (edit, new, list, etc.).
 *
 * It handles:
 * - Record lookups and validation
 * - Creation of "new record" breadcrumb nodes
 * - Multi-record edit scenarios
 * - Parent record resolution
 *
 * @internal Subject to change until v15 LTS
 */
#[Autoconfigure(public: true)]
final class BreadcrumbFactory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly RecordFactory $recordFactory,
        private readonly IconFactory $iconFactory,
        private readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Creates breadcrumb context for editing an existing record.
     *
     * @param string $table The table name
     * @param int $uid The record UID
     * @return BreadcrumbContext Context containing the record or null on failure
     */
    public function forEditAction(string $table, int $uid): BreadcrumbContext
    {
        $rawRecord = BackendUtility::getRecord($table, $uid);

        if ($rawRecord === null) {
            $this->logger?->warning(
                'Failed to load record for breadcrumb',
                ['table' => $table, 'uid' => $uid]
            );
            return new BreadcrumbContext(null, []);
        }

        try {
            $record = $this->recordFactory->createResolvedRecordFromDatabaseRow($table, $rawRecord);
            return new BreadcrumbContext($record, []);
        } catch (\Exception $e) {
            $this->logger?->error(
                'Failed to create record instance for breadcrumb',
                ['table' => $table, 'uid' => $uid, 'exception' => $e->getMessage()]
            );
            return new BreadcrumbContext(null, []);
        }
    }

    /**
     * Creates breadcrumb context for editing multiple records.
     *
     * Shows a generic "Edit Multiple [RecordType]" node instead of individual records.
     *
     * @param string $table The table name
     * @param int $pid The parent page ID
     * @return BreadcrumbContext Context with parent page and "edit multiple" suffix node
     */
    public function forEditMultipleAction(string $table, int $pid): BreadcrumbContext
    {
        $parentRecord = $this->getParentPageRecord($pid);
        $schema = $this->tcaSchemaFactory->has($table) ? $this->tcaSchemaFactory->get($table) : null;

        $recordTypeLabel = $schema?->getTitle($this->getLanguageService()->sL(...))
            ?? $schema?->getTitle()
            ?? $table;

        $suffixNode = new BreadcrumbNode(
            identifier: 'edit-multiple-' . $table,
            label: sprintf(
                $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.editMultiple'),
                $recordTypeLabel
            ),
            icon: $this->iconFactory->getIconForRecord($table, [])->getIdentifier(),
        );

        return new BreadcrumbContext($parentRecord, [$suffixNode]);
    }

    /**
     * Creates breadcrumb context for creating a new record.
     *
     * @param string $table The table name
     * @param int $pid The parent page ID
     * @param array $defaults Default values for the new record (used for icon overlay)
     * @return BreadcrumbContext Context with parent page and "create new" suffix node
     */
    public function forNewAction(string $table, int $pid, array $defaults = []): BreadcrumbContext
    {
        $parentRecord = $this->getParentPageRecord($pid);
        $schema = $this->tcaSchemaFactory->has($table) ? $this->tcaSchemaFactory->get($table) : null;

        $recordTypeLabel = $schema?->getTitle($this->getLanguageService()->sL(...))
            ?? $schema?->getTitle()
            ?? $table;

        try {
            $icon = $this->iconFactory->getIconForRecord($table, $defaults);
            $suffixNode = new BreadcrumbNode(
                identifier: 'new-' . $table,
                label: sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.createNew'),
                    $recordTypeLabel
                ),
                icon: $icon->getIdentifier(),
                iconOverlay: 'overlay-new',
            );
        } catch (\Exception $e) {
            $this->logger?->warning(
                'Failed to create icon for new record breadcrumb',
                ['table' => $table, 'exception' => $e->getMessage()]
            );
            $suffixNode = new BreadcrumbNode(
                identifier: 'new-' . $table,
                label: sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.createNew'),
                    $recordTypeLabel
                ),
            );
        }

        return new BreadcrumbContext($parentRecord, [$suffixNode]);
    }

    /**
     * Creates breadcrumb context from a page record array.
     *
     * This is the most common migration path from DocHeaderComponent::setMetaInformation().
     *
     * Example migration:
     * Before: `$view->getDocHeaderComponent()->setMetaInformation($pageInfo);`
     * After:  `$view->getDocHeaderComponent()->setBreadcrumbContext($this->breadcrumbFactory->forPageArray($pageInfo));`
     *
     * @param array $pageRecord The page record array (must contain 'uid')
     * @return BreadcrumbContext Context with the page record or null on failure
     */
    public function forPageArray(array $pageRecord): BreadcrumbContext
    {
        if (!isset($pageRecord['uid'])) {
            $this->logger?->warning('Page record array must contain uid for breadcrumb');
            return new BreadcrumbContext(null, []);
        }

        try {
            $record = $this->recordFactory->createResolvedRecordFromDatabaseRow('pages', $pageRecord);
            return new BreadcrumbContext($record, []);
        } catch (\Exception $e) {
            $this->logger?->error(
                'Failed to create page record instance for breadcrumb',
                ['uid' => $pageRecord['uid'], 'exception' => $e->getMessage()]
            );
            return new BreadcrumbContext(null, []);
        }
    }

    /**
     * Creates breadcrumb context for any resource (file or folder).
     *
     * @param ResourceInterface $resource The resource (file or folder)
     * @return BreadcrumbContext Context with the resource
     */
    public function forResource(ResourceInterface $resource): BreadcrumbContext
    {
        return new BreadcrumbContext($resource, []);
    }

    /**
     * Gets the parent page record for a given PID.
     *
     * @param int $pid The page ID
     * @return RecordInterface|null The page record or null if not found/accessible
     */
    private function getParentPageRecord(int $pid): ?RecordInterface
    {
        if ($pid <= 0) {
            return null;
        }

        $rawRecord = BackendUtility::getRecord('pages', $pid);
        if ($rawRecord === null) {
            $this->logger?->warning(
                'Failed to load parent page for breadcrumb',
                ['pid' => $pid]
            );
            return null;
        }

        try {
            return $this->recordFactory->createResolvedRecordFromDatabaseRow('pages', $rawRecord);
        } catch (\Exception $e) {
            $this->logger?->error(
                'Failed to create page record instance for breadcrumb',
                ['pid' => $pid, 'exception' => $e->getMessage()]
            );
            return null;
        }
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}

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

namespace TYPO3\CMS\Core\Upgrades;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Table;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Migrates `pages.url` field values for `pages.doktype = 3 (Link)` to
 * TypoLink notation suitable for the `pages.link` field and displays
 * failed pages uid.
 *
 * @since 14.0
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 * @todo Remove in 16.0 as breaking change.
 */
#[UpgradeWizard('pageDoktypeLinkMigration')]
class PageDoktypeLinkMigration implements UpgradeWizardInterface, ChattyInterface
{
    protected ?OutputInterface $output = null;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    public function getTitle(): string
    {
        return 'Migrate field "pages.url" to "pages.link" for pages of type Link.';
    }

    public function getDescription(): string
    {
        return 'Migrates "pages.url" to "pages.link", preserving former behaviour of the page type "External Link".';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        $tableSchema = $this->getPagesTableSchema();
        return $tableSchema !== null
            && $tableSchema->hasColumn('url')
            && $tableSchema->hasColumn('target')
            && $tableSchema->hasColumn('link')
            && $this->hasRecordsToUpdate();
    }

    public function executeUpdate(): bool
    {
        if (!$this->updateNecessary()) {
            return true;
        }
        $connection = $this->connectionPool->getConnectionForTable('pages');
        $migratableItemsQueryBuilder = $connection->createQueryBuilder();
        $result = $migratableItemsQueryBuilder
            ->select('uid', 'url', 'target', 'deleted')
            ->from('pages')
            ->where(
                $migratableItemsQueryBuilder->expr()->and(
                    $migratableItemsQueryBuilder->expr()->eq('link', $migratableItemsQueryBuilder->createNamedParameter('')),
                    $migratableItemsQueryBuilder->expr()->neq('url', $migratableItemsQueryBuilder->createNamedParameter('')),
                    $migratableItemsQueryBuilder->expr()->eq('doktype', $migratableItemsQueryBuilder->createNamedParameter(PageRepository::DOKTYPE_LINK, ParameterType::INTEGER)),
                ),
            )
            ->executeQuery();
        $scheme = $GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultScheme'] ?? 'http';
        $failedMigrations = [];
        try {
            while ($row = $result->fetchAssociative()) {
                $url = $this->migrateExternalUrlToTypoLink($row['url'], $row['target'], $scheme);
                if ($url === '') {
                    if ($row['deleted'] !== 1) {
                        $failedMigrations[] = $row['uid'];
                    }
                    continue;
                }
                $updateQueryBuilder = $connection->createQueryBuilder();
                $updateQueryBuilder->getRestrictions()->removeAll();
                $expression = $updateQueryBuilder->expr();
                $updateQueryBuilder->update('pages')
                    ->set('link', $url)
                    // Empty url field to flag already migrated record.
                    ->set('url', '')
                    // Empty target field
                    ->set('target', '')
                    ->where($expression->eq('uid', ($row['uid'])))
                    ->executeStatement();
            }
        } finally {
            // Ensure to buffer is freed in case any exception occurred to avoid follow issues.
            $result->free();
        }
        if ($failedMigrations !== []) {
            $this->output?->writeln(sprintf(
                'The following pages of type "Link" could not be migrated: %s',
                implode(', ', $failedMigrations),
            ));
        }
        return true;
    }

    protected function hasRecordsToUpdate(): bool
    {
        $connection = $this->connectionPool->getConnectionForTable('pages');
        $migratableItemsQueryBuilder = $connection->createQueryBuilder();
        return (bool)$migratableItemsQueryBuilder
            ->count('*')
            ->from('pages')
            ->where(
                $migratableItemsQueryBuilder->expr()->and(
                    $migratableItemsQueryBuilder->expr()->eq('link', $migratableItemsQueryBuilder->createNamedParameter('')),
                    $migratableItemsQueryBuilder->expr()->neq('url', $migratableItemsQueryBuilder->createNamedParameter('')),
                    $migratableItemsQueryBuilder->expr()->eq('doktype', $migratableItemsQueryBuilder->createNamedParameter(PageRepository::DOKTYPE_LINK, ParameterType::INTEGER)),
                ),
            )->executeQuery()->fetchOne();
    }

    protected function migrateExternalUrlToTypoLink(string $urlString, string $target, string $sitePrefix): string
    {
        $urlTargetSuffix = $target !== '' ? ' ' . $target : '';
        if ($urlString === '') {
            return '';
        }
        // Old ExternalUrl field allowed to simply define query parameters appended to the current page,
        // which TypoScript still supports. Simply keep/copy the option.
        if (str_starts_with($urlString, '?')) {
            return $urlString . $urlTargetSuffix;
        }
        $parsedUrl = parse_url($urlString);
        if (str_starts_with($urlString, 'mailto:')) {
            if (GeneralUtility::validEmail(substr($urlString, 7))) {
                // valid mailto: URL
                return $urlString . $urlTargetSuffix;
            }
            return '';

        }
        if (!($parsedUrl['scheme'] ?? false)) {
            if (GeneralUtility::validEmail($urlString)) {
                // Email Address without mailto prefix
                return 'mailto:' . $urlString;
            }
            if (str_starts_with($urlString, '/')) {
                // Relative Url on site base
                return $urlString . $urlTargetSuffix;
            }
            // domain without https prefix
            $urlString = $sitePrefix . '://' . $urlString;
        }
        if (!GeneralUtility::isValidUrl($urlString)) {
            return '';
        }
        // Reject any non-http(s) schemes (mailto: already handled above)
        // This rejects javascript: ftp: and other potentially harmful prefixes
        $scheme = strtolower((string)(parse_url($urlString, PHP_URL_SCHEME) ?? ''));
        if ($scheme !== '' && $scheme !== 'http' && $scheme !== 'https') {
            return '';
        }
        return $urlString . $urlTargetSuffix;
    }

    protected function getPagesTableSchema(): ?Table
    {
        $schemaManager = $this->connectionPool->getConnectionForTable('pages')->createSchemaManager();
        if (!$schemaManager->tablesExist(['pages'])) {
            return null;
        }
        return $schemaManager->introspectTable('pages');
    }
}

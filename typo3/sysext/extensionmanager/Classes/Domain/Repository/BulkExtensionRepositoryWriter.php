<?php

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

namespace TYPO3\CMS\Extensionmanager\Domain\Repository;

use Doctrine\DBAL\Exception as DBALException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Parser\ExtensionXmlParser;

/**
 * Importer object for extension list, which handles the XML parser and writes directly into the database.
 *
 * @internal This class is a specific domain repository implementation and is not part of the Public TYPO3 API.
 */
class BulkExtensionRepositoryWriter implements \SplObserver
{
    /**
     * @var string
     */
    private const TABLE_NAME = 'tx_extensionmanager_domain_model_extension';

    protected ExtensionXmlParser $parser;

    /**
     * Keeps number of processed version records.
     *
     * @var int
     */
    protected $sumRecords = 0;

    /**
     * Keeps record values to be inserted into database.
     *
     * @var array
     */
    protected $arrRows = [];

    /**
     * Keeps fieldnames of tx_extensionmanager_domain_model_extension table.
     *
     * @var array
     */
    protected static $fieldNames = [
        'extension_key',
        'version',
        'integer_version',
        'current_version',
        'alldownloadcounter',
        'downloadcounter',
        'title',
        'ownerusername',
        'author_name',
        'author_email',
        'authorcompany',
        'last_updated',
        'md5hash',
        'remote',
        'state',
        'review_state',
        'category',
        'description',
        'serialized_dependencies',
        'update_comment',
        'documentation_link',
        'distribution_image',
        'distribution_welcome_image',
    ];

    /**
     * Maximum of rows that can be used in a bulk insert for the current
     * database platform.
     *
     * @var int
     */
    protected $maxRowsPerChunk = 50;

    /**
     * Keeps the information from which remote the extension list was fetched.
     *
     * @var string
     */
    protected $remoteIdentifier;

    /**
     * @var ExtensionRepository
     */
    protected $extensionRepository;

    /**
     * @var Extension
     */
    protected $extensionModel;

    /**
     * @var ConnectionPool
     */
    protected $connectionPool;

    /**
     * Only import extensions newer than this date (timestamp),
     * see constructor
     *
     * @var int
     */
    protected $minimumDateToImport;

    /**
     * Method retrieves and initializes extension XML parser instance.
     *
     * @param ExtensionRepository $repository
     * @param Extension $extension
     * @param ConnectionPool $connectionPool
     * @param ExtensionXmlParser $parser
     * @throws DBALException
     */
    public function __construct(
        ExtensionRepository $repository,
        Extension $extension,
        ConnectionPool $connectionPool,
        ExtensionXmlParser $parser
    ) {
        $this->extensionRepository = $repository;
        $this->extensionModel = $extension;
        $this->connectionPool = $connectionPool;
        $this->parser = $parser;
        $this->parser->attach($this);

        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);
        $maxBindParameters = PlatformInformation::getMaxBindParameters(
            $connection->getDatabasePlatform()
        );
        $countOfBindParamsPerRow = count(self::$fieldNames);
        // flush at least chunks of 50 elements - in case the currently used
        // database platform does not support that, the threshold is lowered
        $this->maxRowsPerChunk = (int)min(
            $this->maxRowsPerChunk,
            floor($maxBindParameters / $countOfBindParamsPerRow)
        );
        // Only import extensions that are compatible with 7.6 or higher.
        // TER only allows to publish extensions with compatibility if the TYPO3 version has been released
        // And 7.6 was released on 10th of November 2015.
        // This effectively reduces the number of extensions imported into this TYPO3 installation
        // by more than 70%. As long as the extensions.xml from TER includes these files, we need to "hack" this
        // within TYPO3 Core.
        // For TYPO3 v11.0, this date could be set to 2018-10-02 (v9 LTS release).
        // Also see https://decisions.typo3.org/t/reduce-size-of-extension-manager-db-table/329/
        $this->minimumDateToImport = strtotime('2017-04-04T00:00:00+00:00');
    }

    /**
     * Method initializes parsing of extension.xml.gz file.
     *
     * @param string $localExtensionListFile absolute path to extension list xml.gz
     * @param string $remoteIdentifier identifier of the remote when inserting records into DB
     */
    public function import(string $localExtensionListFile, string $remoteIdentifier): void
    {
        // Remove all existing entries of this remote from the database
        $this->connectionPool
            ->getConnectionForTable(self::TABLE_NAME)
            ->delete(
                self::TABLE_NAME,
                ['remote' => $remoteIdentifier],
                [\PDO::PARAM_STR]
            );
        $this->remoteIdentifier = $remoteIdentifier;
        $zlibStream = 'compress.zlib://';
        $this->sumRecords = 0;
        $this->parser->parseXml($zlibStream . $localExtensionListFile);
        // flush last rows to database if existing
        if (!empty($this->arrRows)) {
            $this->connectionPool
                ->getConnectionForTable(self::TABLE_NAME)
                ->bulkInsert(
                    'tx_extensionmanager_domain_model_extension',
                    $this->arrRows,
                    self::$fieldNames
                );
        }
        $this->markExtensionWithMaximumVersionAsCurrent($remoteIdentifier);
    }

    /**
     * Method collects and stores extension version details into the database.
     *
     * @param ExtensionXmlParser $subject a subject notifying this observer
     */
    protected function loadIntoDatabase(ExtensionXmlParser $subject): void
    {
        if ($this->sumRecords !== 0 && $this->sumRecords % $this->maxRowsPerChunk === 0) {
            $this->connectionPool
                ->getConnectionForTable(self::TABLE_NAME)
                ->bulkInsert(
                    self::TABLE_NAME,
                    $this->arrRows,
                    self::$fieldNames
                );
            $this->arrRows = [];
        }
        if (!$subject->isValidVersionNumber()) {
            // Skip in case extension version is not valid
            return;
        }
        $versionRepresentations = VersionNumberUtility::convertVersionStringToArray($subject->getVersion());
        // order must match that of self::$fieldNames!
        $this->arrRows[] = [
            $subject->getExtkey(),
            $subject->getVersion(),
            $versionRepresentations['version_int'],
            // initialize current_version, correct value computed later:
            0,
            $subject->getAlldownloadcounter(),
            $subject->getDownloadcounter(),
            $subject->getTitle(),
            $subject->getOwnerusername(),
            $subject->getAuthorname(),
            $subject->getAuthoremail(),
            $subject->getAuthorcompany(),
            $subject->getLastuploaddate(),
            $subject->getT3xfilemd5(),
            $this->remoteIdentifier,
            $this->extensionModel->getDefaultState($subject->getState() ?: ''),
            $subject->getReviewstate(),
            $this->extensionModel->getCategoryIndexFromStringOrNumber($subject->getCategory() ?: ''),
            $subject->getDescription() ?: '',
            $subject->getDependencies() ?: '',
            $subject->getUploadcomment() ?: '',
            $subject->getDocumentationLink() ?: '',
            $subject->getDistributionImage() ?: '',
            $subject->getDistributionWelcomeImage() ?: '',
        ];
        ++$this->sumRecords;
    }

    /**
     * Method receives an update from a subject.
     *
     * @param \SplSubject $subject a subject notifying this observer
     */
    public function update(\SplSubject $subject): void
    {
        if ($subject instanceof ExtensionXmlParser) {
            if ((int)$subject->getLastuploaddate() > $this->minimumDateToImport) {
                $this->loadIntoDatabase($subject);
            }
        }
    }

    /**
     * Sets current_version = 1 for all extensions where the extension version is maximal.
     *
     * For performance reasons, the "native" database connection is used here directly.
     *
     * @param string $remoteIdentifier
     */
    protected function markExtensionWithMaximumVersionAsCurrent(string $remoteIdentifier): void
    {
        $uidsOfCurrentVersion = $this->fetchMaximalVersionsForAllExtensions($remoteIdentifier);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);
        $maxBindParameters = PlatformInformation::getMaxBindParameters(
            $connection->getDatabasePlatform()
        );

        foreach (array_chunk($uidsOfCurrentVersion, $maxBindParameters - 10) as $chunk) {
            $queryBuilder
                ->update(self::TABLE_NAME)
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->createNamedParameter($chunk, Connection::PARAM_INT_ARRAY)
                    )
                )
                ->set('current_version', 1)
                ->executeStatement();
        }
    }

    /**
     * Fetches the UIDs of all maximal versions for all extensions.
     * This is done by doing a LEFT JOIN to itself ("a" and "b") and comparing
     * both integer_version fields.
     *
     * @param string $remoteIdentifier
     * @return array
     */
    protected function fetchMaximalVersionsForAllExtensions(string $remoteIdentifier): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);

        $queryResult = $queryBuilder
            ->select('a.uid AS uid')
            ->from(self::TABLE_NAME, 'a')
            ->leftJoin(
                'a',
                self::TABLE_NAME,
                'b',
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('a.remote', $queryBuilder->quoteIdentifier('b.remote')),
                    $queryBuilder->expr()->eq('a.extension_key', $queryBuilder->quoteIdentifier('b.extension_key')),
                    $queryBuilder->expr()->lt('a.integer_version', $queryBuilder->quoteIdentifier('b.integer_version'))
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'a.remote',
                    $queryBuilder->createNamedParameter($remoteIdentifier)
                ),
                $queryBuilder->expr()->isNull('b.extension_key')
            )
            ->orderBy('a.uid')
            ->executeQuery();

        $extensionUids = [];
        while ($row = $queryResult->fetchAssociative()) {
            $extensionUids[] = $row['uid'];
        }

        return $extensionUids;
    }
}

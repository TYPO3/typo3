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

namespace TYPO3\CMS\Backend\View\BackendLayout;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Backend layout data provider class
 *
 * @internal Specific DataProviderInterface implementation, not considered public API.
 */
#[Autoconfigure(public: true)]
readonly class DefaultDataProvider implements DataProviderInterface
{
    private const DEFAULT_COLUMNS_LAYOUT = '
		backend_layout {
			colCount = 1
			rowCount = 1
			rows {
				1 {
					columns {
						1 {
							name = LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos.I.1
							colPos = 0
							identifier = main
						}
					}
				}
			}
		}
		';

    public function __construct(
        private FileRepository $fileRepository,
        private ConnectionPool $connectionPool,
        private TcaSchemaFactory $tcsSchemaFactory,
        private Context $context,
    ) {}

    /**
     * Adds backend layouts to the given backend layout collection.
     * The default backend layout ('default_default') is not added
     * since it's the default fallback if nothing is specified.
     */
    public function addBackendLayouts(
        DataProviderContext $dataProviderContext,
        BackendLayoutCollection $backendLayoutCollection
    ): void {
        $layoutData = $this->getLayoutData(
            $dataProviderContext->fieldName,
            $dataProviderContext->pageTsConfig,
            $dataProviderContext->pageId
        );
        foreach ($layoutData as $data) {
            $backendLayout = $this->createBackendLayout($data);
            $backendLayoutCollection->add($backendLayout);
        }
    }

    /**
     * Gets a backend layout by (regular) identifier.
     *
     * @param string|int $identifier
     * @param int $pageId
     */
    public function getBackendLayout($identifier, $pageId): ?BackendLayout
    {
        $backendLayout = null;
        if ((string)$identifier === 'default') {
            return $this->createDefaultBackendLayout();
        }
        $data = BackendUtility::getRecordWSOL('backend_layout', (int)$identifier);
        if (is_array($data)) {
            $backendLayout = $this->createBackendLayout($data);
        }
        return $backendLayout;
    }

    /**
     * Creates a backend layout with the default configuration.
     */
    protected function createDefaultBackendLayout(): BackendLayout
    {
        return BackendLayout::create(
            'default',
            'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.backend_layout.default',
            self::DEFAULT_COLUMNS_LAYOUT
        );
    }

    /**
     * Creates a new backend layout using the given record data.
     */
    protected function createBackendLayout(array $data): BackendLayout
    {
        $backendLayout = BackendLayout::create((string)$data['uid'], $data['title'], $data['config']);
        $backendLayout->setIconPath($this->getIconPath($data));
        $backendLayout->setData($data);
        return $backendLayout;
    }

    /**
     * Resolves the icon from the database record
     */
    protected function getIconPath(array $icon): string
    {
        $references = $this->fileRepository->findByRelation('backend_layout', 'icon', (int)$icon['uid']);
        if (!empty($references)) {
            $icon = reset($references);
            return $icon->getPublicUrl();
        }
        return '';
    }

    /**
     * Get all layouts from the core's default data provider.
     *
     * @param string $fieldName the name of the field the layouts are provided for (either backend_layout or backend_layout_next_level)
     * @param array $pageTsConfig PageTSconfig of the given page
     * @param int $pageUid the ID of the page wea re getting the layouts for
     * @return array $layouts A collection of layout data of the registered provider
     */
    protected function getLayoutData(string $fieldName, array $pageTsConfig, int $pageUid): array
    {
        // @todo: This depends on backend_layout TCA being available for both the query and
        //        TcaSchemaFactory. backend_layout TCA comes from ext:frontend, so we have
        //        an indirect cross dependency between ext:backend and ext:frontend here.
        //        There should be an explicit exception here when core is decoupled to run
        //        an instance with ext:backend but without ext:frontend, or backend_layout TCA
        //        should be relocated to ext:core? There are probably many more cases like this.

        $storagePid = $this->getStoragePid($pageTsConfig);
        $pageTsConfigId = $this->getPageTSconfigIds($pageTsConfig);

        // Add layout records
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('backend_layout');
        $queryBuilder->getRestrictions()
            ->add(
                GeneralUtility::makeInstance(
                    WorkspaceRestriction::class,
                    $this->context->getPropertyFromAspect('workspace', 'id')
                )
            );
        $queryBuilder
            ->select('*')
            ->from('backend_layout')
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->comparison(
                            $queryBuilder->createNamedParameter($pageTsConfigId[$fieldName], Connection::PARAM_INT),
                            ExpressionBuilder::EQ,
                            $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                        ),
                        $queryBuilder->expr()->comparison(
                            $queryBuilder->createNamedParameter($storagePid, Connection::PARAM_INT),
                            ExpressionBuilder::EQ,
                            $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                        )
                    ),
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->eq(
                            'backend_layout.pid',
                            $queryBuilder->createNamedParameter($pageTsConfigId[$fieldName], Connection::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            'backend_layout.pid',
                            $queryBuilder->createNamedParameter($storagePid, Connection::PARAM_INT)
                        )
                    ),
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->comparison(
                            $queryBuilder->createNamedParameter($pageTsConfigId[$fieldName], Connection::PARAM_INT),
                            ExpressionBuilder::EQ,
                            $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            'backend_layout.pid',
                            $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT)
                        )
                    )
                )
            );

        // Not catching UndefinedSchemaException here since backend_layout must exist at
        // this point, or the entire query would fail already.
        $schema = $this->tcsSchemaFactory->get('backend_layout');
        if ($schema->hasCapability(TcaSchemaCapability::SortByField)) {
            $queryBuilder->orderBy((string)$schema->getCapability(TcaSchemaCapability::SortByField));
        }

        $statement = $queryBuilder->executeQuery();

        $results = [];
        while ($record = $statement->fetchAssociative()) {
            BackendUtility::workspaceOL('backend_layout', $record);
            if (is_array($record)) {
                $results[$record['t3ver_oid'] ?: $record['uid']] = $record;
            }
        }

        return $results;
    }

    /**
     * Returns the storage PID from TCEFORM.
     */
    protected function getStoragePid(array $pageTsConfig): int
    {
        $storagePid = 0;

        if (!empty($pageTsConfig['TCEFORM.']['pages.']['_STORAGE_PID'])) {
            $storagePid = (int)$pageTsConfig['TCEFORM.']['pages.']['_STORAGE_PID'];
        }

        return $storagePid;
    }

    /**
     * Returns the page TSconfig from TCEFORM.
     */
    protected function getPageTSconfigIds(array $pageTsConfig): array
    {
        $pageTsConfigIds = [
            'backend_layout' => 0,
            'backend_layout_next_level' => 0,
        ];

        if (!empty($pageTsConfig['TCEFORM.']['pages.']['backend_layout.']['PAGE_TSCONFIG_ID'])) {
            $pageTsConfigIds['backend_layout'] = (int)$pageTsConfig['TCEFORM.']['pages.']['backend_layout.']['PAGE_TSCONFIG_ID'];
        }

        if (!empty($pageTsConfig['TCEFORM.']['pages.']['backend_layout_next_level.']['PAGE_TSCONFIG_ID'])) {
            $pageTsConfigIds['backend_layout_next_level'] = (int)$pageTsConfig['TCEFORM.']['pages.']['backend_layout_next_level.']['PAGE_TSCONFIG_ID'];
        }

        return $pageTsConfigIds;
    }
}

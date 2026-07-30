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

namespace TYPO3\CMS\Seo\XmlSitemap;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Schema\Capability\LanguageAwareSchemaCapability;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Seo\XmlSitemap\Exception\MissingConfigurationException;

/**
 * XmlSiteDataProvider will provide information for the XML sitemap for a specific database table
 * @internal this class is not part of TYPO3's Core API.
 */
class RecordsXmlSitemapDataProvider implements XmlSitemapDataProviderInterface
{
    public function __construct(
        protected readonly Context $context,
        protected readonly ConnectionPool $connectionPool,
        protected readonly PageRepository $pageRepository,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    public function getSitemap(XmlSitemapRequest $sitemapRequest): XmlSitemap
    {
        return XmlSitemap::forPage(
            $this->generateItems($sitemapRequest),
            $sitemapRequest->page,
            fn(array $item): array => $this->defineUrl($item, $sitemapRequest),
        );
    }

    /**
     * @throws MissingConfigurationException
     */
    protected function generateItems(XmlSitemapRequest $sitemapRequest): array
    {
        $configuration = $sitemapRequest->configuration;
        $table = $configuration['table'];
        if (!$this->tcaSchemaFactory->has($table)) {
            throw new MissingConfigurationException(
                'No configuration found for sitemap ' . $sitemapRequest->name,
                1535576053
            );
        }
        $schema = $this->tcaSchemaFactory->get($table);

        $pids = !empty($configuration['pid']) ? GeneralUtility::intExplode(',', (string)$configuration['pid']) : [];
        $lastModifiedField = $configuration['lastModifiedField'] ?? 'tstamp';
        $sortField = $configuration['sortField'] ?? 'sorting';

        $changeFreqField = $schema->hasField($configuration['changeFreqField'] ?? '') ? $configuration['changeFreqField'] : '';
        $priorityField = $schema->hasField($configuration['priorityField'] ?? '') ? $configuration['priorityField'] : '';

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);

        $constraints = [];

        if ($schema->isLanguageAware()) {
            /** @var LanguageAwareSchemaCapability $languageCapability */
            $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
            $constraints[] = $queryBuilder->expr()->in(
                $languageCapability->getLanguageField()->getName(),
                [
                    -1, // All languages
                    $this->getLanguageId(),  // Current language
                ]
            );
        }

        if (!empty($pids)) {
            $recursiveLevel = isset($configuration['recursive']) ? (int)$configuration['recursive'] : 0;
            $pids = $this->pageRepository->getPageIdsRecursive($pids, $recursiveLevel);
            $constraints[] = $queryBuilder->expr()->in('pid', $pids);
        }

        if (!empty($configuration['additionalWhere'])) {
            $constraints[] = QueryHelper::quoteDatabaseIdentifiers($queryBuilder->getConnection(), QueryHelper::stripLogicalOperatorPrefix($configuration['additionalWhere']));
        }

        $queryBuilder->getRestrictions()->add(
            new WorkspaceRestriction($this->getCurrentWorkspaceAspect()->getId())
        );

        $queryBuilder->select('*')
            ->from($table);

        if (!empty($constraints)) {
            $queryBuilder->where(
                ...$constraints
            );
        }

        $rows = $queryBuilder->orderBy($sortField)
            ->executeQuery()
            ->fetchAllAssociative();

        $items = [];
        foreach ($rows as $row) {
            $item = [
                'data' => $row,
                'lastMod' => (int)$row[$lastModifiedField],
            ];
            if (!empty($changeFreqField)) {
                $item['changefreq'] = $row[$changeFreqField];
            }
            $item['priority'] = !empty($priorityField) ? $row[$priorityField] : 0.5;
            $items[] = $item;
        }
        return $items;
    }

    protected function defineUrl(array $data, XmlSitemapRequest $sitemapRequest): array
    {
        $configuration = $sitemapRequest->configuration;
        $pageId = $sitemapRequest->request->getAttribute('frontend.page.information')->getId();
        $pageId = $configuration['url']['pageId'] ?? $pageId;
        $additionalParams = $this->getUrlFieldParameterMap($data['data'], $configuration);
        $additionalParams = $this->getUrlAdditionalParams($additionalParams, $configuration);
        $data['loc'] = $sitemapRequest->contentObjectRenderer->createUrl([
            'parameter' => $pageId,
            'queryParameters' => $additionalParams,
            'forceAbsoluteUrl' => 1,
        ]);

        return $data;
    }

    protected function getUrlFieldParameterMap(array $data, array $configuration): array
    {
        $additionalParams = [];
        if (!empty($configuration['url']['fieldToParameterMap'])
            && is_array($configuration['url']['fieldToParameterMap'])) {
            foreach ($configuration['url']['fieldToParameterMap'] as $field => $urlPart) {
                $paramValue = $data[$field];
                parse_str($urlPart . '=' . urlencode((string)$paramValue), $nested);
                $additionalParams = array_replace_recursive($additionalParams, $nested);
            }
        }
        return $additionalParams;
    }

    protected function getUrlAdditionalParams(array $additionalParams, array $configuration): array
    {
        if (!empty($configuration['url']['additionalGetParameters'])
            && is_array($configuration['url']['additionalGetParameters'])) {
            $additionalParams = array_replace_recursive($additionalParams, $configuration['url']['additionalGetParameters']);
        }
        return $additionalParams;
    }

    protected function getLanguageId(): int
    {
        return (int)$this->context->getPropertyFromAspect('language', 'id');
    }

    protected function getCurrentWorkspaceAspect(): WorkspaceAspect
    {
        return $this->context->getAspect('workspace');
    }
}

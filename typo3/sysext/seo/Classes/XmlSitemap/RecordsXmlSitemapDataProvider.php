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

use Psr\Http\Message\ServerRequestInterface;
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
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Seo\XmlSitemap\Exception\MissingConfigurationException;

/**
 * XmlSiteDataProvider will provide information for the XML sitemap for a specific database table
 * @internal this class is not part of TYPO3's Core API.
 */
class RecordsXmlSitemapDataProvider extends AbstractXmlSitemapDataProvider
{
    private TcaSchemaFactory $tcaSchemaFactory;

    public function __construct(ServerRequestInterface $request, string $key, array $config = [], ?ContentObjectRenderer $cObj = null)
    {
        parent::__construct($request, $key, $config, $cObj);
        $this->tcaSchemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
        $this->generateItems();
    }

    /**
     * @throws MissingConfigurationException
     */
    public function generateItems(): void
    {
        $table = $this->config['table'];
        if (!$this->tcaSchemaFactory->has($table)) {
            throw new MissingConfigurationException(
                'No configuration found for sitemap ' . $this->getKey(),
                1535576053
            );
        }
        $schema = $this->tcaSchemaFactory->get($table);

        $pids = !empty($this->config['pid']) ? GeneralUtility::intExplode(',', (string)$this->config['pid']) : [];
        $lastModifiedField = $this->config['lastModifiedField'] ?? 'tstamp';
        $sortField = $this->config['sortField'] ?? 'sorting';

        $changeFreqField = $schema->hasField($this->config['changeFreqField'] ?? '') ? $this->config['changeFreqField'] : '';
        $priorityField = $schema->hasField($this->config['priorityField'] ?? '') ? $this->config['priorityField'] : '';

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

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
            $recursiveLevel = isset($this->config['recursive']) ? (int)$this->config['recursive'] : 0;
            $pids = GeneralUtility::makeInstance(PageRepository::class)->getPageIdsRecursive($pids, $recursiveLevel);
            $constraints[] = $queryBuilder->expr()->in('pid', $pids);
        }

        if (!empty($this->config['additionalWhere'])) {
            $constraints[] = QueryHelper::quoteDatabaseIdentifiers($queryBuilder->getConnection(), QueryHelper::stripLogicalOperatorPrefix($this->config['additionalWhere']));
        }

        $queryBuilder->getRestrictions()->add(
            GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getCurrentWorkspaceAspect()->getId())
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

        foreach ($rows as $row) {
            $item = [
                'data' => $row,
                'lastMod' => (int)$row[$lastModifiedField],
            ];
            if (!empty($changeFreqField)) {
                $item['changefreq'] = $row[$changeFreqField];
            }
            $item['priority'] = !empty($priorityField) ? $row[$priorityField] : 0.5;
            $this->items[] = $item;
        }
    }

    protected function defineUrl(array $data): array
    {
        $pageId = $this->request->getAttribute('frontend.page.information')->getId();
        $pageId = $this->config['url']['pageId'] ?? $pageId;
        $additionalParams = [];

        $additionalParams = $this->getUrlFieldParameterMap($additionalParams, $data['data']);
        $additionalParams = $this->getUrlAdditionalParams($additionalParams);

        $additionalParamsString = http_build_query(
            $additionalParams,
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        $typoLinkConfig = [
            'parameter' => $pageId,
            'additionalParams' => $additionalParamsString ? '&' . $additionalParamsString : '',
            'forceAbsoluteUrl' => 1,
        ];

        $data['loc'] = $this->cObj->createUrl($typoLinkConfig);

        return $data;
    }

    protected function getUrlFieldParameterMap(array $additionalParams, array $data): array
    {
        if (!empty($this->config['url']['fieldToParameterMap']) &&
            \is_array($this->config['url']['fieldToParameterMap'])) {
            foreach ($this->config['url']['fieldToParameterMap'] as $field => $urlPart) {
                $additionalParams[$urlPart] = $data[$field];
            }
        }

        return $additionalParams;
    }

    protected function getUrlAdditionalParams(array $additionalParams): array
    {
        if (!empty($this->config['url']['additionalGetParameters']) &&
            is_array($this->config['url']['additionalGetParameters'])) {
            foreach ($this->config['url']['additionalGetParameters'] as $extension => $extensionConfig) {
                foreach ($extensionConfig as $key => $value) {
                    $additionalParams[$extension . '[' . $key . ']'] = $value;
                }
            }
        }

        return $additionalParams;
    }

    protected function getLanguageId(): int
    {
        $context = GeneralUtility::makeInstance(Context::class);
        return (int)$context->getPropertyFromAspect('language', 'id');
    }

    protected function getCurrentWorkspaceAspect(): WorkspaceAspect
    {
        return GeneralUtility::makeInstance(Context::class)->getAspect('workspace');
    }
}

<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Seo\XmlSitemap;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Seo\XmlSitemap\Exception\MissingConfigurationException;

/**
 * XmlSiteDataProvider will provide information for the XML sitemap for a specific database table
 */
class RecordsXmlSitemapDataProvider extends AbstractXmlSitemapDataProvider
{
    /**
     * @param string $key
     * @param array $config
     * @param ContentObjectRenderer|null $cObj
     * @throws MissingConfigurationException
     */
    public function __construct(ServerRequestInterface $request, string $key, array $config = [], ContentObjectRenderer $cObj = null)
    {
        parent::__construct($request, $key, $config, $cObj);

        $this->generateItems();
    }

    /**
     * @throws MissingConfigurationException
     */
    public function generateItems(): void
    {
        if (empty($this->config['table'])) {
            throw new MissingConfigurationException(
                'No configuration found for sitemap ' . $this->getKey(),
                1535576053
            );
        }

        $pids = GeneralUtility::intExplode(',', $this->config['pid']) ?? $GLOBALS['TSFE']->id;
        $lastModifiedField = $this->config['lastModifiedField'] ?? 'tstamp';
        $sortField = $this->config['sortField'] ?? 'sorting';

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->config['table']);

        $constraints = [
            $queryBuilder->expr()->in('pid', $pids)
        ];

        if (!empty($this->config['additionalWhere'])) {
            $constraints[] = $this->config['additionalWhere'];
        }

        $rows = $queryBuilder->select('*')
            ->from($this->config['table'])
            ->where(
                ...$constraints
            )
            ->orderBy($sortField)
            ->execute()
            ->fetchAll();

        foreach ($rows as $row) {
            $this->items[] = [
                'loc' => $this->defineUrl($row),
                'lastMod' => $row[$lastModifiedField]
            ];
        }
    }

    /**
     * @param array $data
     * @return string
     */
    protected function defineUrl(array $data): string
    {
        $pageId = $this->config['url']['pageId'] ?? $GLOBALS['TSFE']->id;
        $additionalParams = [];

        $additionalParams = $this->getUrlFieldParameterMap($additionalParams, $data);
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
            'useCacheHash' => $this->config['url']['useCacheHash'] ?? 0
        ];

        return $this->cObj->typoLink_URL($typoLinkConfig);
    }

    /**
     * @param array $additionalParams
     * @param array $data
     * @return array
     */
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

    /**
     * @param array $additionalParams
     * @return array
     */
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
}

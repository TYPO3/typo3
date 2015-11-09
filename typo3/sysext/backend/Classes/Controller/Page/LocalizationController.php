<?php
namespace TYPO3\CMS\Backend\Controller\Page;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * LocalizationController handles the AJAX requests for record localization
 */
class LocalizationController
{
    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Get used languages in a colPos of a page
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getUsedLanguagesInPageAndColumn(ServerRequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();
        if (!isset($params['pageId'], $params['colPos'], $params['languageId'])) {
            $response = $response->withStatus(500);
            return $response;
        }

        $pageId = (int)$params['pageId'];
        $colPos = (int)$params['colPos'];
        $languageId = (int)$params['languageId'];
        $databaseConnection = $this->getDatabaseConnection();
        $backendUser = $this->getBackendUser();

        /** @var TranslationConfigurationProvider $translationProvider */
        $translationProvider = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
        $systemLanguages = $translationProvider->getSystemLanguages($pageId);

        $availableLanguages = [];
        $availableLanguages[0] = $systemLanguages[0];

        $excludeQueryPart = BackendUtility::deleteClause('tt_content')
            . BackendUtility::versioningPlaceholderClause('tt_content');

        // First check whether column is empty and then load additional languages
        $elementsInColumnCount = $databaseConnection->exec_SELECTcountRows(
            'uid',
            'tt_content',
            'tt_content.sys_language_uid=' . $languageId
                . ' AND tt_content.colPos = ' . $colPos
                . ' AND tt_content.pid=' . $pageId
                . $excludeQueryPart
        );
        $additionalWhere = '';
        if (!$backendUser->isAdmin()) {
            $additionalWhere .= ' AND sys_language.hidden=0';

            if (!empty($backendUser->user['allowed_languages'])) {
                $additionalWhere .= ' AND sys_language.uid IN(' . $databaseConnection->cleanIntList($backendUser->user['allowed_languages']) . ')';
            }
        }
        if ($elementsInColumnCount === 0) {
            $res = $databaseConnection->exec_SELECTquery(
                'sys_language.uid',
                'tt_content,sys_language',
                'tt_content.sys_language_uid=sys_language.uid'
                    . ' AND tt_content.colPos = ' . $colPos
                    . ' AND tt_content.pid=' . $pageId
                    . ' AND sys_language.uid <> ' . $languageId
                    . $additionalWhere
                    . $excludeQueryPart,
                'tt_content.sys_language_uid',
                'sys_language.title'
            );
            while ($row = $databaseConnection->sql_fetch_assoc($res)) {
                $row['uid'] = (int)$row['uid'];
                if (isset($systemLanguages[$row['uid']])) {
                    $availableLanguages[] = $systemLanguages[$row['uid']];
                }
            }
            $databaseConnection->sql_free_result($res);
        }

        // Pre-render all flag icons
        foreach ($availableLanguages as &$language) {
            if ($language['flagIcon'] === 'empty-empty') {
                $language['flagIcon'] = '';
            } else {
                $language['flagIcon'] = $this->iconFactory->getIcon($language['flagIcon'], Icon::SIZE_SMALL)->render();
            }
        }

        $response->getBody()->write(json_encode($availableLanguages));
        return $response;
    }

    /**
     * Get a prepared summary of records being translated
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getRecordLocalizeSummary(ServerRequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();
        if (!isset($params['pageId'], $params['colPos'], $params['languageId'])) {
            $response = $response->withStatus(500);
            return $response;
        }

        $records = [];
        $databaseConnection = $this->getDatabaseConnection();
        $res = $this->getRecordsToCopyDatabaseResult($params['pageId'], $params['colPos'], $params['languageId'], '*');
        while ($row = $databaseConnection->sql_fetch_assoc($res)) {
            $records[] = [
                'icon' => $this->iconFactory->getIconForRecord('tt_content', $row, Icon::SIZE_SMALL)->render(),
                'title' => $row[$GLOBALS['TCA']['tt_content']['ctrl']['label']],
                'uid' => $row['uid']
            ];
        }
        $databaseConnection->sql_free_result($res);

        $response->getBody()->write(json_encode($records));
        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getRecordUidsToCopy(ServerRequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();
        if (!isset($params['pageId'], $params['colPos'], $params['languageId'])) {
            $response = $response->withStatus(500);
            return $response;
        }

        $pageId = (int)$params['pageId'];
        $colPos = (int)$params['colPos'];
        $languageId = (int)$params['languageId'];
        $databaseConnection = $this->getDatabaseConnection();

        $res = $this->getRecordsToCopyDatabaseResult($pageId, $colPos, $languageId, 'uid');
        $uids = [];
        while ($row = $databaseConnection->sql_fetch_assoc($res)) {
            $uids[] = (int)$row['uid'];
        }
        $databaseConnection->sql_free_result($res);

        $response->getBody()->write(json_encode($uids));
        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function localizeRecords(ServerRequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParams();
        if (!isset($params['pageId'], $params['srcLanguageId'], $params['destLanguageId'], $params['action'], $params['uidList'])) {
            $response = $response->withStatus(500);
            return $response;
        }

        if ($params['action'] !== 'copyFromLanguage' && $params['action'] !== 'localize') {
            $response->getBody()->write('Invalid action "' . $params['action'] . '" called.');
            $response = $response->withStatus(500);
            return $response;
        }

        $pageId = (int)$params['pageId'];
        $srcLanguageId = (int)$params['srcLanguageId'];
        $destLanguageId = (int)$params['destLanguageId'];
        $params['uidList'] = array_reverse($params['uidList']);

        // Build command map
        $cmd = [
            'tt_content' => []
        ];

        for ($i = 0, $count = count($params['uidList']); $i < $count; ++$i) {
            $currentUid = $params['uidList'][$i];

            if ($params['action'] === 'localize') {
                if ($srcLanguageId === 0) {
                    $cmd['tt_content'][$currentUid] = [
                        'localize' => $destLanguageId
                    ];
                } else {
                    $cmd['tt_content'][$currentUid] = [
                        'copy' => [
                            'action' => 'paste',
                            'target' => $pageId,
                            'update' => [
                                'sys_language_uid' => $destLanguageId
                            ]
                        ]
                    ];
                }
            } else {
                $cmd['tt_content'][$currentUid] = [
                    'copy' => [
                        'action' => 'paste',
                        'target' => $pageId,
                        'update' => [
                            'sys_language_uid' => $destLanguageId,
                            'l18n_parent' => 0
                        ]
                    ]
                ];
            }
        }

        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        $response->getBody()->write(json_encode([]));
        return $response;
    }

    /**
     * Get records for copy process
     *
     * @param int $pageId
     * @param int $colPos
     * @param int $languageId
     * @param string $fields
     * @return bool|\mysqli_result|object
     */
    protected function getRecordsToCopyDatabaseResult($pageId, $colPos, $languageId, $fields = '*')
    {
        return $this->getDatabaseConnection()->exec_SELECTquery(
            $fields,
            'tt_content',
            'tt_content.sys_language_uid=' . (int)$languageId
            . ' AND tt_content.colPos = ' . (int)$colPos
            . ' AND tt_content.pid=' . (int)$pageId
            . BackendUtility::deleteClause('tt_content')
            . BackendUtility::versioningPlaceholderClause('tt_content'),
            '',
            'tt_content.sorting'
        );
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns the database connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
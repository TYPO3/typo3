<?php
declare(strict_types = 1);

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
use TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * LocalizationController handles the AJAX requests for record localization
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class LocalizationController
{
    /**
     * @var string
     */
    const ACTION_COPY = 'copyFromLanguage';

    /**
     * @var string
     */
    const ACTION_LOCALIZE = 'localize';

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var LocalizationRepository
     */
    protected $localizationRepository;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->localizationRepository = GeneralUtility::makeInstance(LocalizationRepository::class);
    }

    /**
     * Get used languages in a page
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getUsedLanguagesInPage(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        if (!isset($params['pageId'], $params['languageId'])) {
            return new JsonResponse(null, 400);
        }

        $pageId = (int)$params['pageId'];
        $languageId = (int)$params['languageId'];

        /** @var TranslationConfigurationProvider $translationProvider */
        $translationProvider = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
        $systemLanguages = $translationProvider->getSystemLanguages($pageId);

        $availableLanguages = [];

        // First check whether column has localized records
        $elementsInColumnCount = $this->localizationRepository->getLocalizedRecordCount($pageId, $languageId);

        if ($elementsInColumnCount === 0) {
            $fetchedAvailableLanguages = $this->localizationRepository->fetchAvailableLanguages($pageId, $languageId);
            $availableLanguages[] = $systemLanguages[0];

            foreach ($fetchedAvailableLanguages as $language) {
                if (isset($systemLanguages[$language['uid']])) {
                    $availableLanguages[] = $systemLanguages[$language['uid']];
                }
            }
        } else {
            $result = $this->localizationRepository->fetchOriginLanguage($pageId, $languageId);
            $availableLanguages[] = $systemLanguages[$result['sys_language_uid']];
        }

        // Pre-render all flag icons
        foreach ($availableLanguages as &$language) {
            if ($language['flagIcon'] === 'empty-empty') {
                $language['flagIcon'] = '';
            } else {
                $language['flagIcon'] = $this->iconFactory->getIcon($language['flagIcon'], Icon::SIZE_SMALL)->render();
            }
        }

        return (new JsonResponse())->setPayload($availableLanguages);
    }

    /**
     * Get a prepared summary of records being translated
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getRecordLocalizeSummary(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        if (!isset($params['pageId'], $params['destLanguageId'], $params['languageId'])) {
            return new JsonResponse(null, 400);
        }

        $pageId = (int)$params['pageId'];
        $destLanguageId = (int)$params['destLanguageId'];
        $languageId = (int)$params['languageId'];

        $records = [];
        $result = $this->localizationRepository->getRecordsToCopyDatabaseResult(
            $pageId,
            $destLanguageId,
            $languageId,
            '*'
        );

        while ($row = $result->fetch()) {
            BackendUtility::workspaceOL('tt_content', $row, -99, true);
            if (!$row || VersionState::cast($row['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
                continue;
            }
            $colPos = $row['colPos'];
            if (!isset($records[$colPos])) {
                $records[$colPos] = [];
            }
            $records[$colPos][] = [
                'icon' => $this->iconFactory->getIconForRecord('tt_content', $row, Icon::SIZE_SMALL)->render(),
                'title' => $row[$GLOBALS['TCA']['tt_content']['ctrl']['label']],
                'uid' => $row['uid']
            ];
        }

        return (new JsonResponse())->setPayload([
            'records' => $records,
            'columns' => $this->getPageColumns($pageId),
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function localizeRecords(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        if (!isset($params['pageId'], $params['srcLanguageId'], $params['destLanguageId'], $params['action'], $params['uidList'])) {
            return new JsonResponse(null, 400);
        }

        if ($params['action'] !== static::ACTION_COPY && $params['action'] !== static::ACTION_LOCALIZE) {
            $response = new Response('php://temp', 400, ['Content-Type' => 'application/json; charset=utf-8']);
            $response->getBody()->write('Invalid action "' . $params['action'] . '" called.');
            return $response;
        }

        // Filter transmitted but invalid uids
        $params['uidList'] = $this->filterInvalidUids(
            (int)$params['pageId'],
            (int)$params['destLanguageId'],
            (int)$params['srcLanguageId'],
            $params['uidList']
        );

        $this->process($params);

        return (new JsonResponse())->setPayload([]);
    }

    /**
     * Gets all possible UIDs of a page, colPos and language that might be processed and removes invalid UIDs that might
     * be smuggled in.
     *
     * @param int $pageId
     * @param int $destLanguageId
     * @param int $srcLanguageId
     * @param array $transmittedUidList
     * @return array
     */
    protected function filterInvalidUids(
        int $pageId,
        int $destLanguageId,
        int $srcLanguageId,
        array $transmittedUidList
    ): array {
        // Get all valid uids that can be processed
        $validUidList = $this->localizationRepository->getRecordsToCopyDatabaseResult(
            $pageId,
            $destLanguageId,
            $srcLanguageId,
            'uid'
        );

        return array_intersect(array_unique($transmittedUidList), array_column($validUidList->fetchAll(), 'uid'));
    }

    /**
     * Processes the localization actions
     *
     * @param array $params
     */
    protected function process($params): void
    {
        $destLanguageId = (int)$params['destLanguageId'];

        // Build command map
        $cmd = [
            'tt_content' => []
        ];

        if (isset($params['uidList']) && is_array($params['uidList'])) {
            foreach ($params['uidList'] as $currentUid) {
                if ($params['action'] === static::ACTION_LOCALIZE) {
                    $cmd['tt_content'][$currentUid] = [
                        'localize' => $destLanguageId
                    ];
                } else {
                    $cmd['tt_content'][$currentUid] = [
                        'copyToLanguage' => $destLanguageId,
                    ];
                }
            }
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();
    }

    /**
     * @param int $pageId
     * @return array
     */
    protected function getPageColumns(int $pageId): array
    {
        $columns = [];
        $backendLayoutView = GeneralUtility::makeInstance(BackendLayoutView::class);
        $backendLayouts = $backendLayoutView->getSelectedBackendLayout($pageId);

        foreach ($backendLayouts['__items'] as $backendLayout) {
            $columns[(int)$backendLayout[1]] = $backendLayout[0];
        }

        return [
            'columns' => $columns,
            'columnList' => $backendLayouts['__colPosList'],
        ];
    }
}

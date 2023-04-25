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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Configuration\SiteTcaConfiguration;
use TYPO3\CMS\Backend\Exception\SiteValidationErrorException;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\SiteConfigurationDataGroup;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Exception\SiteConfigurationWriteException;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\SysLog\Action\Site as SiteAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Backend controller: The "Site management" -> "Sites" module
 *
 * List all site root pages, CRUD site configuration.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Controller]
class SiteConfigurationController
{
    public function __construct(
        protected readonly SiteFinder $siteFinder,
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly SiteConfiguration $siteConfiguration,
    ) {
    }

    /**
     * List pages that have 'is_siteroot' flag set - those that have the globe icon in page tree.
     * Link to Add / Edit / Delete for each.
     */
    public function overviewAction(ServerRequestInterface $request): ResponseInterface
    {
        // forcing uncached sites will re-initialize `SiteFinder`
        // which is used later by FormEngine (implicit behavior)
        $allSites = $this->siteFinder->getAllSites(false);
        $pages = $this->getAllSitePages();
        $unassignedSites = [];
        $duplicatedRootPages = [];
        foreach ($allSites as $identifier => $site) {
            $rootPageId = $site->getRootPageId();
            if (isset($pages[$rootPageId]['siteConfiguration'])) {
                // rootPage is already used in a site configuration
                $duplicatedRootPages[$rootPageId][] = $pages[$rootPageId]['siteConfiguration']->getIdentifier();
                $duplicatedRootPages[$rootPageId][] = $site->getIdentifier();
                $duplicatedRootPages[$rootPageId] = array_unique($duplicatedRootPages[$rootPageId]);
            }
            if (isset($pages[$rootPageId])) {
                $pages[$rootPageId]['siteIdentifier'] = $identifier;
                $pages[$rootPageId]['siteConfiguration'] = $site;
            } else {
                $unassignedSites[] = $site;
            }
        }

        $view = $this->moduleTemplateFactory->create($request);
        $this->configureOverViewDocHeader($view, $request->getAttribute('normalizedParams')->getRequestUri());
        $view->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_module.xlf:mlang_tabs_tab')
        );
        $view->assignMultiple([
            'pages' => $pages,
            'unassignedSites' => $unassignedSites,
            'duplicatedRootPages' => $duplicatedRootPages,
            'duplicatedEntryPoints' => $this->getDuplicatedEntryPoints($allSites, $pages),
        ]);
        return $view->renderResponse('SiteConfiguration/Overview');
    }

    /**
     * Shows a form to create a new site configuration, or edit an existing one.
     *
     * @throws \RuntimeException
     */
    public function editAction(ServerRequestInterface $request): ResponseInterface
    {
        // forcing uncached sites will re-initialize `SiteFinder`
        // which is used later by FormEngine (implicit behavior)
        $allSites = $this->siteFinder->getAllSites(false);

        // Put site and friends TCA into global TCA
        // @todo: We might be able to get rid of that later
        $GLOBALS['TCA'] = array_merge($GLOBALS['TCA'], GeneralUtility::makeInstance(SiteTcaConfiguration::class)->getTca());

        $siteIdentifier = $request->getQueryParams()['site'] ?? null;
        $pageUid = (int)($request->getQueryParams()['pageUid'] ?? 0);

        if (empty($siteIdentifier) && empty($pageUid)) {
            throw new \RuntimeException('Either site identifier to edit a config or page uid to add new config must be set', 1521561148);
        }
        $isNewConfig = empty($siteIdentifier);

        $defaultValues = [];
        if ($isNewConfig) {
            $defaultValues['site']['rootPageId'] = $pageUid;
        }

        if (!$isNewConfig && !isset($allSites[$siteIdentifier])) {
            throw new \RuntimeException('Existing config for site ' . $siteIdentifier . ' not found', 1521561226);
        }

        $returnUrl = $this->uriBuilder->buildUriFromRoute('site_configuration');

        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class);
        $formDataCompilerInput = [
            'request' => $request,
            'tableName' => 'site',
            'vanillaUid' => $isNewConfig ? $pageUid : $allSites[$siteIdentifier]->getRootPageId(),
            'command' => $isNewConfig ? 'new' : 'edit',
            'returnUrl' => (string)$returnUrl,
            'customData' => [
                'siteIdentifier' => $isNewConfig ? '' : $siteIdentifier,
            ],
            'defaultValues' => $defaultValues,
        ];
        $formData = $formDataCompiler->compile($formDataCompilerInput, GeneralUtility::makeInstance(SiteConfigurationDataGroup::class));
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $formData['renderType'] = 'outerWrapContainer';
        $formResult = $nodeFactory->create($formData)->render();
        // Needed to be set for 'onChange="reload"' and reload on type change to work
        $formResult['doSaveFieldName'] = 'doSave';
        $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
        $formResultCompiler->mergeResult($formResult);
        $formResultCompiler->addCssFiles();

        $view = $this->moduleTemplateFactory->create($request);
        $view->assignMultiple([
            // Always add rootPageId as additional field to have a reference for new records
            'rootPageId' => $isNewConfig ? $pageUid : $allSites[$siteIdentifier]->getRootPageId(),
            'returnUrl' => $returnUrl,
            'formEngineHtml' => $formResult['html'],
            'formEngineFooter' => $formResultCompiler->printNeededJSFunctions(),
        ]);

        $this->configureEditViewDocHeader($view);
        $view->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_module.xlf:mlang_tabs_tab'),
            $siteIdentifier ?? ''
        );
        return $view->renderResponse('SiteConfiguration/Edit');
    }

    /**
     * Save incoming data from editAction and redirect to overview or edit
     *
     * @throws \RuntimeException
     */
    public function saveAction(ServerRequestInterface $request): ResponseInterface
    {
        // loading uncached site configurations without settings.yaml
        /** @var array<int, Site> $mappingRootPageToSite */
        $mappingRootPageToSite = [];
        $allSites = $this->siteConfiguration->resolveAllExistingSitesRaw();
        foreach ($allSites as $site) {
            $mappingRootPageToSite[$site->getRootPageId()] = $site;
        }

        // Put site and friends TCA into global TCA
        // @todo We might be able to get rid of that later
        $GLOBALS['TCA'] = array_merge($GLOBALS['TCA'], GeneralUtility::makeInstance(SiteTcaConfiguration::class)->getTca());

        $siteTca = GeneralUtility::makeInstance(SiteTcaConfiguration::class)->getTca();

        $overviewRoute = $this->uriBuilder->buildUriFromRoute('site_configuration');
        $parsedBody = $request->getParsedBody();
        if (isset($parsedBody['closeDoc']) && (int)$parsedBody['closeDoc'] === 1) {
            // Closing means no save, just redirect to overview
            return new RedirectResponse($overviewRoute);
        }
        $isSave = $parsedBody['_savedok'] ?? $parsedBody['doSave'] ?? false;
        $isSaveClose = $parsedBody['_saveandclosedok'] ?? false;
        if (!$isSave && !$isSaveClose) {
            throw new \RuntimeException('Either save or save and close', 1520370364);
        }

        if (!isset($parsedBody['data']['site']) || !is_array($parsedBody['data']['site'])) {
            throw new \RuntimeException('No site data or site identifier given', 1521030950);
        }

        $data = $parsedBody['data'];
        // This can be NEW123 for new records
        $pageId = (int)key($data['site']);
        $sysSiteRow = current($data['site']);
        $siteIdentifier = $sysSiteRow['identifier'] ?? '';

        $isNewConfiguration = false;
        $currentIdentifier = '';
        if (isset($mappingRootPageToSite[$pageId])) {
            $currentSite = $mappingRootPageToSite[$pageId];
            $currentSiteConfiguration = $currentSite->getConfiguration();
            $currentIdentifier = $currentSite->getIdentifier();
        } else {
            $currentSiteConfiguration = [];
            $isNewConfiguration = true;
            $pageId = (int)$parsedBody['rootPageId'];
            if ($pageId <= 0) {
                // Early validation of rootPageId - it must always be given and greater than 0
                throw new \RuntimeException('No root page id found', 1521719709);
            }
        }

        // Validate site identifier and do not store or further process it
        $siteIdentifier = $this->validateAndProcessIdentifier($isNewConfiguration, $siteIdentifier, $pageId, $allSites, $mappingRootPageToSite);
        unset($sysSiteRow['identifier']);

        try {
            $newSysSiteData = [];
            // Hard set rootPageId: This is TCA readOnly and not transmitted by FormEngine, but is also the "uid" of the site record
            $newSysSiteData['rootPageId'] = $pageId;
            foreach ($sysSiteRow as $fieldName => $fieldValue) {
                $type = $siteTca['site']['columns'][$fieldName]['config']['type'];
                switch ($type) {
                    case 'input':
                    case 'number':
                    case 'email':
                    case 'link':
                    case 'datetime':
                    case 'color':
                    case 'text':
                        $fieldValue = $this->validateAndProcessValue('site', $fieldName, $fieldValue);
                        $newSysSiteData[$fieldName] = $fieldValue;
                        break;

                    case 'inline':
                        $newSysSiteData[$fieldName] = [];
                        $childRowIds = GeneralUtility::trimExplode(',', $fieldValue, true);
                        if (!isset($siteTca['site']['columns'][$fieldName]['config']['foreign_table'])) {
                            throw new \RuntimeException('No foreign_table found for inline type', 1521555037);
                        }
                        $foreignTable = $siteTca['site']['columns'][$fieldName]['config']['foreign_table'];
                        foreach ($childRowIds as $childRowId) {
                            $childRowData = [];
                            if (!isset($data[$foreignTable][$childRowId])) {
                                if (!empty($currentSiteConfiguration[$fieldName][$childRowId])) {
                                    // A collapsed inline record: Fetch data from existing config
                                    $newSysSiteData[$fieldName][] = $currentSiteConfiguration[$fieldName][$childRowId];
                                    continue;
                                }
                                throw new \RuntimeException('No data found for table ' . $foreignTable . ' with id ' . $childRowId, 1521555177);
                            }
                            $childRow = $data[$foreignTable][$childRowId];
                            foreach ($childRow as $childFieldName => $childFieldValue) {
                                if ($childFieldName === 'pid') {
                                    // pid is added by inline by default, but not relevant for yml storage
                                    continue;
                                }
                                $type = $siteTca[$foreignTable]['columns'][$childFieldName]['config']['type'];
                                switch ($type) {
                                    case 'input':
                                    case 'number':
                                    case 'email':
                                    case 'link':
                                    case 'datetime':
                                    case 'color':
                                    case 'select':
                                    case 'text':
                                        $childRowData[$childFieldName] = $childFieldValue;
                                        break;
                                    case 'check':
                                        $childRowData[$childFieldName] = (bool)$childFieldValue;
                                        break;
                                    default:
                                        throw new \RuntimeException('TCA type ' . $type . ' not implemented in site handling', 1521555340);
                                }
                            }
                            $newSysSiteData[$fieldName][] = $childRowData;
                        }
                        break;

                    case 'siteLanguage':
                        if (!isset($siteTca['site_language'])) {
                            throw new \RuntimeException('Required foreign table site_language does not exist', 1624286811);
                        }
                        if (!isset($siteTca['site_language']['columns']['languageId'])
                            || ($siteTca['site_language']['columns']['languageId']['config']['type'] ?? '') !== 'select'
                        ) {
                            throw new \RuntimeException(
                                'Required foreign field languageId does not exist or is not of type select',
                                1624286812
                            );
                        }
                        $newSysSiteData[$fieldName] = [];
                        $lastLanguageId = $this->getLastLanguageId();
                        foreach (GeneralUtility::trimExplode(',', $fieldValue, true) as $childRowId) {
                            if (!isset($data['site_language'][$childRowId])) {
                                if (!empty($currentSiteConfiguration[$fieldName][$childRowId])) {
                                    $newSysSiteData[$fieldName][] = $currentSiteConfiguration[$fieldName][$childRowId];
                                    continue;
                                }
                                throw new \RuntimeException('No data found for table site_language with id ' . $childRowId, 1624286813);
                            }
                            $childRowData = [];
                            foreach ($data['site_language'][$childRowId] ?? [] as $childFieldName => $childFieldValue) {
                                if ($childFieldName === 'pid') {
                                    // pid is added by default, but not relevant for yml storage
                                    continue;
                                }
                                if ($childFieldName === 'languageId'
                                    && (int)$childFieldValue === PHP_INT_MAX
                                    && str_starts_with($childRowId, 'NEW')
                                ) {
                                    // In case we deal with a new site language, whose "languageID" field is
                                    // set to the PHP_INT_MAX placeholder, the next available language ID has
                                    // to be used (auto-increment).
                                    $childRowData[$childFieldName] = ++$lastLanguageId;
                                    continue;
                                }
                                $type = $siteTca['site_language']['columns'][$childFieldName]['config']['type'];
                                switch ($type) {
                                    case 'input':
                                    case 'number':
                                    case 'email':
                                    case 'link':
                                    case 'datetime':
                                    case 'color':
                                    case 'select':
                                    case 'text':
                                        $childRowData[$childFieldName] = $childFieldValue;
                                        break;
                                    case 'check':
                                        $childRowData[$childFieldName] = (bool)$childFieldValue;
                                        break;
                                    default:
                                        throw new \RuntimeException('TCA type ' . $type . ' not implemented in site handling', 1624286814);
                                }
                            }
                            $newSysSiteData[$fieldName][] = $childRowData;
                        }
                        break;

                    case 'select':
                        if (MathUtility::canBeInterpretedAsInteger($fieldValue)) {
                            $fieldValue = (int)$fieldValue;
                        } elseif (is_array($fieldValue)) {
                            $fieldValue = implode(',', $fieldValue);
                        }

                        $newSysSiteData[$fieldName] = $fieldValue;
                        break;

                    case 'check':
                        $newSysSiteData[$fieldName] = (bool)$fieldValue;
                        break;

                    default:
                        throw new \RuntimeException('TCA type "' . $type . '" is not implemented in site handling', 1521032781);
                }
            }

            $newSiteConfiguration = $this->validateFullStructure(
                $this->getMergeSiteData($currentSiteConfiguration, $newSysSiteData),
                $isNewConfiguration
            );

            // Persist the configuration
            $siteConfigurationManager = GeneralUtility::makeInstance(SiteConfiguration::class);
            try {
                if (!$isNewConfiguration && $currentIdentifier !== $siteIdentifier) {
                    $siteConfigurationManager->rename($currentIdentifier, $siteIdentifier);
                    $this->getBackendUser()->writelog(Type::SITE, SiteAction::RENAME, SystemLogErrorClassification::MESSAGE, 0, 'Site configuration \'%s\' was renamed to \'%s\'.', [$currentIdentifier, $siteIdentifier], 'site');
                }
                $siteConfigurationManager->write($siteIdentifier, $newSiteConfiguration, true);
                if ($isNewConfiguration) {
                    $this->getBackendUser()->writelog(Type::SITE, SiteAction::CREATE, SystemLogErrorClassification::MESSAGE, 0, 'Site configuration \'%s\' was created.', [$siteIdentifier], 'site');
                } else {
                    $this->getBackendUser()->writelog(Type::SITE, SiteAction::UPDATE, SystemLogErrorClassification::MESSAGE, 0, 'Site configuration \'%s\' was updated.', [$siteIdentifier], 'site');
                }
            } catch (SiteConfigurationWriteException $e) {
                $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $e->getMessage(), '', ContextualFeedbackSeverity::WARNING, true);
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $defaultFlashMessageQueue->enqueue($flashMessage);
            }
        } catch (SiteValidationErrorException $e) {
            // Do not store new config if a validation error is thrown, but redirect only to show a generated flash message
        }

        $saveRoute = $this->uriBuilder->buildUriFromRoute('site_configuration.edit', ['site' => $siteIdentifier]);
        if ($isSaveClose) {
            return new RedirectResponse($overviewRoute);
        }
        return new RedirectResponse($saveRoute);
    }

    /**
     * Validation and processing of site identifier
     *
     * @param bool $isNew If true, we're dealing with a new record
     * @param string $identifier Given identifier to validate and process
     * @param int $rootPageId Page uid this identifier is bound to
     * @param array<non-empty-string, Site> $allSites All sites loaded without `settings.yaml`.
     * @param array<int, Site> $mappingRootPageToSite Identifier site mapping as lookup. Not loaded `settings.yaml`.
     * @return mixed Verified / modified value
     */
    protected function validateAndProcessIdentifier(bool $isNew, string $identifier, int $rootPageId, array $allSites, array $mappingRootPageToSite)
    {
        $languageService = $this->getLanguageService();
        // Normal "eval" processing of field first
        $identifier = $this->validateAndProcessValue('site', 'identifier', $identifier);
        if ($isNew) {
            // Verify no other site with this identifier exists. If so, find a new unique name as
            // identifier and show a flash message the identifier has been adapted
            if (($allSites[$identifier] ?? null) instanceof Site) {
                // Force this identifier to be unique
                $originalIdentifier = $identifier;
                $identifier = StringUtility::getUniqueId($identifier . '-');
                $message = sprintf(
                    $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration.xlf:validation.identifierRenamed.message'),
                    $originalIdentifier,
                    $identifier
                );
                $messageTitle = $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration.xlf:validation.identifierRenamed.title');
                $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $messageTitle, ContextualFeedbackSeverity::WARNING, true);
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $defaultFlashMessageQueue->enqueue($flashMessage);
            }
        } else {
            // If this is an existing config, the site for this identifier must have the same rootPageId, otherwise
            // a user tried to rename a site identifier to a different site that already exists. If so, we do not rename
            // the site and show a flash message
            $site = ($allSites[$identifier] ?? null);
            if ($site instanceof Site
                && $site->getRootPageId() !== $rootPageId
                && ($mappingRootPageToSite[$rootPageId] ?? null) instanceof Site
            ) {
                // Find original value and keep this
                $origSite = $mappingRootPageToSite[$rootPageId];
                $originalIdentifier = $identifier;
                $identifier = $origSite->getIdentifier();
                $message = sprintf(
                    $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration.xlf:validation.identifierExists.message'),
                    $originalIdentifier,
                    $identifier
                );
                $messageTitle = $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration.xlf:validation.identifierExists.title');
                $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $messageTitle, ContextualFeedbackSeverity::WARNING, true);
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $defaultFlashMessageQueue->enqueue($flashMessage);
            }
        }
        return $identifier;
    }

    /**
     * Simple validation and processing method for incoming form field values.
     *
     * Note this does not support all TCA "eval" options but only what we really need.
     *
     * @param string $tableName Table name
     * @param string $fieldName Field name
     * @param mixed $fieldValue Incoming value from FormEngine
     * @return mixed Verified / modified value
     * @throws SiteValidationErrorException
     * @throws \RuntimeException
     */
    protected function validateAndProcessValue(string $tableName, string $fieldName, $fieldValue)
    {
        $languageService = $this->getLanguageService();
        $fieldConfig = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'];
        $handledEvals = [];

        if (!$this->validateValueForRequired($fieldConfig, $fieldValue)) {
            // Validation throws - these should be handled client side already,
            // eg. 'required' being set and receiving empty, shouldn't happen server side
            $message = sprintf(
                $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration.xlf:validation.required.message'),
                $fieldName
            );
            $messageTitle = $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration.xlf:validation.required.title');
            $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $messageTitle, ContextualFeedbackSeverity::WARNING, true);
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
            throw new SiteValidationErrorException(
                'Field ' . $fieldName . ' is set to required, but received empty.',
                1521726421
            );
        }

        if (!empty($fieldConfig['eval'])) {
            $evalArray = GeneralUtility::trimExplode(',', $fieldConfig['eval'], true);
            // Processing
            if (in_array('alphanum_x', $evalArray, true)) {
                $handledEvals[] = 'alphanum_x';
                $fieldValue = preg_replace('/[^a-zA-Z0-9_-]/', '', $fieldValue);
            }
            if (in_array('lower', $evalArray, true)) {
                $handledEvals[] = 'lower';
                $fieldValue = mb_strtolower($fieldValue, 'utf-8');
            }
            if (in_array('trim', $evalArray, true)) {
                $handledEvals[] = 'trim';
                $fieldValue = trim($fieldValue);
            }
            if (in_array('int', $evalArray, true)) {
                $handledEvals[] = 'int';
                $fieldValue = (int)$fieldValue;
            }
            if (!empty(array_diff($evalArray, $handledEvals))) {
                throw new \RuntimeException('At least one not implemented \'eval\' in list ' . $fieldConfig['eval'], 1522491734);
            }
        }
        if (isset($fieldConfig['range']['lower'])) {
            $fieldValue = (int)$fieldValue < (int)$fieldConfig['range']['lower'] ? (int)$fieldConfig['range']['lower'] : (int)$fieldValue;
        }
        if (isset($fieldConfig['range']['upper'])) {
            $fieldValue = (int)$fieldValue > (int)$fieldConfig['range']['upper'] ? (int)$fieldConfig['range']['upper'] : (int)$fieldValue;
        }
        return $fieldValue;
    }

    /**
     * Last sanitation method after all data has been gathered. Check integrity
     * of full record, manipulate if possible, or throw exception if unfixable broken.
     *
     * @param array $newSysSiteData Incoming data
     * @param bool $isNewConfiguration Flag whether site configuration is new
     * @return array Updated data if needed
     * @throws \RuntimeException
     */
    protected function validateFullStructure(array $newSysSiteData, bool $isNewConfiguration): array
    {
        $languageService = $this->getLanguageService();
        // Verify there are not two error handlers with the same error code
        if (isset($newSysSiteData['errorHandling']) && is_array($newSysSiteData['errorHandling'])) {
            $uniqueCriteria = [];
            $validChildren = [];
            foreach ($newSysSiteData['errorHandling'] as $child) {
                if (!isset($child['errorCode'])) {
                    throw new \RuntimeException('No errorCode found', 1521788518);
                }
                if (!in_array((int)$child['errorCode'], $uniqueCriteria, true)) {
                    $uniqueCriteria[] = (int)$child['errorCode'];
                    $child['errorCode'] = (int)$child['errorCode'];
                    $validChildren[] = $child;
                } else {
                    $message = sprintf(
                        $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration.xlf:validation.duplicateErrorCode.message'),
                        $child['errorCode']
                    );
                    $messageTitle = $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration.xlf:validation.duplicateErrorCode.title');
                    $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $messageTitle, ContextualFeedbackSeverity::WARNING, true);
                    $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                    $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                    $defaultFlashMessageQueue->enqueue($flashMessage);
                }
            }
            $newSysSiteData['errorHandling'] = $validChildren;
        }

        // Verify there is at least one site_language element configured.
        if (!isset($newSysSiteData['languages']) || !is_array($newSysSiteData['languages']) || count($newSysSiteData['languages']) < 1) {
            throw new \RuntimeException(
                'No default language definition found. The interface does not allow this. Aborting',
                1521789306
            );
        }
        $uniqueCriteria = [];
        $validChildren = [];
        foreach ($newSysSiteData['languages'] as $child) {
            if (!isset($child['languageId'])) {
                throw new \RuntimeException('languageId not found', 1521789455);
            }
            if (!in_array((int)$child['languageId'], $uniqueCriteria, true)) {
                $uniqueCriteria[] = (int)$child['languageId'];
                $child['languageId'] = (int)$child['languageId'];
                $validChildren[] = $child;
            } else {
                $message = sprintf(
                    $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration.xlf:validation.duplicateLanguageId.title'),
                    $child['languageId']
                );
                $messageTitle = $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration.xlf:validation.duplicateLanguageId.title');
                $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $messageTitle, ContextualFeedbackSeverity::WARNING, true);
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $defaultFlashMessageQueue->enqueue($flashMessage);
            }
        }
        // On new site configurations, ensure that the only existing language has the languageId set to 0
        // @todo: this shouldn't be done here, but rather properly handled in saveAction() where 'siteLanguage' is handled
        if ($isNewConfiguration && count($validChildren) === 1) {
            $validChildren[0]['languageId'] = 0;
        }
        $newSysSiteData['languages'] = $validChildren;

        // cleanup configuration
        foreach ($newSysSiteData as $identifier => $value) {
            if (is_array($value) && empty($value)) {
                unset($newSysSiteData[$identifier]);
            }
        }

        return $newSysSiteData;
    }

    /**
     * Delete an existing configuration
     */
    public function deleteAction(ServerRequestInterface $request): ResponseInterface
    {
        $siteIdentifier = $request->getParsedBody()['site'] ?? '';
        if (empty($siteIdentifier)) {
            throw new \RuntimeException('Not site identifier given', 1521565182);
        }
        try {
            // Verify site does exist, method throws if not
            GeneralUtility::makeInstance(SiteConfiguration::class)->delete($siteIdentifier);
            $this->getBackendUser()->writelog(Type::SITE, SiteAction::DELETE, SystemLogErrorClassification::MESSAGE, 0, 'Site configuration \'%s\' was deleted.', [$siteIdentifier], 'site');
        } catch (SiteConfigurationWriteException $e) {
            $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $e->getMessage(), '', ContextualFeedbackSeverity::WARNING, true);
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
        $overviewRoute = $this->uriBuilder->buildUriFromRoute('site_configuration');
        return new RedirectResponse($overviewRoute);
    }

    /**
     * Create document header buttons of "edit" action
     */
    protected function configureEditViewDocHeader(ModuleTemplate $view): void
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $lang = $this->getLanguageService();
        $closeButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setClasses('t3js-editform-close')
            ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL));
        $saveButton = $buttonBar->makeInputButton()
            ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveDoc'))
            ->setName('_savedok')
            ->setValue('1')
            ->setShowLabelText(true)
            ->setForm('siteConfigurationController')
            ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL));
        $buttonBar->addButton($closeButton);
        $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
    }

    /**
     * Create document header buttons of "overview" action
     */
    protected function configureOverViewDocHeader(ModuleTemplate $view, string $requestUri): void
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $reloadButton = $buttonBar->makeLinkButton()
            ->setHref($requestUri)
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('site_configuration')
            ->setDisplayName($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_module.xlf:mlang_labels_tablabel'));
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * Returns a list of pages that have 'is_siteroot' set
     * or are on pid 0 and not in list of excluded doktypes
     */
    protected function getAllSitePages(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, 0));
        $statement = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', 0),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq('pid', 0),
                        $queryBuilder->expr()->notIn('doktype', [
                            PageRepository::DOKTYPE_SYSFOLDER,
                            PageRepository::DOKTYPE_SPACER,
                            PageRepository::DOKTYPE_RECYCLER,
                            PageRepository::DOKTYPE_LINK,
                        ])
                    ),
                    $queryBuilder->expr()->eq('is_siteroot', 1)
                )
            )
            ->orderBy('pid')
            ->addOrderBy('sorting')
            ->executeQuery();

        $pages = [];
        while ($row = $statement->fetchAssociative()) {
            $row['rootline'] = BackendUtility::BEgetRootLine((int)$row['uid']);
            array_pop($row['rootline']);
            $row['rootline'] = array_reverse($row['rootline']);
            $i = 0;
            foreach ($row['rootline'] as &$record) {
                $record['margin'] = $i++ * 20;
            }
            $pages[(int)$row['uid']] = $row;
        }
        return $pages;
    }

    /**
     * Get all entry duplicates which are used multiple times
     *
     * @param Site[] $allSites
     */
    protected function getDuplicatedEntryPoints(array $allSites, array $pages): array
    {
        $duplicatedEntryPoints = [];

        foreach ($allSites as $identifier => $site) {
            if (!isset($pages[$site->getRootPageId()])) {
                continue;
            }
            foreach ($site->getAllLanguages() as $language) {
                $base = $language->getBase();
                $entryPoint = rtrim((string)$language->getBase(), '/');
                $scheme = $base->getScheme() ? $base->getScheme() . '://' : '//';
                $entryPointWithoutScheme = str_replace($scheme, '', $entryPoint);
                if (!isset($duplicatedEntryPoints[$entryPointWithoutScheme][$entryPoint])) {
                    $duplicatedEntryPoints[$entryPointWithoutScheme][$entryPoint] = 1;
                } else {
                    $duplicatedEntryPoints[$entryPointWithoutScheme][$entryPoint]++;
                }
            }
        }
        return array_filter($duplicatedEntryPoints, static function (array $variants): bool {
            return count($variants) > 1 || reset($variants) > 1;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Returns the last (highest) language id from all sites
     */
    protected function getLastLanguageId(): int
    {
        $lastLanguageId = 0;
        foreach (GeneralUtility::makeInstance(SiteFinder::class)->getAllSites() as $site) {
            foreach ($site->getAllLanguages() as $language) {
                if ($language->getLanguageId() > $lastLanguageId) {
                    $lastLanguageId = $language->getLanguageId();
                }
            }
        }
        return $lastLanguageId;
    }

    /**
     * Checks if required=TRUE is set.
     * If set: checks if the value is not empty (or not "0").
     * If not set or set to FALSE: Returns TRUE.
     */
    protected function validateValueForRequired(array $tcaFieldConfig, mixed $value): bool
    {
        if (!($tcaFieldConfig['required'] ?? false)) {
            return true;
        }

        return !empty($value) || $value === '0';
    }

    /**
     * Method keeps root config objects, which are not given via GUI. This way,
     * extension authors are able to use their own objects on root level that are
     * not configurable via GUI. However: We overwrite the full subset of any GUI
     * object to make sure we have a clean state.
     *
     * Additionally, we also keep the baseVariants of languages, since they
     * can't be modified via the GUI, but are part of the public API.
     */
    protected function getMergeSiteData(array $currentSiteConfiguration, array $newSysSiteData): array
    {
        $newSysSiteData = array_merge($currentSiteConfiguration, $newSysSiteData);

        // @todo: this should go away, once base variants for languages are managable via the GUI.
        $existingLanguageConfigurationsWithBaseVariants = [];
        $existingLanguagesWithLegacyProperties = [];
        foreach ($currentSiteConfiguration['languages'] ?? [] as $languageConfiguration) {
            if (isset($languageConfiguration['baseVariants'])) {
                $existingLanguageConfigurationsWithBaseVariants[$languageConfiguration['languageId']] = $languageConfiguration['baseVariants'];
            }
            if (isset($languageConfiguration['typo3Language'])) {
                $existingLanguagesWithLegacyProperties[$languageConfiguration['languageId']]['typo3Language'] = $languageConfiguration['typo3Language'];
            }
            if (isset($languageConfiguration['iso-639-1'])) {
                $existingLanguagesWithLegacyProperties[$languageConfiguration['languageId']]['iso-639-1'] = $languageConfiguration['iso-639-1'];
            }
            if (isset($languageConfiguration['direction'])) {
                $existingLanguagesWithLegacyProperties[$languageConfiguration['languageId']]['direction'] = $languageConfiguration['direction'];
            }
        }
        foreach ($newSysSiteData['languages'] ?? [] as $key => $languageConfiguration) {
            $languageId = $languageConfiguration['languageId'];
            if (isset($existingLanguageConfigurationsWithBaseVariants[$languageId])) {
                $newSysSiteData['languages'][$key]['baseVariants'] = $existingLanguageConfigurationsWithBaseVariants[$languageId];
            }
            foreach ($existingLanguagesWithLegacyProperties[$languageId] ?? [] as $propertyName => $propertyValue) {
                $newSysSiteData['languages'][$key][$propertyName] = $propertyValue;
            }
        }

        return $newSysSiteData;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}

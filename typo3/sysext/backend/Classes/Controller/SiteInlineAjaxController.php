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
use TYPO3\CMS\Backend\Configuration\SiteTcaConfiguration;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\SiteConfigurationDataGroup;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Page\JavaScriptItems;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Site configuration FormEngine controller class. Receives inline "edit" and "new"
 * commands to expand / create site configuration inline records
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class SiteInlineAjaxController extends AbstractFormEngineAjaxController
{
    /**
     * Default constructor
     */
    public function __construct()
    {
        // Bring site TCA into global scope.
        // @todo: We might be able to get rid of that later
        $GLOBALS['TCA'] = array_merge($GLOBALS['TCA'], GeneralUtility::makeInstance(SiteTcaConfiguration::class)->getTca());
    }

    /**
     * Inline "create" new child of site configuration child records
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \RuntimeException
     */
    public function newInlineChildAction(ServerRequestInterface $request): ResponseInterface
    {
        $ajaxArguments = $request->getParsedBody()['ajax'] ?? $request->getQueryParams()['ajax'];
        $parentConfig = $this->extractSignedParentConfigFromRequest((string)$ajaxArguments['context']);
        $domObjectId = $ajaxArguments[0];
        $inlineFirstPid = $this->getInlineFirstPidFromDomObjectId($domObjectId);
        $childChildUid = null;
        if (isset($ajaxArguments[1]) && MathUtility::canBeInterpretedAsInteger($ajaxArguments[1])) {
            $childChildUid = (int)$ajaxArguments[1];
        }
        // Parse the DOM identifier, add the levels to the structure stack
        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByParsingDomObjectIdString($domObjectId);
        $inlineStackProcessor->injectAjaxConfiguration($parentConfig);
        $inlineTopMostParent = $inlineStackProcessor->getStructureLevel(0);
        // Parent, this table embeds the child table
        $parent = $inlineStackProcessor->getStructureLevel(-1);
        // Child, a record from this table should be rendered
        $child = $inlineStackProcessor->getUnstableStructure();
        if (MathUtility::canBeInterpretedAsInteger($child['uid'] ?? false)) {
            // If uid comes in, it is the id of the record neighbor record "create after"
            $childVanillaUid = -1 * abs((int)$child['uid']);
        } else {
            // Else inline first Pid is the storage pid of new inline records
            $childVanillaUid = (int)$inlineFirstPid;
        }
        $childTableName = $parentConfig['foreign_table'];
        $defaultDatabaseRow = [];

        if ($childTableName === 'site_language') {
            if ($childChildUid !== null) {
                $language = $this->getLanguageById($childChildUid);
                if ($language !== null) {
                    $defaultDatabaseRow['languageId'] = $language->getLanguageId();
                    $defaultDatabaseRow['locale'] = $language->getLocale();
                    if ($language->getTitle() !== '') {
                        $defaultDatabaseRow['title'] = $language->getTitle();
                    }
                    if ($language->getTypo3Language() !== '') {
                        $locales = GeneralUtility::makeInstance(Locales::class);
                        $allLanguages = $locales->getLanguages();
                        if (isset($allLanguages[$language->getTypo3Language()])) {
                            $defaultDatabaseRow['typo3Language'] = $language->getTypo3Language();
                        }
                    }
                    if ($language->getTwoLetterIsoCode() !== '') {
                        $defaultDatabaseRow['iso-639-1'] = $language->getTwoLetterIsoCode();
                        if ($language->getBase()->getPath() !== '/') {
                            $defaultDatabaseRow['base'] = '/' . $language->getTwoLetterIsoCode() . '/';
                        }
                    }
                    if ($language->getNavigationTitle() !== '') {
                        $defaultDatabaseRow['navigationTitle'] = $language->getNavigationTitle();
                    }
                    if ($language->getHreflang() !== '') {
                        $defaultDatabaseRow['hreflang'] = $language->getHreflang();
                    }
                    if ($language->getDirection() !== '') {
                        $defaultDatabaseRow['direction'] = $language->getDirection();
                    }
                    if (strpos($language->getFlagIdentifier(), 'flags-') === 0) {
                        $flagIdentifier = str_replace('flags-', '', $language->getFlagIdentifier());
                        $defaultDatabaseRow['flag'] = ($flagIdentifier === 'multiple') ? 'global' : $flagIdentifier;
                    }
                } elseif ($childChildUid !== 0) {
                    // In case no language could be found for $childChildUid and
                    // its value is not "0", which is a special case as the default
                    // language is added automatically, throw a custom exception.
                    throw new \RuntimeException('Referenced language not found', 1521783937);
                }
            } else {
                // Set new childs' UID to PHP_INT_MAX, as this is the placeholder UID for
                // new records, created with the "Create new" button. This is necessary
                // as we use the "inline selector" mode which usually does not allow
                // to create new records besides the ones, defined in the selector.
                // The correct UID will then be calculated by the controller.
                $childChildUid = PHP_INT_MAX;
            }
        }

        $formDataGroup = GeneralUtility::makeInstance(SiteConfigurationDataGroup::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'command' => 'new',
            'tableName' => $childTableName,
            'vanillaUid' => $childVanillaUid,
            'databaseRow' => $defaultDatabaseRow,
            'isInlineChild' => true,
            'inlineStructure' => $inlineStackProcessor->getStructure(),
            'inlineFirstPid' => $inlineFirstPid,
            'inlineParentUid' => $parent['uid'],
            'inlineParentTableName' => $parent['table'],
            'inlineParentFieldName' => $parent['field'],
            'inlineParentConfig' => $parentConfig,
            'inlineTopMostParentUid' => $inlineTopMostParent['uid'],
            'inlineTopMostParentTableName' => $inlineTopMostParent['table'],
            'inlineTopMostParentFieldName' => $inlineTopMostParent['field'],
        ];
        if ($childChildUid) {
            $formDataCompilerInput['inlineChildChildUid'] = $childChildUid;
        }
        $childData = $formDataCompiler->compile($formDataCompilerInput);

        if (($parentConfig['foreign_selector'] ?? false) && ($parentConfig['appearance']['useCombination'] ?? false)) {
            throw new \RuntimeException('useCombination not implemented in sites module', 1522493094);
        }

        $childData['inlineParentUid'] = (int)$parent['uid'];
        $childData['renderType'] = 'inlineRecordContainer';
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $childResult = $nodeFactory->create($childData)->render();

        $jsonArray = [
            'data' => '',
            'stylesheetFiles' => [],
            'scriptItems' => GeneralUtility::makeInstance(JavaScriptItems::class),
            'scriptCall' => [],
            'compilerInput' => [
                'uid' => $childData['databaseRow']['uid'],
                'childChildUid' => $childChildUid,
                'parentConfig' => $parentConfig,
            ],
        ];

        $jsonArray = $this->mergeChildResultIntoJsonResult($jsonArray, $childResult);

        return new JsonResponse($jsonArray);
    }

    /**
     * Show the details of site configuration child records.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \RuntimeException
     */
    public function openInlineChildAction(ServerRequestInterface $request): ResponseInterface
    {
        $ajaxArguments = $request->getParsedBody()['ajax'] ?? $request->getQueryParams()['ajax'];

        $domObjectId = $ajaxArguments[0];
        $inlineFirstPid = $this->getInlineFirstPidFromDomObjectId($domObjectId);
        $parentConfig = $this->extractSignedParentConfigFromRequest((string)$ajaxArguments['context']);

        // Parse the DOM identifier, add the levels to the structure stack
        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByParsingDomObjectIdString($domObjectId);
        $inlineStackProcessor->injectAjaxConfiguration($parentConfig);

        // Parent, this table embeds the child table
        $parent = $inlineStackProcessor->getStructureLevel(-1);
        $parentFieldName = $parent['field'];

        // Set flag in config so that only the fields are rendered
        // @todo: Solve differently / rename / whatever
        $parentConfig['renderFieldsOnly'] = true;

        $parentData = [
            'processedTca' => [
                'columns' => [
                    $parentFieldName => [
                        'config' => $parentConfig,
                    ],
                ],
            ],
            'uid' => $parent['uid'],
            'tableName' => $parent['table'],
            'inlineFirstPid' => $inlineFirstPid,
            // Hand over given original return url to compile stack. Needed if inline children compile links to
            // another view (eg. edit metadata in a nested inline situation like news with inline content element image),
            // so the back link is still the link from the original request. See issue #82525. This is additionally
            // given down in TcaInline data provider to compiled children data.
            'returnUrl' => $parentConfig['originalReturnUrl'],
        ];

        // Child, a record from this table should be rendered
        $child = $inlineStackProcessor->getUnstableStructure();

        $childData = $this->compileChild($parentData, $parentFieldName, (int)$child['uid'], $inlineStackProcessor->getStructure());

        $childData['inlineParentUid'] = (int)$parent['uid'];
        $childData['renderType'] = 'inlineRecordContainer';
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $childResult = $nodeFactory->create($childData)->render();

        $jsonArray = [
            'data' => '',
            'stylesheetFiles' => [],
            'scriptItems' => GeneralUtility::makeInstance(JavaScriptItems::class),
            'scriptCall' => [],
        ];

        $jsonArray = $this->mergeChildResultIntoJsonResult($jsonArray, $childResult);

        return new JsonResponse($jsonArray);
    }

    /**
     * Compile a full child record
     *
     * @param array $parentData Result array of parent
     * @param string $parentFieldName Name of parent field
     * @param int $childUid Uid of child to compile
     * @param array $inlineStructure Current inline structure
     * @return array Full result array
     * @throws \RuntimeException
     *
     * @todo: This clones methods compileChild from TcaInline Provider. Find a better abstraction
     * @todo: to also encapsulate the more complex scenarios with combination child and friends.
     */
    protected function compileChild(array $parentData, string $parentFieldName, int $childUid, array $inlineStructure): array
    {
        $parentConfig = $parentData['processedTca']['columns'][$parentFieldName]['config'];

        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByGivenStructure($inlineStructure);
        $inlineTopMostParent = $inlineStackProcessor->getStructureLevel(0);

        // @todo: do not use stack processor here ...
        $child = $inlineStackProcessor->getUnstableStructure();
        $childTableName = $child['table'];

        $formDataGroup = GeneralUtility::makeInstance(SiteConfigurationDataGroup::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'command' => 'edit',
            'tableName' => $childTableName,
            'vanillaUid' => (int)$childUid,
            'returnUrl' => $parentData['returnUrl'],
            'isInlineChild' => true,
            'inlineStructure' => $inlineStructure,
            'inlineFirstPid' => $parentData['inlineFirstPid'],
            'inlineParentConfig' => $parentConfig,
            'isInlineAjaxOpeningContext' => true,

            // values of the current parent element
            // it is always a string either an id or new...
            'inlineParentUid' => $parentData['uid'],
            'inlineParentTableName' => $parentData['tableName'],
            'inlineParentFieldName' => $parentFieldName,

            // values of the top most parent element set on first level and not overridden on following levels
            'inlineTopMostParentUid' => $inlineTopMostParent['uid'],
            'inlineTopMostParentTableName' => $inlineTopMostParent['table'],
            'inlineTopMostParentFieldName' => $inlineTopMostParent['field'],
        ];
        if (($parentConfig['foreign_selector'] ?? false) && ($parentConfig['appearance']['useCombination'] ?? false)) {
            throw new \RuntimeException('useCombination not implemented in sites module', 1522493095);
        }
        return $formDataCompiler->compile($formDataCompilerInput);
    }

    /**
     * Merge stuff from child array into json array.
     * This method is needed since ajax handling methods currently need to put scriptCalls before and after child code.
     *
     * @param array $jsonResult Given json result
     * @param array $childResult Given child result
     * @return array Merged json array
     */
    protected function mergeChildResultIntoJsonResult(array $jsonResult, array $childResult): array
    {
        /** @var JavaScriptItems $scriptItems */
        $scriptItems = $jsonResult['scriptItems'];

        $jsonResult['data'] .= $childResult['html'];
        $jsonResult['stylesheetFiles'] = [];
        foreach ($childResult['stylesheetFiles'] as $stylesheetFile) {
            $jsonResult['stylesheetFiles'][] = $this->getRelativePathToStylesheetFile($stylesheetFile);
        }
        if (!empty($childResult['inlineData'])) {
            $jsonResult['inlineData'] = $childResult['inlineData'];
        }
        // @todo deprecate with TYPO3 v12.0
        foreach ($childResult['additionalJavaScriptPost'] as $singleAdditionalJavaScriptPost) {
            $jsonResult['scriptCall'][] = $singleAdditionalJavaScriptPost;
        }
        if (!empty($childResult['additionalInlineLanguageLabelFiles'])) {
            $labels = [];
            foreach ($childResult['additionalInlineLanguageLabelFiles'] as $additionalInlineLanguageLabelFile) {
                ArrayUtility::mergeRecursiveWithOverrule(
                    $labels,
                    $this->getLabelsFromLocalizationFile($additionalInlineLanguageLabelFile)
                );
            }
            $scriptItems->addGlobalAssignment(['TYPO3' => ['lang' => $labels]]);
        }
        $this->addRegisteredRequireJsModulesToJavaScriptItems($childResult, $scriptItems);
        // @todo deprecate modules with arbitrary JavaScript callback function in TYPO3 v12.0
        $jsonResult['requireJsModules'] = $this->createExecutableStringRepresentationOfRegisteredRequireJsModules($childResult, true);

        return $jsonResult;
    }

    /**
     * Inline ajax helper method.
     *
     * Validates the config that is transferred over the wire to provide the
     * correct TCA config for the parent table
     *
     * @param string $contextString
     * @throws \RuntimeException
     * @return array
     */
    protected function extractSignedParentConfigFromRequest(string $contextString): array
    {
        if ($contextString === '') {
            throw new \RuntimeException('Empty context string given', 1522771624);
        }
        $context = json_decode($contextString, true);
        if (empty($context['config'])) {
            throw new \RuntimeException('Empty context config section given', 1522771632);
        }
        $config = json_decode($context['config'], true);
        // encode JSON again to ensure same `json_encode()` settings as used when generating original hash
        // (side-note: JSON encoded literals differ for target scenarios, e.g. HTML attr, JS string, ...)
        $encodedConfig = (string)json_encode($config);
        if (!hash_equals(GeneralUtility::hmac($encodedConfig, 'InlineContext'), (string)$context['hmac'])) {
            throw new \RuntimeException('Hash does not validate', 1522771640);
        }
        return $config;
    }

    /**
     * Get inlineFirstPid from a given objectId string
     *
     * @param string $domObjectId The id attribute of an element
     * @return int|null Pid or null
     */
    protected function getInlineFirstPidFromDomObjectId(string $domObjectId): ?int
    {
        // Substitute FlexForm addition and make parsing a bit easier
        $domObjectId = str_replace('---', ':', $domObjectId);
        // The starting pattern of an object identifier (e.g. "data-<firstPidValue>-<anything>)
        $pattern = '/^data-(.+?)-(.+)$/';
        if (preg_match($pattern, $domObjectId, $match)) {
            return (int)$match[1];
        }
        return null;
    }

    /**
     * Find a site language by id. This will return the first occurrence of a
     * language, even if the same language is used in other site configurations.
     *
     * @param int $languageId
     * @return SiteLanguage|null
     */
    protected function getLanguageById(int $languageId): ?SiteLanguage
    {
        foreach (GeneralUtility::makeInstance(SiteFinder::class)->getAllSites() as $site) {
            foreach ($site->getAllLanguages() as $language) {
                if ($languageId === $language->getLanguageId()) {
                    return $language;
                }
            }
        }

        return null;
    }
}

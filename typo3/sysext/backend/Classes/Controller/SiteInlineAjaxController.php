<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Controller;

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
use TYPO3\CMS\Backend\Configuration\SiteTcaConfiguration;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\SiteConfigurationDataGroup;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Http\JsonResponse;
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
        if (MathUtility::canBeInterpretedAsInteger($child['uid'])) {
            // If uid comes in, it is the id of the record neighbor record "create after"
            $childVanillaUid = -1 * abs((int)$child['uid']);
        } else {
            // Else inline first Pid is the storage pid of new inline records
            $childVanillaUid = (int)$inlineFirstPid;
        }
        $childTableName = $parentConfig['foreign_table'];

        $defaultDatabaseRow = [];
        if ($childTableName === 'site_language') {
            // Feed new site_language row with data from sys_language record if possible
            if ($childChildUid > 0) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
                $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
                $row = $queryBuilder->select('*')->from('sys_language')
                    ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($childChildUid, \PDO::PARAM_INT)))
                    ->execute()->fetch();
                if (empty($row)) {
                    throw new \RuntimeException('Referenced sys_language row not found', 1521783937);
                }
                if (!empty($row['language_isocode'])) {
                    $defaultDatabaseRow['iso-639-1'] = $row['language_isocode'];
                    $defaultDatabaseRow['base'] = '/' . $row['language_isocode'] . '/';
                }
                if (!empty($row['flag']) && $row['flag'] === 'multiple') {
                    $defaultDatabaseRow['flag'] = 'global';
                } elseif (!empty($row)) {
                    $defaultDatabaseRow['flag'] = $row['flag'];
                }
                if (!empty($row['title'])) {
                    $defaultDatabaseRow['title'] = $row['title'];
                }
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

        if ($parentConfig['foreign_selector'] && $parentConfig['appearance']['useCombination']) {
            throw new \RuntimeException('useCombination not implemented in sites module', 1522493094);
        }

        $childData['inlineParentUid'] = (int)$parent['uid'];
        $childData['renderType'] = 'inlineRecordContainer';
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $childResult = $nodeFactory->create($childData)->render();

        $jsonArray = [
            'data' => '',
            'stylesheetFiles' => [],
            'scriptCall' => [],
        ];

        // The HTML-object-id's prefix of the dynamically created record
        $objectName = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($inlineFirstPid);
        $objectPrefix = $objectName . '-' . $child['table'];
        $objectId = $objectPrefix . '-' . $childData['databaseRow']['uid'];
        $expandSingle = $parentConfig['appearance']['expandSingle'];
        if (!$child['uid']) {
            $jsonArray['scriptCall'][] = 'inline.domAddNewRecord(\'bottom\',' . GeneralUtility::quoteJSvalue($objectName . '_records') . ',' . GeneralUtility::quoteJSvalue($objectPrefix) . ',json.data);';
            $jsonArray['scriptCall'][] = 'inline.memorizeAddRecord(' . GeneralUtility::quoteJSvalue($objectPrefix) . ',' . GeneralUtility::quoteJSvalue($childData['databaseRow']['uid']) . ',null,' . GeneralUtility::quoteJSvalue($childChildUid) . ');';
        } else {
            $jsonArray['scriptCall'][] = 'inline.domAddNewRecord(\'after\',' . GeneralUtility::quoteJSvalue($domObjectId . '_div') . ',' . GeneralUtility::quoteJSvalue($objectPrefix) . ',json.data);';
            $jsonArray['scriptCall'][] = 'inline.memorizeAddRecord(' . GeneralUtility::quoteJSvalue($objectPrefix) . ',' . GeneralUtility::quoteJSvalue($childData['databaseRow']['uid']) . ',' . GeneralUtility::quoteJSvalue($child['uid']) . ',' . GeneralUtility::quoteJSvalue($childChildUid) . ');';
        }
        $jsonArray = $this->mergeChildResultIntoJsonResult($jsonArray, $childResult);
        if ($parentConfig['appearance']['useSortable']) {
            $inlineObjectName = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($inlineFirstPid);
            $jsonArray['scriptCall'][] = 'inline.createDragAndDropSorting(' . GeneralUtility::quoteJSvalue($inlineObjectName . '_records') . ');';
        }
        if (!$parentConfig['appearance']['collapseAll'] && $expandSingle) {
            $jsonArray['scriptCall'][] = 'inline.collapseAllRecords(' . GeneralUtility::quoteJSvalue($objectId) . ',' . GeneralUtility::quoteJSvalue($objectPrefix) . ',' . GeneralUtility::quoteJSvalue($childData['databaseRow']['uid']) . ');';
        }
        // Fade out and fade in the new record in the browser view to catch the user's eye
        $jsonArray['scriptCall'][] = 'inline.fadeOutFadeIn(' . GeneralUtility::quoteJSvalue($objectId . '_div') . ');';

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
            'scriptCall' => [],
        ];

        // The HTML-object-id's prefix of the dynamically created record
        $objectPrefix = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($inlineFirstPid) . '-' . $child['table'];
        $objectId = $objectPrefix . '-' . (int)$child['uid'];
        $expandSingle = $parentConfig['appearance']['expandSingle'];
        $jsonArray['scriptCall'][] = 'inline.domAddRecordDetails(' . GeneralUtility::quoteJSvalue($domObjectId) . ',' . GeneralUtility::quoteJSvalue($objectPrefix) . ',' . ($expandSingle ? '1' : '0') . ',json.data);';
        if ($parentConfig['foreign_unique']) {
            $jsonArray['scriptCall'][] = 'inline.removeUsed(' . GeneralUtility::quoteJSvalue($objectPrefix) . ',\'' . (int)$child['uid'] . '\');';
        }
        $jsonArray = $this->mergeChildResultIntoJsonResult($jsonArray, $childResult);
        if ($parentConfig['appearance']['useSortable']) {
            $inlineObjectName = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($inlineFirstPid);
            $jsonArray['scriptCall'][] = 'inline.createDragAndDropSorting(' . GeneralUtility::quoteJSvalue($inlineObjectName . '_records') . ');';
        }
        if (!$parentConfig['appearance']['collapseAll'] && $expandSingle) {
            $jsonArray['scriptCall'][] = 'inline.collapseAllRecords(' . GeneralUtility::quoteJSvalue($objectId) . ',' . GeneralUtility::quoteJSvalue($objectPrefix) . ',\'' . (int)$child['uid'] . '\');';
        }

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
            'inlineParentUid' => $parentData['databaseRow']['uid'],
            'inlineParentTableName' => $parentData['tableName'],
            'inlineParentFieldName' => $parentFieldName,

            // values of the top most parent element set on first level and not overridden on following levels
            'inlineTopMostParentUid' => $inlineTopMostParent['uid'],
            'inlineTopMostParentTableName' => $inlineTopMostParent['table'],
            'inlineTopMostParentFieldName' => $inlineTopMostParent['field'],
        ];
        if ($parentConfig['foreign_selector'] && $parentConfig['appearance']['useCombination']) {
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
        $jsonResult['data'] .= $childResult['html'];
        $jsonResult['stylesheetFiles'] = [];
        foreach ($childResult['stylesheetFiles'] as $stylesheetFile) {
            $jsonResult['stylesheetFiles'][] = $this->getRelativePathToStylesheetFile($stylesheetFile);
        }
        if (!empty($childResult['inlineData'])) {
            $jsonResult['scriptCall'][] = 'inline.addToDataArray(' . json_encode($childResult['inlineData']) . ');';
        }
        if (!empty($childResult['additionalJavaScriptSubmit'])) {
            $additionalJavaScriptSubmit = implode('', $childResult['additionalJavaScriptSubmit']);
            $additionalJavaScriptSubmit = str_replace([CR, LF], '', $additionalJavaScriptSubmit);
            $jsonResult['scriptCall'][] = 'TBE_EDITOR.addActionChecks("submit", "' . addslashes($additionalJavaScriptSubmit) . '");';
        }
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
            $javaScriptCode = [];
            $javaScriptCode[] = 'if (typeof TYPO3 === \'undefined\' || typeof TYPO3.lang === \'undefined\') {';
            $javaScriptCode[] = '   TYPO3.lang = {}';
            $javaScriptCode[] = '}';
            $javaScriptCode[] = 'var additionalInlineLanguageLabels = ' . json_encode($labels) . ';';
            $javaScriptCode[] = 'for (var attributeName in additionalInlineLanguageLabels) {';
            $javaScriptCode[] = '   if (typeof TYPO3.lang[attributeName] === \'undefined\') {';
            $javaScriptCode[] = '       TYPO3.lang[attributeName] = additionalInlineLanguageLabels[attributeName]';
            $javaScriptCode[] = '   }';
            $javaScriptCode[] = '}';

            $jsonResult['scriptCall'][] = implode(LF, $javaScriptCode);
        }
        $requireJsModule = $this->createExecutableStringRepresentationOfRegisteredRequireJsModules($childResult);
        $jsonResult['scriptCall'] = array_merge($requireJsModule, $jsonResult['scriptCall']);

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
        if (!hash_equals(GeneralUtility::hmac(json_encode($context['config']), 'InlineContext'), $context['hmac'])) {
            throw new \RuntimeException('Hash does not validate', 1522771640);
        }
        return $context['config'];
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
        $pattern = '/^data' . '-' . '(.+?)' . '-' . '(.+)$/';
        if (preg_match($pattern, $domObjectId, $match)) {
            return (int)$match[1];
        }
        return null;
    }
}

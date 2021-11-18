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

namespace TYPO3\CMS\Backend\Controller\Wizard;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Script Class for redirecting a backend user to the editing form when an "Edit wizard" link was clicked in FormEngine somewhere
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class EditController extends AbstractWizardController
{
    protected const JAVASCRIPT_HELPER = 'EXT:backend/Resources/Public/JavaScript/Helper.js';

    /**
     * Wizard parameters, coming from FormEngine linking to the wizard.
     *
     * Contains the following parts:
     * - table
     * - field
     * - formName
     * - hmac
     * - fieldChangeFunc
     * - fieldChangeFuncHash
     * - currentValue
     * - currentSelectedValues
     *
     * @var array
     */
    protected $P;

    /**
     * Boolean; if set, the window will be closed by JavaScript
     *
     * @var int
     */
    protected $doClose;

    /**
     * HTML markup to close the open window.
     *
     * @var string
     */
    protected string $closeWindow;

    public function __construct()
    {
        $this->closeWindow = sprintf(
            '<script %s></script>',
            GeneralUtility::implodeAttributes([
                'src' => PathUtility::getAbsoluteWebPath(
                    GeneralUtility::getFileAbsFileName(self::JAVASCRIPT_HELPER)
                ),
                'data-action' => 'window.close',
            ], true)
        );
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_wizards.xlf');

        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->P = $parsedBody['P'] ?? $queryParams['P'] ?? [];
        // Used for the return URL to FormEngine so that we can close the window.
        $this->doClose = $parsedBody['doClose'] ?? $queryParams['doClose'] ?? 0;

        return $this->processRequest();
    }

    /**
     * Process request function
     * Makes a header-location redirect to an edit form IF POSSIBLE from the passed data - otherwise the window will
     * just close.
     *
     * @return ResponseInterface
     */
    protected function processRequest(): ResponseInterface
    {
        if ($this->doClose) {
            return new HtmlResponse($this->closeWindow);
        }
        // Initialize:
        $table = $this->P['table'];
        $field = $this->P['field'];

        if (empty($this->P['flexFormDataStructureIdentifier'])) {
            // If there is not flex data structure identifier, field config is found in globals
            $config = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
        } else {
            // If there is a flex data structure identifier, parse that data structure and
            // fetch config defined by given flex path
            $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
            $dataStructure = $flexFormTools->parseDataStructureByIdentifier($this->P['flexFormDataStructureIdentifier']);
            $config = ArrayUtility::getValueByPath($dataStructure, $this->P['flexFormDataStructurePath']);
            if (!is_array($config)) {
                throw new \RuntimeException(
                    'Something went wrong finding flex path ' . $this->P['flexFormDataStructurePath']
                    . ' in data structure identified by ' . $this->P['flexFormDataStructureIdentifier'],
                    1537356346
                );
            }
        }

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $urlParameters = [
            'returnUrl' => (string)$uriBuilder->buildUriFromRoute('wizard_edit', ['doClose' => 1]),
        ];

        // Detecting the various allowed field type setups and acting accordingly.
        if (is_array($config)
            && $config['type'] === 'select'
            && !($config['MM'] ?? false)
            && (int)($config['maxitems'] ?? 0) <= 1
            && MathUtility::canBeInterpretedAsInteger($this->P['currentValue'])
            && $this->P['currentValue']
            && $config['foreign_table']
        ) {
            // SINGLE value
            $urlParameters['edit[' . $config['foreign_table'] . '][' . $this->P['currentValue'] . ']'] = 'edit';
            // Redirect to FormEngine
            $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
            return new RedirectResponse($url);
        }

        if (!empty($config['type'])
            && !empty($this->P['currentSelectedValues'])
            && (
                $config['type'] === 'select' && !empty($config['foreign_table'])
                || $config['type'] === 'group' && !empty($config['allowed'])
            )
        ) {
            // MULTIPLE VALUES:
            // Init settings:
            $allowedTables = $config['type'] === 'group' ? $config['allowed'] : $config['foreign_table'];
            // Selecting selected values into an array:
            $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
            $relationHandler->start($this->P['currentSelectedValues'], $allowedTables);
            $value = $relationHandler->getValueArray(true);
            // Traverse that array and make parameters for FormEngine
            foreach ($value as $rec) {
                $recTableUidParts = GeneralUtility::revExplode('_', $rec, 2);
                $urlParameters['edit[' . $recTableUidParts[0] . '][' . $recTableUidParts[1] . ']'] = 'edit';
            }
            // Redirect to FormEngine
            $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);

            return new RedirectResponse($url);
        }
        return new HtmlResponse($this->closeWindow);
    }
}

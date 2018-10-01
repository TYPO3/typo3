<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Controller\Wizard;

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
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Script Class for redirecting a backend user to the editing form when an "Edit wizard" link was clicked in FormEngine somewhere
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class EditController extends AbstractWizardController
{
    use PublicPropertyDeprecationTrait;

    /**
     * Properties which have been moved to protected status from public
     *
     * @var array
     */
    protected $deprecatedPublicProperties = [
        'P' => 'Using $P of class EditController from the outside is discouraged, as this variable is only used for internal storage.',
        'doClose' => 'Using $doClose of class EditController from the outside is discouraged, as this variable is only used for internal storage.',
    ];

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
     * A little JavaScript to close the open window.
     *
     * @var string
     */
    protected $closeWindow = '<script language="javascript" type="text/javascript">close();</script>';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_wizards.xlf');

        // @deprecated since TYPO3 v9, will be moved out of __construct() in TYPO3 v10.0
        $this->init($GLOBALS['TYPO3_REQUEST']);
    }

    /**
     * Initialization of the script
     *
     * @param ServerRequestInterface $request
     */
    protected function init(ServerRequestInterface $request)
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->P = $parsedBody['P'] ?? $queryParams['P'] ?? [];

        // Used for the return URL to FormEngine so that we can close the window.
        $this->doClose = $parsedBody['doClose'] ?? $queryParams['doClose'] ?? 0;
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
        $content = $this->processRequest($request);
        return $content;
    }

    /**
     * Main function
     * Makes a header-location redirect to an edit form IF POSSIBLE from the passed data - otherwise the window will
     * just close.
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     * @return string
     */
    public function main()
    {
        trigger_error('EditController->main() will be set to protected in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
        $request = $GLOBALS['TYPO3_REQUEST'];

        $response = $this->processRequest($request);

        if ($response instanceof RedirectResponse) {
            HttpUtility::redirect($response->getHeaders()['location'][0]);
        } else {
            return $response->getBody()->getContents();
        }
    }

    /**
     * Process request function
     * Makes a header-location redirect to an edit form IF POSSIBLE from the passed data - otherwise the window will
     * just close.
     *
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function processRequest(ServerRequestInterface $request): ResponseInterface
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
            $config = $flexFormTools->getArrayValueByPath($this->P['flexFormDataStructurePath'], $dataStructure);
            if (!is_array($config)) {
                throw new \RuntimeException(
                    'Something went wrong finding flex path ' . $this->P['flexFormDataStructurePath']
                    . ' in data structure identified by ' . $this->P['flexFormDataStructureIdentifier'],
                    1537356346
                );
            }
        }

        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $urlParameters = [
            'returnUrl' => (string)$uriBuilder->buildUriFromRoute('wizard_edit', ['doClose' => 1])
        ];

        // Detecting the various allowed field type setups and acting accordingly.
        if (is_array($config)
            && $config['type'] === 'select'
            && !$config['MM']
            && $config['maxitems'] <= 1
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

        if (is_array($config)
            && $this->P['currentSelectedValues']
            && (
                $config['type'] === 'select'
                && $config['foreign_table']
                || $config['type'] === 'group'
                && $config['internal_type'] === 'db'
            )
        ) {
            // MULTIPLE VALUES:
            // Init settings:
            $allowedTables = $config['type'] === 'group' ? $config['allowed'] : $config['foreign_table'];
            $prependName = 1;
            // Selecting selected values into an array:
            $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
            $relationHandler->start($this->P['currentSelectedValues'], $allowedTables);
            $value = $relationHandler->getValueArray($prependName);
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

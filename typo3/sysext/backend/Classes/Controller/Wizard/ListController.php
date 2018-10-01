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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * Script Class for redirecting the user to the Web > List module if a wizard-link has been clicked in FormEngine
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class ListController extends AbstractWizardController
{
    use PublicPropertyDeprecationTrait;

    /**
     * Properties which have been moved to protected status from public
     *
     * @var array
     */
    protected $deprecatedPublicProperties = [
        'pid' => 'Using $pid of class ListController from the outside is discouraged, as this variable is only used for internal storage.',
        'P' => 'Using $P of class ListController from the outside is discouraged, as this variable is only used for internal storage.',
        'table' => 'Using $table of class ListController from the outside is discouraged, as this variable is only used for internal storage.',
        'id' => 'Using $id of class ListController from the outside is discouraged, as this variable is only used for internal storage.',
    ];
    /**
     * @var int
     */
    protected $pid;

    /**
     * Wizard parameters, coming from FormEngine linking to the wizard.
     *
     * @var array
     */
    protected $P;

    /**
     * Table to show, if none, then all tables are listed in list module.
     *
     * @var string
     */
    protected $table;

    /**
     * Page id to list.
     *
     * @var string
     */
    protected $id;

    /**
     * Initialization of the class, setting GPvars.
     */
    public function __construct()
    {
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_wizards.xlf');

        // @deprecated since TYPO3 v9, will be obsolete in TYPO3 v10.0
        $request = $GLOBALS['TYPO3_REQUEST'];
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->P = $parsedBody['P'] ?? $queryParams['P'] ?? null;
        $this->table = $parsedBody['table'] ?? $queryParams['table'] ?? null;
        $this->id = $parsedBody['id'] ?? $queryParams['id'] ?? null;
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
        $this->processRequest($request);
        return new HtmlResponse('');
    }

    /**
     * Main function
     * Will issue a location-header, redirecting either BACK or to a new FormEngine instance...
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function main(): void
    {
        trigger_error('ListController->main() will be replaced by protected method processRequest() in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
        $this->processRequest($GLOBALS['TYPO3_REQUEST']);
    }
    /**
     * Main function
     * Will issue a location-header, redirecting either BACK or to a new FormEngine instance...
     * @param ServerRequestInterface $request
     */
    protected function processRequest(ServerRequestInterface $request): void
    {
        // Get this record
        $origRow = BackendUtility::getRecord($this->P['table'], $this->P['uid']);
        // Get TSconfig for it.
        $TSconfig = BackendUtility::getTCEFORM_TSconfig(
            $this->table,
            is_array($origRow) ? $origRow : ['pid' => $this->P['pid']]
        );
        // Set [params][pid]
        if (strpos($this->P['params']['pid'], '###') === 0 && substr($this->P['params']['pid'], -3) === '###') {
            $keyword = substr($this->P['params']['pid'], 3, -3);
            if (strpos($keyword, 'PAGE_TSCONFIG_') === 0) {
                $this->pid = (int)$TSconfig[$this->P['field']][$keyword];
            } else {
                $this->pid = (int)$TSconfig['_' . $keyword];
            }
        } else {
            $this->pid = (int)$this->P['params']['pid'];
        }
        // Make redirect:
        // If pid is blank OR if id is set, then return...
        if ((string)$this->id !== '') {
            $redirectUrl = GeneralUtility::sanitizeLocalUrl($this->P['returnUrl']);
        } else {
            /** @var UriBuilder $uriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $normalizedParams = $request->getAttribute('normalizedParams');
            $requestUri = $normalizedParams->getRequestUri();
            // Otherwise, show the list:
            $urlParameters = [];
            $urlParameters['id'] = $this->pid;
            $urlParameters['table'] = $this->P['params']['table'];
            $urlParameters['returnUrl'] = !empty($this->P['returnUrl'])
                ? GeneralUtility::sanitizeLocalUrl($this->P['returnUrl'])
                : $requestUri;
            $redirectUrl = (string)$uriBuilder->buildUriFromRoute('web_list', $urlParameters);
        }
        HttpUtility::redirect($redirectUrl);
    }
}

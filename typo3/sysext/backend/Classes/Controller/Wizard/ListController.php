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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for redirecting the user to the Web > List module if a wizard-link has been clicked in FormEngine
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class ListController extends AbstractWizardController
{
    /**
     * Injects the request object for the current request or sub request
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_wizards.xlf');

        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        // Wizard parameters, coming from FormEngine linking to this wizard.
        $parameters = $parsedBody['P'] ?? $queryParams['P'] ?? null;
        $id = $parsedBody['id'] ?? $queryParams['id'] ?? null;
        $table = $parameters['table'] ?? '';
        $origRow = BackendUtility::getRecord($table, $parameters['uid']);
        $tsConfig = BackendUtility::getTCEFORM_TSconfig($table, $origRow ?? ['pid' => $parameters['pid']]);

        if (strpos($parameters['params']['pid'], '###') === 0 && substr($parameters['params']['pid'], -3) === '###') {
            $keyword = substr($parameters['params']['pid'], 3, -3);
            if (strpos($keyword, 'PAGE_TSCONFIG_') === 0) {
                $pid = (int)$tsConfig[$parameters['field']][$keyword];
            } else {
                $pid = (int)$tsConfig['_' . $keyword];
            }
        } else {
            $pid = (int)$parameters['params']['pid'];
        }

        if ((string)$id !== '') {
            // If pid is blank
            $redirectUrl = GeneralUtility::sanitizeLocalUrl($parameters['returnUrl']);
        } else {
            // Otherwise, show the list
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $normalizedParams = $request->getAttribute('normalizedParams');
            $requestUri = $normalizedParams->getRequestUri();
            $urlParameters = [];
            $urlParameters['id'] = $pid;
            $urlParameters['table'] = $parameters['params']['table'];
            $urlParameters['returnUrl'] = !empty($parameters['returnUrl'])
                ? GeneralUtility::sanitizeLocalUrl($parameters['returnUrl'])
                : $requestUri;
            $redirectUrl = (string)$uriBuilder->buildUriFromRoute('web_list', $urlParameters);
        }

        return new RedirectResponse($redirectUrl);
    }
}

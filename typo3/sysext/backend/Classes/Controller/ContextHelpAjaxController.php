<?php

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ContextHelpAjaxController
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class ContextHelpAjaxController
{
    /**
     * The main dispatcher function. Collect data and prepare HTML output.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \RuntimeException
     */
    public function getHelpAction(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getParsedBody()['params'] ?? $request->getQueryParams()['params'];
        if (($params['action'] ?? '') !== 'getContextHelp') {
            throw new \RuntimeException('Action must be set to "getContextHelp"', 1518787887);
        }
        $result = $this->getContextHelp($params['table'], $params['field']);
        return new JsonResponse([
            'title' => $result['title'],
            'content' => $result['description'],
            'link' => $result['moreInfo'],
        ]);
    }

    /**
     * Fetch the context help for the given table/field parameters
     *
     * @param string $table Table identifier
     * @param string $field Field identifier
     * @return array complete Help information
     */
    protected function getContextHelp($table, $field)
    {
        $helpTextArray = BackendUtility::helpTextArray($table, $field);
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $moreIcon = $helpTextArray['moreInfo'] ? $iconFactory->getIcon('actions-view-go-forward', Icon::SIZE_SMALL)->render() : '';
        return [
            'title' => $helpTextArray['title'],
            'description' => '<p class="help-short' . ($moreIcon ? ' help-has-link' : '') . '">' . $helpTextArray['description'] . $moreIcon . '</p>',
            'id' => $table . '.' . $field,
            'moreInfo' => $helpTextArray['moreInfo'],
        ];
    }
}

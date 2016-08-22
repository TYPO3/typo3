<?php
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
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Backend controller for selectTree ajax operations
 */
class SelectTreeController
{
    /**
     * Returns json representing category tree
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function fetchDataAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $tableName = $request->getQueryParams()['table'];
        if (!$this->getBackendUser()->check('tables_select', $tableName)) {
            return $response;
        }
        $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);

        $formDataCompilerInput = [
            'tableName' => $request->getQueryParams()['table'],
            'vanillaUid' => (int)$request->getQueryParams()['uid'],
            'command' => 'edit',
        ];
        $fieldName = $request->getQueryParams()['field'];
        $formData = $formDataCompiler->compile($formDataCompilerInput);
        $treeData = $formData['processedTca']['columns'][$fieldName]['config']['treeData'];
        $json = json_encode($treeData['items']);
        $response->getBody()->write($json);
        return $response;
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}

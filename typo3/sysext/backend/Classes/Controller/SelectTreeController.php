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
            'command' => $request->getQueryParams()['command'],
        ];

        $fieldName = $request->getQueryParams()['field'];
        $formData = $formDataCompiler->compile($formDataCompilerInput);

        if ($formData['processedTca']['columns'][$fieldName]['config']['type'] === 'flex') {
            $flexFormFieldName = $request->getQueryParams()['flex_form_field_name'];
            $value = $this->searchForFieldInFlexStructure($formData['processedTca']['columns'][$fieldName]['config'], $flexFormFieldName);
            $treeData = $value['config']['treeData'];
        } else {
            $treeData = $formData['processedTca']['columns'][$fieldName]['config']['treeData'];
        }

        $json = json_encode($treeData['items']);
        $response->getBody()->write($json);
        return $response;
    }

    /**
     * A workaround for flexforms - there is no easy way to get flex field by key, so we need to search for it
     *
     * @todo remove me once flexforms are refactored
     *
     * @param array $array
     * @param string $needle
     * @return array
     */
    protected function searchForFieldInFlexStructure(array $array, $needle)
    {
        $needle = trim($needle);
        $iterator  = new \RecursiveArrayIterator($array);
        $recursive = new \RecursiveIteratorIterator(
            $iterator,
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($recursive as $key => $value) {
            if ($key === $needle) {
                return $value;
            }
        }
        return [];
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}

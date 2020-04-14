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

namespace TYPO3\CMS\Backend\Tests\Functional\Form;

use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service class for handling recurring tasks in tests.
 */
class FormTestService
{
    /**
     * Creates an outerWrapContainer node for a new record of the given table.
     *
     * @param string $table
     * @param array $defaults
     * @return array
     */
    public function createNewRecordForm(string $table, array $defaults = [])
    {
        $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);

        $formDataCompilerInput = [
            'tableName' => $table,
            'vanillaUid' => 0,
            'command' => 'new',
            'databaseRow' => $defaults
        ];
        $formData = $formDataCompiler->compile($formDataCompilerInput);

        $formData['renderType'] = 'outerWrapContainer';
        return $nodeFactory->create($formData)->render();
    }

    /**
     * Checks if the form field with the given name exists in the given form HTML.
     *
     * @param string $fieldName
     * @param string $formHtml
     * @return bool
     */
    public function formHtmlContainsField(string $fieldName, string $formHtml): bool
    {
        return strpos($formHtml, '[' . $fieldName . ']');
    }
}

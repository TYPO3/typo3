<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Form\FieldWizard;

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

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render file upload,
 * typically used with type=group and internal_type=file and file_reference.
 */
class FileUpload extends AbstractNode
{
    /**
     * Render file upload
     *
     * @return array
     */
    public function render(): array
    {
        $backendUser = $this->getBackendUserAuthentication();
        $result = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        $itemName = $parameterArray['itemFormElName'];
        $uploadFieldId = $parameterArray['itemFormElID'] . '_files';
        $config = $parameterArray['fieldConf']['config'];
        $maxItems = $config['maxitems'];
        $isDirectFileUploadEnabled = (bool)$backendUser->uc['edit_docModuleUpload'];

        if (!isset($config['internal_type'])
            || $config['internal_type'] !== 'file'
            || !$isDirectFileUploadEnabled
            || empty($config['uploadfolder'])
        ) {
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Deprecation logged by TcaMigration class.
            // No upload if disabled for user or upload folder missing
            return $result;
        }

        if ($maxItems === 1) {
            // If maxItems = 1 then automatically replace the current item file selector
            $resultArray['additionalJavaScriptPost'][] =
                'TBE_EDITOR.clearBeforeSettingFormValueFromBrowseWin[' . GeneralUtility::quoteJSvalue($itemName) . '] = {'
                    . 'itemFormElID_file: ' . GeneralUtility::quoteJSvalue($uploadFieldId)
                . '}';
        }

        // Insert the multiple attribute to enable HTML5 multiple file upload
        $selectorMultipleAttribute = '';
        $multipleFilenameSuffix = '';
        if ($maxItems > 1) {
            $selectorMultipleAttribute = ' multiple="multiple"';
            $multipleFilenameSuffix = '[]';
        }

        $html= [];
        $html[] = '<div id="' . $uploadFieldId . '">';
        $html[] =   '<input';
        $html[] =       ' type="file"';
        $html[] =       $selectorMultipleAttribute;
        $html[] =       ' name="data_files' . $this->data['elementBaseName'] . $multipleFilenameSuffix . '"';
        $html[] =       ' size="35"';
        $html[] =       ' onchange="' . implode('', $parameterArray['fieldChangeFunc']) . '"';
        $html[] =   '/>';
        $html[] = '</div>';

        $result['html'] = implode(LF, $html);
        return $result;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}

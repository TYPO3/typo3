<?php
namespace TYPO3\CMS\Recordlist\View;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;

/**
 * Renders utility forms used in the views for files/folders of Element and Link Browser
 */
class FolderUtilityRenderer
{
    /**
     * @var LinkParameterProviderInterface
     */
    protected $parameterProvider;

    /**
     * @param LinkParameterProviderInterface $parameterProvider
     */
    public function __construct(LinkParameterProviderInterface $parameterProvider)
    {
        $this->parameterProvider = $parameterProvider;
    }

    /**
     * For TBE: Makes a form for creating new folders in the filemount the user is browsing.
     * The folder creation request is sent to the tce_file.php script in the core which will handle the creation.
     *
     * @param Folder $folderObject Absolute filepath on server in which to create the new folder.
     *
     * @return string HTML for the create folder form.
     */
    public function createFolder(Folder $folderObject)
    {
        if (!$folderObject->checkActionPermission('write')) {
            return '';
        }
        $backendUser = $this->getBackendUser();
        if (!$backendUser->isAdmin() && !$backendUser->getTSConfigVal('options.createFoldersInEB')) {
            return '';
        }
        // Don't show Folder-create form if it's denied
        if ($backendUser->getTSConfigVal('options.folderTree.hideCreateFolder')) {
            return '';
        }
        $lang = $this->getLanguageService();

        $formAction = BackendUtility::getModuleUrl('tce_file');
        $markup = [];
        $markup[] = '<div class="element-browser-section element-browser-createfolder">';
        $markup[] = '<form action="' . htmlspecialchars($formAction)
            . '" method="post" name="editform" enctype="multipart/form-data">';
        $markup[] = '<h3>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:create_folder.title', true) . ':</h3>';
        $markup[] = '<p><strong>' . $lang->getLL('path', true) . ':</strong>'
            . htmlspecialchars($folderObject->getIdentifier()) . '</p>';

        $a = 1;
        $markup[] = '<input class="form-control" type="text" name="file[newfolder][' . $a . '][data]" />';
        $markup[] = '<input type="hidden" name="file[newfolder][' . $a . '][target]" value="'
            . htmlspecialchars($folderObject->getCombinedIdentifier()) . '" />';

        // Make footer of upload form, including the submit button:
        $redirectValue = $this->parameterProvider->getScriptUrl() . GeneralUtility::implodeArrayForUrl(
            '',
            $this->parameterProvider->getUrlParameters(
                ['identifier' => $folderObject->getCombinedIdentifier()]
            )
        );
        $markup[] = '<input type="hidden" name="redirect" value="' . htmlspecialchars($redirectValue) . '" />';
        $markup[] = '<input class="btn btn-default" type="submit" name="submit" value="'
            . $lang->sL('LLL:EXT:lang/locallang_core.xlf:create_folder.submit', true) . '" />';

        $markup[] = '   </form>';
        $markup[] = '</div>';

        return implode(LF, $markup);
    }

    /**
     * Makes an upload form for uploading files to the filemount the user is browsing.
     * The files are uploaded to the tce_file.php script in the core which will handle the upload.
     *
     * @param Folder $folderObject
     * @param string[] $allowedExtensions
     *
     * @return string HTML for an upload form.
     */
    public function uploadForm(Folder $folderObject, array $allowedExtensions)
    {
        if (!$folderObject->checkActionPermission('write')) {
            return '';
        }
        // Read configuration of upload field count
        $userSetting = $this->getBackendUser()->getTSConfigVal('options.folderTree.uploadFieldsInLinkBrowser');
        $count = isset($userSetting) ? (int)$userSetting : 1;
        if ($count === 0) {
            return '';
        }

        $count = (int)$count === 0 ? 1 : (int)$count;
        // Create header, showing upload path:
        $header = $folderObject->getIdentifier();
        $lang = $this->getLanguageService();
        // Create a list of allowed file extensions with the readable format "youtube, vimeo" etc.
        $fileExtList = [];
        foreach ($allowedExtensions as $fileExt) {
            if (GeneralUtility::verifyFilenameAgainstDenyPattern('.' . $fileExt)) {
                $fileExtList[] = '<span class="label label-success">'
                    . strtoupper(htmlspecialchars($fileExt)) . '</span>';
            }
        }
        $formAction = BackendUtility::getModuleUrl('tce_file');
        $combinedIdentifier = $folderObject->getCombinedIdentifier();
        $markup = [];
        $markup[] = '<div class="element-browser-section element-browser-upload">';
        $markup[] = '   <form action="' . htmlspecialchars($formAction)
            . '" method="post" name="editform" enctype="multipart/form-data">';
        $markup[] = '   <h3>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:file_upload.php.pagetitle', true) . ':</h3>';
        $markup[] = '   <p><strong>' . $lang->getLL('path', true) . ':</strong>' . htmlspecialchars($header) . '</p>';
        // Traverse the number of upload fields:
        for ($a = 1; $a <= $count; $a++) {
            $markup[] = '<div class="form-group">';
            $markup[] = '<span class="btn btn-default btn-file">';
            $markup[] = '<input type="file" multiple="multiple" name="upload_' . $a . '[]" size="50" />';
            $markup[] = '</span>';
            $markup[] = '</div>';
            $markup[] = '<input type="hidden" name="file[upload][' . $a . '][target]" value="'
                . htmlspecialchars($combinedIdentifier) . '" />';
            $markup[] = '<input type="hidden" name="file[upload][' . $a . '][data]" value="' . $a . '" />';
        }
        $redirectValue = $this->parameterProvider->getScriptUrl() . GeneralUtility::implodeArrayForUrl(
            '',
            $this->parameterProvider->getUrlParameters(['identifier' => $combinedIdentifier])
        );
        $markup[] = '<input type="hidden" name="redirect" value="' . htmlspecialchars($redirectValue) . '" />';

        if (!empty($fileExtList)) {
            $markup[] = '<div class="form-group">';
            $markup[] = '    <label>';
            $markup[] = $lang->sL('LLL:EXT:lang/locallang_core.xlf:cm.allowedFileExtensions', true) . '<br/>';
            $markup[] = '    </label>';
            $markup[] = '    <div class="form-control">';
            $markup[] = implode(' ', $fileExtList);
            $markup[] = '    </div>';
            $markup[] = '</div>';
        }

        $markup[] = '<div class="checkbox">';
        $markup[] = '    <label>';
        $markup[] = '    <input type="checkbox" name="overwriteExistingFiles" id="overwriteExistingFiles" value="1" />';
        $markup[] = $lang->sL('LLL:EXT:lang/locallang_misc.xlf:overwriteExistingFiles', true);
        $markup[] = '    </label>';
        $markup[] = '</div>';
        $markup[] = '<input class="btn btn-default" type="submit" name="submit" value="'
            . $lang->sL('LLL:EXT:lang/locallang_core.xlf:file_upload.php.submit', true) . '" />';

        $markup[] = '   </form>';
        $markup[] = '</div>';

        $code = implode(LF, $markup);

        // Add online media
        // Create a list of allowed file extensions in a readable format "youtube, vimeo" etc.
        $fileExtList = [];
        $onlineMediaFileExt = OnlineMediaHelperRegistry::getInstance()->getSupportedFileExtensions();
        foreach ($onlineMediaFileExt as $fileExt) {
            if (GeneralUtility::verifyFilenameAgainstDenyPattern('.' . $fileExt)
                && (empty($allowedExtensions) || in_array($fileExt, $allowedExtensions, true))
            ) {
                $fileExtList[] = '<span class="label label-success">'
                    . strtoupper(htmlspecialchars($fileExt)) . '</span>';
            }
        }
        if (!empty($fileExtList)) {
            $formAction = BackendUtility::getModuleUrl('online_media');

            $markup = [];
            $markup[] = '<div class="element-browser-section element-browser-mediaurls">';
            $markup[] = '   <form action="' . htmlspecialchars($formAction)
                . '" method="post" name="editform1" id="typo3-addMediaForm" enctype="multipart/form-data">';
            $markup[] = '<h3>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:online_media.new_media', true) . ':</h3>';
            $markup[] = '<p><strong>' . $lang->getLL('path', true) . ':</strong>' . htmlspecialchars($header) . '</p>';
            $markup[] = '<div class="form-group">';
            $markup[] = '<input type="hidden" name="file[newMedia][0][target]" value="'
                . htmlspecialchars($folderObject->getCombinedIdentifier()) . '" />';
            $markup[] = '<input type="hidden" name="file[newMedia][0][allowed]" value="'
                . htmlspecialchars(implode(',', $allowedExtensions)) . '" />';
            $markup[] = '<input type="text" name="file[newMedia][0][url]" class="form-control" placeholder="'
                . $lang->sL('LLL:EXT:lang/locallang_core.xlf:online_media.new_media.placeholder', true) . '" />';
            $markup[] = '<button class="btn btn-default">'
                . $lang->sL('LLL:EXT:lang/locallang_core.xlf:online_media.new_media.submit', true) . '</button>';
            $markup[] = '</div>';
            $markup[] = '<div class="form-group">';
            $markup[] = '    <label>';
            $markup[] = $lang->sL('LLL:EXT:lang/locallang_core.xlf:online_media.new_media.allowedProviders') . '<br/>';
            $markup[] = '    </label>';
            $markup[] = '    <div class="form-control">';
            $markup[] = implode(' ', $fileExtList);
            $markup[] = '    </div>';
            $markup[] = '</div>';
            $markup[] = '<input type="hidden" name="redirect" value="' . htmlspecialchars($redirectValue) . '" />';
            $markup[] = '</div>';
            $markup[] = '</form>';
            $markup[] = '</div>';

            $code .= implode(LF, $markup);
        }

        return $code;
    }

    /**
     * Get the HTML data required for the file search field of the TYPO3 Element Browser.
     *
     * @param string $searchWord
     * @return string HTML data required for the search field in the file list of the Element Browser
     */
    public function getFileSearchField($searchWord)
    {
        $action = $this->parameterProvider->getScriptUrl()
            . GeneralUtility::implodeArrayForUrl('', $this->parameterProvider->getUrlParameters([]));

        $markup = [];
        $markup[] = '<form method="post" action="' . htmlspecialchars($action) . '" style="padding-bottom: 15px;">';
        $markup[] = '   <div class="input-group">';
        $markup[] = '       <input class="form-control" type="text" name="searchWord" value="'
            . htmlspecialchars($searchWord) . '">';
        $markup[] = '       <span class="input-group-btn">';
        $markup[] = '           <button class="btn btn-default" type="submit">'
            . htmlspecialchars(
                $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:search')
            )
            . '</button>';
        $markup[] = '       </span>';
        $markup[] = '   </div>';
        $markup[] = '</form>';
        return implode(LF, $markup);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}

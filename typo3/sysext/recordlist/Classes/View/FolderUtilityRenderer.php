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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;

/**
 * Renders utility forms used in the views for files/folders of Element and Link Browser
 * @internal
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
        $backendUser = $this->getBackendUser();
        $userTsConfig = $backendUser->getTSConfig();
        $lang = $this->getLanguageService();

        if (!$folderObject->checkActionPermission('write')
            || !$backendUser->isAdmin() && !($userTsConfig['options.']['createFoldersInEB'] ?? false)
            || $userTsConfig['options.']['folderTree.']['hideCreateFolder'] ?? false
        ) {
            // Do not show create folder form if it is denied
            return '';
        }

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $formAction = (string)$uriBuilder->buildUriFromRoute('tce_file');
        $markup = [];
        $markup[] = '<form action="' . htmlspecialchars($formAction)
            . '" method="post" name="editform" enctype="multipart/form-data">';
        $markup[] = '<h3>' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:create_folder.title')) . ':</h3>';
        $markup[] = '<p><strong>' . htmlspecialchars($lang->getLL('path')) . ':</strong>'
            . htmlspecialchars($folderObject->getIdentifier()) . '</p>';

        $a = 1;
        $markup[] = '<div class="form-group">';
        $markup[] = '<div class="input-group">';
        $markup[] = '<input class="form-control" type="text" name="data[newfolder][' . $a . '][data]" />';
        $markup[] = '<span class="input-group-btn">';
        $markup[] = '<input class="btn btn-default" type="submit" name="submit" value="'
            . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:create_folder.submit')) . '" />';
        $markup[] = '</span>';
        $markup[] = '</div>';
        $markup[] = '<input type="hidden" name="data[newfolder][' . $a . '][target]" value="'
            . htmlspecialchars($folderObject->getCombinedIdentifier()) . '" />';

        // Make footer of upload form, including the submit button:
        $redirectValue = $this->parameterProvider->getScriptUrl() . HttpUtility::buildQueryString(
                $this->parameterProvider->getUrlParameters(
                    ['identifier' => $folderObject->getCombinedIdentifier()]
                ),
                '&'
            );
        $markup[] = '<input type="hidden" name="data[newfolder][' . $a . '][redirect]" value="' . htmlspecialchars($redirectValue) . '" />';

        $markup[] = '</div></form>';

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
        $count = (int)($this->getBackendUser()->getTSConfig()['options.']['folderTree.']['uploadFieldsInLinkBrowser'] ?? 1);
        if ($count === 0) {
            return '';
        }

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
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $formAction = (string)$uriBuilder->buildUriFromRoute('tce_file');
        $combinedIdentifier = $folderObject->getCombinedIdentifier();
        $markup = [];
        $markup[] = '<form action="' . htmlspecialchars($formAction)
            . '" method="post" name="editform" enctype="multipart/form-data">';
        $markup[] = '   <h3>' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:file_upload.php.pagetitle')) . ':</h3>';
        $markup[] = '   <p><strong>' . htmlspecialchars($lang->getLL('path')) . ':</strong>' . htmlspecialchars($header) . '</p>';
        // Traverse the number of upload fields:
        for ($a = 1; $a <= $count; $a++) {
            $markup[] = '<div class="form-group">';
            $markup[] = '<span class="btn btn-default btn-file">';
            $markup[] = '<input type="file" multiple="multiple" name="upload_' . $a . '[]" size="50" />';
            $markup[] = '</span>';
            $markup[] = '</div>';
            $markup[] = '<input type="hidden" name="data[upload][' . $a . '][target]" value="'
                . htmlspecialchars($combinedIdentifier) . '" />';
            $markup[] = '<input type="hidden" name="data[upload][' . $a . '][data]" value="' . $a . '" />';
        }
        $redirectValue = $this->parameterProvider->getScriptUrl() . HttpUtility::buildQueryString(
                $this->parameterProvider->getUrlParameters(['identifier' => $combinedIdentifier]),
                '&'
            );
        $markup[] = '<input type="hidden" name="data[upload][1][redirect]" value="' . htmlspecialchars($redirectValue) . '" />';

        if (!empty($fileExtList)) {
            $markup[] = '<div class="form-group">';
            $markup[] = '    <label>';
            $markup[] = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.allowedFileExtensions')) . '<br/>';
            $markup[] = '    </label>';
            $markup[] = '    <div>';
            $markup[] = implode(' ', $fileExtList);
            $markup[] = '    </div>';
            $markup[] = '</div>';
        }

        $markup[] = '<div class="checkbox">';
        $markup[] = '    <label>';
        $markup[] = '    <input type="checkbox" name="overwriteExistingFiles" id="overwriteExistingFiles" value="replace" />';
        $markup[] = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:overwriteExistingFiles'));
        $markup[] = '    </label>';
        $markup[] = '</div>';
        $markup[] = '<input class="btn btn-default" type="submit" name="submit" value="'
            . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:file_upload.php.submit')) . '" />';

        $markup[] = '</form>';

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
            $formAction = (string)$uriBuilder->buildUriFromRoute('online_media');

            $markup = [];
            $markup[] = '<form action="' . htmlspecialchars($formAction)
                . '" method="post" name="editform1" id="typo3-addMediaForm" enctype="multipart/form-data">';
            $markup[] = '<h3>' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media')) . ':</h3>';
            $markup[] = '<p><strong>' . htmlspecialchars($lang->getLL('path')) . ':</strong>' . htmlspecialchars($header) . '</p>';
            $markup[] = '<div class="form-group">';
            $markup[] = '<input type="hidden" name="data[newMedia][0][target]" value="'
                . htmlspecialchars($folderObject->getCombinedIdentifier()) . '" />';
            $markup[] = '<input type="hidden" name="data[newMedia][0][allowed]" value="'
                . htmlspecialchars(implode(',', $allowedExtensions)) . '" />';
            $markup[] = '<div class="input-group">';
            $markup[] = '<input type="text" name="data[newMedia][0][url]" class="form-control" placeholder="'
                . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media.placeholder')) . '" />';
            $markup[] = '<div class="input-group-btn">';
            $markup[] = '<button class="btn btn-default">'
                . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media.submit')) . '</button>';
            $markup[] = '</div>';
            $markup[] = '</div>';
            $markup[] = '<div class="form-group">';
            $markup[] = '<label>';
            $markup[] = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media.allowedProviders') . '<br/>';
            $markup[] = '</label>';
            $markup[] = '<div>';
            $markup[] = implode(' ', $fileExtList);
            $markup[] = '</div>';
            $markup[] = '</div>';
            $markup[] = '<input type="hidden" name="redirect" value="' . htmlspecialchars($redirectValue) . '" />';
            $markup[] = '</form>';

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
            . HttpUtility::buildQueryString($this->parameterProvider->getUrlParameters([]), '&');

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

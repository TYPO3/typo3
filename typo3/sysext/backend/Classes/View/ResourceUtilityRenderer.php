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

namespace TYPO3\CMS\Backend\View;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Renders utility forms for creating files and folder.
 * Used in different views, e.g. FileList but also in Element and Link Browsers.
 * @internal
 */
class ResourceUtilityRenderer
{
    /**
     * @var LinkParameterProviderInterface
     */
    protected $parameterProvider;
    protected UriBuilder $uriBuilder;

    public function __construct(LinkParameterProviderInterface $parameterProvider)
    {
        $this->parameterProvider = $parameterProvider;
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
    }

    /**
     * For TBE: Makes a form for creating new folders in the file mount the user is browsing.
     * The folder creation request is sent to the tce_file.php script in the core which will handle the creation.
     *
     * @param Folder $folderObject Absolute filepath on server in which to create the new folder.
     *
     * @return string HTML for the create folder form.
     */
    public function createFolder(ServerRequestInterface $request, Folder $folderObject)
    {
        $lang = $this->getLanguageService();

        if (!$folderObject->checkActionPermission('write')) {
            // Do not show create folder form if it is denied
            return '';
        }

        $formAction = (string)$this->uriBuilder->buildUriFromRoute('tce_file');
        $markup = [];
        $markup[] = '<form class="pt-3 pb-3" action="' . htmlspecialchars($formAction)
            . '" method="post" name="editform" enctype="multipart/form-data">';
        $markup[] = '<h4>' . htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:create_folder.title')) . '</h4>';
        $markup[] = '<div class="input-group">';
        $markup[] = '<input class="form-control" type="text" name="data[newfolder][0][data]" placeholder="' . htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:create_folder.placeholder')) . '" />';
        $markup[] = '<input class="btn btn-default" type="submit" name="submit" value="'
            . htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:create_folder.submit')) . '" />';
        $markup[] = '</div>';
        $markup[] = '<input type="hidden" name="data[newfolder][0][target]" value="'
            . htmlspecialchars($folderObject->getCombinedIdentifier()) . '" />';

        // Make footer of upload form, including the submit button:
        $redirectValue = (string)$this->uriBuilder->buildUriFromRequest(
            $request,
            $this->parameterProvider->getUrlParameters(
                ['identifier' => $folderObject->getCombinedIdentifier()]
            )
        );
        $markup[] = '<input type="hidden" name="redirect" value="' . htmlspecialchars($redirectValue) . '" />';

        $markup[] = '</form>';

        return implode(LF, $markup);
    }

    /**
     * For TBE: Creates a form for creating new text files.
     *
     * @param Folder $folderObject Folder object in which to create the text file.
     *
     * @return string HTML for the text file creation form.
     */
    public function createRegularFile(ServerRequestInterface $request, Folder $folderObject): string
    {
        if (!$folderObject->checkActionPermission('write') || !$folderObject->getStorage()->checkUserActionPermission('add', 'File')) {
            return '';
        }

        $markup = [];
        $lang = $this->getLanguageService();
        $fileNameVerifier = GeneralUtility::makeInstance(FileNameValidator::class);

        $redirectValue = (string)$this->uriBuilder->buildUriFromRequest(
            $request,
            $this->parameterProvider->getUrlParameters(
                ['identifier' => $folderObject->getCombinedIdentifier()]
            )
        );

        // Create a list of allowed text file extensions
        $textFileExt = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'], true);
        $allowedTextFileList = [];
        foreach ($textFileExt as $fileExt) {
            if ($fileNameVerifier->isValid('.' . $fileExt)) {
                $allowedTextFileList[] = '<li class="badge badge-success">' . strtoupper(htmlspecialchars($fileExt)) . '</li>';
            }
        }

        if (!empty($allowedTextFileList)) {
            $formAction = (string)$this->uriBuilder->buildUriFromRoute('tce_file');

            $markup[] = '<form class="pt-3 pb-3" action="' . htmlspecialchars($formAction) . '" method="post" name="newFileForm" enctype="multipart/form-data">';
            $markup[] = '<input type="hidden" name="data[newfile][0][target]" value="' . htmlspecialchars($folderObject->getCombinedIdentifier()) . '" />';
            $markup[] = '<input type="hidden" name="redirect" value="' . htmlspecialchars($redirectValue) . '" />';
            $markup[] = '<input type="hidden" name="edit" value="true" />';
            $markup[] = '<h4>' . htmlspecialchars($lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:file_newfolder.php.newfile')) . '</h4>';
            $markup[] = '<div class="input-group">';
            $markup[] = '<input class="form-control" type="text" name="data[newfile][0][data]" placeholder="' . htmlspecialchars($lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:file_newfolder.php.label_newfile')) . '" />';
            $markup[] = '<button class="btn btn-default" type="submit" name="submitCreateFileForm" value="1">' . htmlspecialchars($lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:file_newfolder.php.newfile_submit')) . '</button>';
            $markup[] = '</div>';
            $markup[] = '<div class="form-text mt-1">';
            $markup[] = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.allowedEditableTextFileExtensions'));
            $markup[] = '<ul class="badge-list">' . implode(' ', $allowedTextFileList) . '</ul>';
            $markup[] = '</div>';
            $markup[] = '</form>';
        }

        return implode(LF, $markup);
    }

    /**
     * For TBE: Creates a form for adding online media files (YouTube, Vimeo, etc.).
     *
     * @param Folder $folderObject Folder object in which to create the online media file.
     * @param FileExtensionFilter|null $fileExtensionFilter Optional filter for allowed/disallowed file extensions.
     *
     * @return string HTML for the online media form.
     */
    public function addOnlineMedia(ServerRequestInterface $request, Folder $folderObject, ?FileExtensionFilter $fileExtensionFilter = null): string
    {
        if (!$folderObject->checkActionPermission('write') || !$folderObject->getStorage()->checkUserActionPermission('add', 'File')) {
            return '';
        }

        $markup = [];
        $lang = $this->getLanguageService();
        $fileNameVerifier = GeneralUtility::makeInstance(FileNameValidator::class);
        $onlineMediaHelperRegistry = GeneralUtility::makeInstance(OnlineMediaHelperRegistry::class);

        $redirectValue = (string)$this->uriBuilder->buildUriFromRequest(
            $request,
            $this->parameterProvider->getUrlParameters(
                ['identifier' => $folderObject->getCombinedIdentifier()]
            )
        );

        // Create a list of allowed online media file extensions
        $onlineMediaFileExt = $onlineMediaHelperRegistry->getSupportedFileExtensions();
        $allowedOnlineMediaList = [];
        foreach ($onlineMediaFileExt as $fileExt) {
            if ($fileNameVerifier->isValid('.' . $fileExt)
                && ($fileExtensionFilter === null || $fileExtensionFilter->isAllowed($fileExt))
            ) {
                $allowedOnlineMediaList[] = '<li class="badge badge-success">' . strtoupper(htmlspecialchars($fileExt)) . '</li>';
            }
        }

        if (!empty($allowedOnlineMediaList)) {
            $formAction = (string)$this->uriBuilder->buildUriFromRoute('online_media');

            $markup[] = '<form class="pt-3 pb-3" action="' . htmlspecialchars($formAction) . '" method="post" name="newMediaForm" enctype="multipart/form-data">';
            $markup[] = '<input type="hidden" name="data[newMedia][0][target]" value="' . htmlspecialchars($folderObject->getCombinedIdentifier()) . '" />';
            $markup[] = '<input type="hidden" name="redirect" value="' . htmlspecialchars($redirectValue) . '" />';
            $markup[] = '<h4>' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media')) . '</h4>';
            $markup[] = '<div class="input-group">';
            $markup[] = '<input class="form-control" type="url" name="data[newMedia][0][url]" placeholder="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media.placeholder')) . '" />';
            $markup[] = '<button class="btn btn-default" type="submit">' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media.submit')) . '</button>';
            $markup[] = '</div>';
            $markup[] = '<div class="form-text mt-1">';
            $markup[] = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media.allowedProviders'));
            $markup[] = '<ul class="badge-list">' . implode(' ', $allowedOnlineMediaList) . '</ul>';
            $markup[] = '</div>';
            $markup[] = '</form>';
        }

        return implode(LF, $markup);
    }

    /**
     * For TBE: Creates a drag-uploader trigger button for file uploads.
     * Used in the element browser context with drag-uploader support.
     *
     * @param Folder $folderObject Folder object in which to upload files.
     * @param FileExtensionFilter|null $fileExtensionFilter Optional filter for allowed/disallowed file extensions.
     *
     * @return string HTML for the drag-uploader trigger.
     */
    public function createDragUpload(Folder $folderObject, ?FileExtensionFilter $fileExtensionFilter = null, bool $checkFileBrowserPermission = false): string
    {
        if (!$folderObject->checkActionPermission('write') || !$folderObject->getStorage()->checkUserActionPermission('add', 'File')) {
            return '';
        }

        if ($checkFileBrowserPermission && !($this->getBackendUser()->getTSConfig()['options.']['folderTree.']['uploadFieldsInLinkBrowser'] ?? true)) {
            return '';
        }

        $lang = $this->getLanguageService();
        $fileNameVerifier = GeneralUtility::makeInstance(FileNameValidator::class);

        // Determine allowed/disallowed file extensions
        $list = ['*'];
        $denyList = false;
        $allowedFileExtensionsList = [];

        if ($fileExtensionFilter !== null) {
            $resolvedFileExtensions = $fileExtensionFilter->getFilteredFileExtensions();
            if (($resolvedFileExtensions['allowedFileExtensions'] ?? []) !== []) {
                $list = $resolvedFileExtensions['allowedFileExtensions'];
            } elseif (($resolvedFileExtensions['disallowedFileExtensions'] ?? []) !== []) {
                $denyList = true;
                $list = $resolvedFileExtensions['disallowedFileExtensions'];
            }
        }

        foreach ($list as $fileExt) {
            if (($fileExt === '*' && !$denyList) || $fileNameVerifier->isValid('.' . $fileExt)) {
                $allowedFileExtensionsList[] = '<li class="badge badge-' . ($denyList ? 'danger' : 'success') . '">' . strtoupper(htmlspecialchars($fileExt)) . '</li>';
            }
        }

        // Only render the form if there are allowed file extensions
        if (empty($allowedFileExtensionsList)) {
            return '';
        }

        $markup = [];
        $markup[] = '<div class="pt-3 pb-3">';
        $markup[] = '<h4>' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:file_upload.php.pagetitle')) . '</h4>';
        $markup[] = '<button type="button" class="btn btn-default t3js-drag-uploader-trigger">';
        $markup[] = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:file_upload.select-and-submit'));
        $markup[] = '</button>';
        $markup[] = '<div class="form-text mt-1">';
        $markup[] = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.' . ($denyList ? 'disallowedFileExtensions' : 'allowedFileExtensions')));
        $markup[] = '<ul class="badge-list">' . implode(' ', $allowedFileExtensionsList) . '</ul>';
        $markup[] = '</div>';
        $markup[] = '</div>';

        return implode(LF, $markup);
    }

    /**
     * Makes an upload form for uploading files to the file mount the user is browsing.
     * The files are uploaded to the tce_file.php script in the core which will handle the upload.
     *
     * @return string HTML for an upload form.
     */
    public function uploadForm(ServerRequestInterface $request, Folder $folderObject, ?FileExtensionFilter $fileExtensionFilter = null)
    {
        if (!$folderObject->checkActionPermission('write')) {
            return '';
        }
        $allowUpload = (bool)($this->getBackendUser()->getTSConfig()['options.']['folderTree.']['uploadFieldsInLinkBrowser'] ?? true);
        if (!$allowUpload) {
            return '';
        }

        $lang = $this->getLanguageService();
        $fileNameVerifier = GeneralUtility::makeInstance(FileNameValidator::class);

        // Determine allowed/disallowed file extensions
        $list = ['*'];
        $denyList = false;
        $allowedFileExtensionsList = [];

        if ($fileExtensionFilter !== null) {
            $resolvedFileExtensions = $fileExtensionFilter->getFilteredFileExtensions();
            if (($resolvedFileExtensions['allowedFileExtensions'] ?? []) !== []) {
                $list = $resolvedFileExtensions['allowedFileExtensions'];
            } elseif (($resolvedFileExtensions['disallowedFileExtensions'] ?? []) !== []) {
                $denyList = true;
                $list = $resolvedFileExtensions['disallowedFileExtensions'];
            }
        }

        foreach ($list as $fileExt) {
            if (($fileExt === '*' && !$denyList) || $fileNameVerifier->isValid('.' . $fileExt)) {
                $allowedFileExtensionsList[] = '<li class="badge badge-' . ($denyList ? 'danger' : 'success') . '">' . strtoupper(htmlspecialchars($fileExt)) . '</li>';
            }
        }

        // Only render the form if there are allowed file extensions
        if (empty($allowedFileExtensionsList)) {
            return '';
        }

        $formAction = (string)$this->uriBuilder->buildUriFromRoute('tce_file');
        $combinedIdentifier = $folderObject->getCombinedIdentifier();
        $redirectValue = (string)$this->uriBuilder->buildUriFromRequest($request, $this->parameterProvider->getUrlParameters(['identifier' => $combinedIdentifier]));

        $markup = [];
        $markup[] = '<form class="pt-3 pb-3" action="' . htmlspecialchars($formAction) . '" method="post" name="editform" enctype="multipart/form-data">';
        $markup[] = '<input type="hidden" name="data[upload][0][target]" value="' . htmlspecialchars($combinedIdentifier) . '" />';
        $markup[] = '<input type="hidden" name="data[upload][0][data]" value="0" />';
        $markup[] = '<input type="hidden" name="redirect" value="' . htmlspecialchars($redirectValue) . '" />';
        $markup[] = '<div class="row">';
        $markup[] = '<div class="col-auto me-auto">';
        $markup[] = '   <h4>' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:file_upload.php.pagetitle')) . '</h4>';
        $markup[] = '</div>';
        $markup[] = '<div class="col-auto">';
        $markup[] = '<div class="form-check form-switch">';
        $markup[] = '    <input class="form-check-input" type="checkbox" name="overwriteExistingFiles" id="overwriteExistingFiles" value="replace" />';
        $markup[] = '    <label class="form-check-label" for="overwriteExistingFiles">';
        $markup[] =          htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:overwriteExistingFiles'));
        $markup[] = '    </label>';
        $markup[] = '</div>';
        $markup[] = '</div>';
        $markup[] = '<div class="col-12">';
        $markup[] = '<div class="input-group">';
        $markup[] = '<input type="file" multiple="multiple" name="upload_0[]" class="form-control" />';
        $markup[] = '<input class="btn btn-default" type="submit" name="submit" value="'
            . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:file_upload.php.submit')) . '" />';
        $markup[] = '</div>';
        $markup[] = '</div>';
        $markup[] = '</div>';
        $markup[] = '<div class="form-text mt-1">';
        $markup[] = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.' . ($denyList ? 'disallowedFileExtensions' : 'allowedFileExtensions')));
        $markup[] = '<ul class="badge-list">' . implode(' ', $allowedFileExtensionsList) . '</ul>';
        $markup[] = '</div>';
        $markup[] = '</form>';

        return implode(LF, $markup);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}

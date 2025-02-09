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
 * Renders utility forms used in the views for files/folders of Element and Link Browser
 * @internal
 */
class FolderUtilityRenderer
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
        // Read configuration of upload field count
        $allowUpload = (bool)($this->getBackendUser()->getTSConfig()['options.']['folderTree.']['uploadFieldsInLinkBrowser'] ?? true);
        if (!$allowUpload) {
            return '';
        }

        $list = ['*'];
        $denyList = false;
        $allowedOnlineMediaList = [];
        $lang = $this->getLanguageService();

        if ($fileExtensionFilter !== null) {
            $resolvedFileExtensions = $fileExtensionFilter->getFilteredFileExtensions();
            if (($resolvedFileExtensions['allowedFileExtensions'] ?? []) !== []) {
                $list = $resolvedFileExtensions['allowedFileExtensions'];
            } elseif (($resolvedFileExtensions['disallowedFileExtensions'] ?? []) !== []) {
                $denyList = true;
                $list = $resolvedFileExtensions['disallowedFileExtensions'];
            }
        }

        $fileNameVerifier = GeneralUtility::makeInstance(FileNameValidator::class);
        foreach ($list as $fileExt) {
            if (($fileExt === '*' && !$denyList) || $fileNameVerifier->isValid('.' . $fileExt)) {
                $allowedOnlineMediaList[] = '<li class="badge badge-' . ($denyList ? 'danger' : 'success') . '">' . strtoupper(htmlspecialchars($fileExt)) . '</li>';
            }
        }
        $markup = [];
        if (!empty($allowedOnlineMediaList)) {
            $markup[] = '<div class="row">';
            $markup[] = '    <label>';
            $markup[] = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.' . ($denyList ? 'disallowedFileExtensions' : 'allowedFileExtensions'))) . '<br/>';
            $markup[] = '    </label>';
            $markup[] = '    <ul>' . implode(' ', $allowedOnlineMediaList) . '</ul>';
            $markup[] = '</div>';
        }

        $formAction = (string)$this->uriBuilder->buildUriFromRoute('tce_file');
        $combinedIdentifier = $folderObject->getCombinedIdentifier();
        $redirectValue = (string)$this->uriBuilder->buildUriFromRequest($request, $this->parameterProvider->getUrlParameters(['identifier' => $combinedIdentifier]));
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
        $markup[] = '</form>';

        $code = implode(LF, $markup);

        // Add online media
        // Create a list of allowed file extensions in a readable format "youtube, vimeo" etc.
        $allowedOnlineMediaList = [];
        foreach (GeneralUtility::makeInstance(OnlineMediaHelperRegistry::class)->getSupportedFileExtensions() as $supportedFileExtension) {
            if ($fileNameVerifier->isValid('.' . $supportedFileExtension)
                && ($fileExtensionFilter === null || $fileExtensionFilter->isAllowed($supportedFileExtension))
            ) {
                $allowedOnlineMediaList[$supportedFileExtension] = '<li class="badge badge-success">' . strtoupper(htmlspecialchars($supportedFileExtension)) . '</li>';
            }
        }
        if (!empty($allowedOnlineMediaList)) {
            $formAction = (string)$this->uriBuilder->buildUriFromRoute('online_media');

            $markup = [];
            $markup[] = '<form class="pt-3 pb-3" action="' . htmlspecialchars($formAction) . '" method="post" name="editform1" id="typo3-addMediaForm" enctype="multipart/form-data">';
            $markup[] = '<input type="hidden" name="redirect" value="' . htmlspecialchars($redirectValue) . '" />';
            $markup[] = '<input type="hidden" name="data[newMedia][0][target]" value="' . htmlspecialchars($folderObject->getCombinedIdentifier()) . '" />';
            $markup[] = '<input type="hidden" name="data[newMedia][0][allowed]" value="' . htmlspecialchars(implode(',', array_keys($allowedOnlineMediaList))) . '" />';
            $markup[] = '<h4>' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:onlinemedia.new_media')) . '</h4>';
            $markup[] = '<div class="row">';
            $markup[] = '<div class="col">';
            $markup[] = '<div class="input-group">';
            $markup[] = '<input type="url" name="data[newMedia][0][url]" class="form-control" placeholder="'
                . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media.placeholder')) . '" />';
            $markup[] = '<button class="btn btn-default">'
                . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media.submit')) . '</button>';
            $markup[] = '</div>';
            $markup[] = '</div>';
            $markup[] = '<div class="row mt-1">';
            $markup[] = '<div class="col-auto">';
            $markup[] = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media.allowedProviders');
            $markup[] = '</div>';
            $markup[] = '<ul>';
            $markup[] = implode(' ', $allowedOnlineMediaList);
            $markup[] = '</ul>';
            $markup[] = '</div>';
            $markup[] = '</div>';
            $markup[] = '</form>';

            $code .= implode(LF, $markup);
        }

        return $code;
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

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
        $code = '

			<!--
				Form, for creating new folders:
			-->
			<form action="' . htmlspecialchars(BackendUtility::getModuleUrl('tce_file')) . '" method="post" name="editform2" id="typo3-crFolderForm">
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-crFolder">
					<tr>
						<td><h3>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:file_newfolder.php.pagetitle', true) . ':</h3></td>
					</tr>
					<tr>
						<td class="c-wCell c-hCell"><strong>' . $lang->getLL('path', true) . ':</strong> '
                            . htmlspecialchars($folderObject->getIdentifier()) . '</td>
					</tr>
					<tr>
						<td class="c-wCell c-hCell">';
        // Create the new-folder name field:
        $a = 1;
        $code .= '<input size="20" type="text" name="file[newfolder][' . $a . '][data]" />'
            . '<input type="hidden" name="file[newfolder][' . $a . '][target]" value="'
            . htmlspecialchars($folderObject->getCombinedIdentifier()) . '" />';
        // Make footer of upload form, including the submit button:
        $redirectValue = $this->parameterProvider->getScriptUrl() . GeneralUtility::implodeArrayForUrl('', $this->parameterProvider->getUrlParameters(['identifier' => $folderObject->getCombinedIdentifier()]));
        $code .= '<input type="hidden" name="redirect" value="' . htmlspecialchars($redirectValue) . '" />'
            . '<input class="btn btn-default" type="submit" name="submit" value="'
            . $lang->sL('LLL:EXT:lang/locallang_core.xlf:file_newfolder.php.submit', true) . '" />';
        $code .= '</td>
					</tr>
				</table>
			</form>';
        return $code;
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
        $fileExtList = array();
        foreach ($allowedExtensions as $fileExt) {
            if (GeneralUtility::verifyFilenameAgainstDenyPattern($fileExt)) {
                $fileExtList[] = '<span class="label label-success">' . strtoupper(htmlspecialchars($fileExt)) . '</span>';
            }
        }
        $code = '
			<br />
			<!--
				Form, for uploading files:
			-->
			<form action="' . htmlspecialchars(BackendUtility::getModuleUrl('tce_file')) . '" method="post" name="editform"'
            . ' id="typo3-uplFilesForm" enctype="multipart/form-data">
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-uplFiles">
					<tr>
						<td><h3>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:file_upload.php.pagetitle', true) . ':</h3></td>
					</tr>
					<tr>
						<td class="c-wCell c-hCell"><strong>' . $lang->getLL('path', true) . ':</strong> ' . htmlspecialchars($header) . '</td>
					</tr>
					<tr>
						<td class="c-wCell c-hCell">';
        // Traverse the number of upload fields:
        $combinedIdentifier = $folderObject->getCombinedIdentifier();
        for ($a = 1; $a <= $count; $a++) {
            $code .= '<input type="file" multiple="multiple" name="upload_' . $a . '[]" size="50" />
				<input type="hidden" name="file[upload][' . $a . '][target]" value="' . htmlspecialchars($combinedIdentifier) . '" />
				<input type="hidden" name="file[upload][' . $a . '][data]" value="' . $a . '" /><br />';
        }

        $redirectValue = $this->parameterProvider->getScriptUrl() . GeneralUtility::implodeArrayForUrl('', $this->parameterProvider->getUrlParameters(['identifier' => $combinedIdentifier]));
        $code .= '<input type="hidden" name="redirect" value="' . htmlspecialchars($redirectValue) . '" />';

        if (!empty($fileExtList)) {
            $code .= '
				<div class="help-block">
					' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:cm.allowedFileExtensions', true) . '<br>
					' . implode(' ', $fileExtList) . '
				</div>
			';
        }

        $code .= '
			<div id="c-override">
				<label>
					<input type="checkbox" name="overwriteExistingFiles" id="overwriteExistingFiles" value="1" /> ' . $lang->sL('LLL:EXT:lang/locallang_misc.xlf:overwriteExistingFiles', true) . '
				</label>
			</div>
			<input class="btn btn-default" type="submit" name="submit" value="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:file_upload.php.submit', true) . '" />
		';
        $code .= '</td>
					</tr>
				</table>
			</form><br />';

        // Add online media
        // Create a list of allowed file extensions in a readable format "youtube, vimeo" etc.
        $fileExtList = array();
        $onlineMediaFileExt = OnlineMediaHelperRegistry::getInstance()->getSupportedFileExtensions();
        foreach ($onlineMediaFileExt as $fileExt) {
            if (
                GeneralUtility::verifyFilenameAgainstDenyPattern($fileExt)
                && (empty($allowedExtensions) || in_array($fileExt, $allowedExtensions, true))
            ) {
                $fileExtList[] = '<span class="label label-success">' . strtoupper(htmlspecialchars($fileExt)) . '</span>';
            }
        }
        if (!empty($fileExtList)) {
            $code .= '
				<!--
			Form, adding online media urls:
				-->
				<form action="' . htmlspecialchars(BackendUtility::getModuleUrl('online_media')) . '" method="post" name="editform1"'
                . ' id="typo3-addMediaForm">
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-uplFiles">
						<tr>
							<td><h3>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:online_media.new_media', true) . ':</h3></td>
						</tr>
						<tr>
							<td class="c-wCell c-hCell"><strong>' . $lang->getLL('path', true) . ':</strong> '
                                . htmlspecialchars($header) . '</td>
						</tr>
						<tr>
							<td class="c-wCell c-hCell">
								<input type="text" name="file[newMedia][0][url]" size="50" placeholder="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:online_media.new_media.placeholder', true) . '" />
								<input type="hidden" name="file[newMedia][0][target]" value="' . htmlspecialchars($folderObject->getCombinedIdentifier()) . '" />
								<input type="hidden" name="file[newMedia][0][allowed]" value="' . htmlspecialchars(implode(',', $allowedExtensions)) . '" />
								<button>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:online_media.new_media.submit', true) . '</button>
								<div class="help-block">
									' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:online_media.new_media.allowedProviders') . '<br />
									' . implode(' ', $fileExtList) . '
								</div>
						';
            $code .= '<input type="hidden" name="redirect" value="' . htmlspecialchars($redirectValue) . '" />';

            $code .= '</td>
					</tr>
				</table>
			</form><br />';
        }

        return $code;
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

<?php
namespace TYPO3\CMS\Backend\Controller\File;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Gateway for TCE (TYPO3 Core Engine) file-handling through POST forms.
 * This script serves as the file administration part of the TYPO3 Core Engine.
 * Basically it includes two libraries which are used to manipulate files on the server.
 * Before TYPO3 4.3, it was located in typo3/tce_file.php and redirected back to a
 * $redirectURL. Since 4.3 this class is also used for accessing via AJAX
 */
class FileController
{
    /**
     * Array of file-operations.
     *
     * @var array
     */
    protected $file;

    /**
     * Clipboard operations array
     *
     * @var array
     */
    protected $CB;

    /**
     * Defines behaviour when uploading files with names that already exist; possible values are
     * the values of the \TYPO3\CMS\Core\Resource\DuplicationBehavior enumeration
     *
     * @var \TYPO3\CMS\Core\Resource\DuplicationBehavior
     */
    protected $overwriteExistingFiles;

    /**
     * VeriCode - a hash of server specific value and other things which
     * identifies if a submission is OK. (see $GLOBALS['BE_USER']->veriCode())
     *
     * @var string
     */
    protected $vC;

    /**
     * The page where the user should be redirected after everything is done
     *
     * @var string
     */
    protected $redirect;

    /**
     * Internal, dynamic:
     * File processor object
     *
     * @var ExtendedFileUtility
     */
    protected $fileProcessor;

    /**
     * The result array from the file processor
     *
     * @var array
     */
    protected $fileData;

    /**
     * Constructor
     */
    public function __construct()
    {
        $GLOBALS['SOBE'] = $this;
        $this->init();
    }

    /**
     * Registering incoming data
     *
     * @return void
     */
    protected function init()
    {
        // Set the GPvars from outside
        $this->file = GeneralUtility::_GP('file');
        $this->CB = GeneralUtility::_GP('CB');
        $this->overwriteExistingFiles = DuplicationBehavior::cast(GeneralUtility::_GP('overwriteExistingFiles'));
        $this->vC = GeneralUtility::_GP('vC');
        $this->redirect = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('redirect'));
        $this->initClipboard();
        $this->fileProcessor = GeneralUtility::makeInstance(ExtendedFileUtility::class);
    }

    /**
     * Initialize the Clipboard. This will fetch the data about files to paste/delete if such an action has been sent.
     *
     * @return void
     */
    public function initClipboard()
    {
        if (is_array($this->CB)) {
            $clipObj = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Clipboard\Clipboard::class);
            $clipObj->initializeClipboard();
            if ($this->CB['paste']) {
                $clipObj->setCurrentPad($this->CB['pad']);
                $this->file = $clipObj->makePasteCmdArray_file($this->CB['paste'], $this->file);
            }
            if ($this->CB['delete']) {
                $clipObj->setCurrentPad($this->CB['pad']);
                $this->file = $clipObj->makeDeleteCmdArray_file($this->file);
            }
        }
    }

    /**
     * Performing the file admin action:
     * Initializes the objects, setting permissions, sending data to object.
     *
     * @return void
     */
    public function main()
    {
        // Initializing:
        $this->fileProcessor->init([], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
        $this->fileProcessor->setActionPermissions();
        $this->fileProcessor->setExistingFilesConflictMode($this->overwriteExistingFiles);
        // Checking referrer / executing:
        $refInfo = parse_url(GeneralUtility::getIndpEnv('HTTP_REFERER'));
        $httpHost = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
        if ($httpHost !== $refInfo['host'] && $this->vC !== $this->getBackendUser()->veriCode() && !$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']) {
            $this->fileProcessor->writeLog(0, 2, 1, 'Referrer host "%s" and server host "%s" did not match!', [$refInfo['host'], $httpHost]);
        } else {
            $this->fileProcessor->start($this->file);
            $this->fileData = $this->fileProcessor->processData();
        }
    }

    /**
     * Redirecting the user after the processing has been done.
     * Might also display error messages directly, if any.
     *
     * @return void
     */
    public function finish()
    {
        BackendUtility::setUpdateSignal('updateFolderTree');
        if ($this->redirect) {
            \TYPO3\CMS\Core\Utility\HttpUtility::redirect($this->redirect);
        }
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it just redirects to the given URL afterwards.
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->main();

        BackendUtility::setUpdateSignal('updateFolderTree');

        if ($this->redirect) {
            return $response
                    ->withHeader('Location', GeneralUtility::locationHeaderUrl($this->redirect))
                    ->withStatus(303);
        } else {
            // empty response
            return $response;
        }
    }

    /**
     * Handles the actual process from within the ajaxExec function
     * therefore, it does exactly the same as the real typo3/tce_file.php
     * but without calling the "finish" method, thus makes it simpler to deal with the
     * actual return value
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function processAjaxRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->main();
        $errors = $this->fileProcessor->getErrorMessages();
        if (!empty($errors)) {
            $response->getBody()->write('<t3err>' . implode(',', $errors) . '</t3err>');
            $response = $response
                ->withHeader('Content-Type', 'text/html; charset=utf-8')
                ->withStatus(500, '(AJAX)');
        } else {
            $flatResult = [];
            foreach ($this->fileData as $action => $results) {
                foreach ($results as $result) {
                    if (is_array($result)) {
                        foreach ($result as $subResult) {
                            $flatResult[$action][] = $this->flattenResultDataValue($subResult);
                        }
                    } else {
                        $flatResult[$action][] = $this->flattenResultDataValue($result);
                    }
                }
            }
            $response->getBody()->write(json_encode($flatResult));
        }
        return $response;
    }

    /**
     * Ajax entry point to check if a file exists in a folder
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function fileExistsInFolderAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $fileName = isset($request->getParsedBody()['fileName']) ? $request->getParsedBody()['fileName'] : $request->getQueryParams()['fileName'];
        $fileTarget = isset($request->getParsedBody()['fileTarget']) ? $request->getParsedBody()['fileTarget'] : $request->getQueryParams()['fileTarget'];

        /** @var \TYPO3\CMS\Core\Resource\ResourceFactory $fileFactory */
        $fileFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class);
        /** @var Folder $fileTargetObject */
        $fileTargetObject = $fileFactory->retrieveFileOrFolderObject($fileTarget);
        $processedFileName = $fileTargetObject->getStorage()->sanitizeFileName($fileName, $fileTargetObject);

        $result = false;
        if ($fileTargetObject->hasFile($processedFileName)) {
            $result = $this->flattenResultDataValue($fileTargetObject->getStorage()->getFileInFolder($processedFileName, $fileTargetObject));
        }
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    /**
     * Flatten result value from FileProcessor
     *
     * The value can be a File, Folder or boolean
     *
     * @param bool|\TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder $result
     * @return bool|string|array
     */
    protected function flattenResultDataValue($result)
    {
        if ($result instanceof \TYPO3\CMS\Core\Resource\File) {
            $thumbUrl = '';
            if (GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $result->getExtension())) {
                $processedFile = $result->process(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGEPREVIEW, []);
                if ($processedFile) {
                    $thumbUrl = $processedFile->getPublicUrl(true);
                }
            }
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $result = array_merge(
                $result->toArray(),
                [
                    'date' => BackendUtility::date($result->getModificationTime()),
                    'icon' => $iconFactory->getIconForFileExtension($result->getExtension(), Icon::SIZE_SMALL)->render(),
                    'thumbUrl' => $thumbUrl
                ]
            );
        } elseif ($result instanceof \TYPO3\CMS\Core\Resource\Folder) {
            $result = $result->getIdentifier();
        }

        return $result;
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}

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

/*
 * Base wrapper for loading old entry points, which are all used within TYPO3 directly
 * This file is only a deprecation layer, and all @deprecated entrypoints fallbacks will be removed with TYPO3 CMS 8
 * Use the UriBuilder for generating routes in your scripts to link to Ajax pages, Modules or simple pages in the Backend.
 */
use TYPO3\CMS\Backend\Controller;
use TYPO3\CMS\Core\Utility\GeneralUtility;

call_user_func(function () {
    $classLoader = require __DIR__ . '/../vendor/autoload.php';
    (new \TYPO3\CMS\Backend\Http\Application($classLoader))->run(function () {
        $originalRequestedFilenameParts = parse_url(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
        $originalRequestedFilename = basename($originalRequestedFilenameParts['path']);

        switch ($originalRequestedFilename) {
            case 'ajax.php':
                GeneralUtility::deprecationLog(
                    'The entry point to ajax.php was moved to index.php with ajaxID given. Please use BackendUtility::getAjaxUrl(\'myAjaxKey\') to link to the AJAX Call.'
                );
            break;

            case 'alt_clickmenu.php':
                GeneralUtility::deprecationLog(
                    'alt_clickmenu.php is deprecated, and will not work anymore, please use the AJAX functionality as used in the TYPO3 Core.'
                );

                $clickMenuController = GeneralUtility::makeInstance(Controller\ClickMenuController::class);
                $clickMenuController->main();
                $clickMenuController->printContent();
            break;

            case 'alt_db_navframe.php':
                GeneralUtility::deprecationLog(
                    'Usage of alt_db_navframe.php is deprecated since TYPO3 CMS 7, and will be removed in TYPO3 CMS 8'
                );

                // Make instance if it is not an AJAX call
                $pageTreeNavigationController = GeneralUtility::makeInstance(Controller\PageTreeNavigationController::class);
                $pageTreeNavigationController->initPage();
                $pageTreeNavigationController->main();
                $pageTreeNavigationController->printContent();
            break;

            case 'alt_doc.php':
                GeneralUtility::deprecationLog(
                    'The entry point to FormEngine was moved to an own module. Please use BackendUtility::getModuleUrl(\'record_edit\') to link to alt_doc.php.'
                );
                \TYPO3\CMS\Backend\Utility\BackendUtility::lockRecords();

                /* @var $editDocumentController Controller\EditDocumentController */
                $editDocumentController = GeneralUtility::makeInstance(Controller\EditDocumentController::class);

                // Preprocessing, storing data if submitted to
                $editDocumentController->preInit();

                // Checks, if a save button has been clicked (or the doSave variable is sent)
                if ($editDocumentController->doProcessData()) {
                    $formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
                    if ($formProtection->validateToken(GeneralUtility::_GP('formToken'), 'editRecord')) {
                        $editDocumentController->processData();
                    }
                }

                $editDocumentController->init();
                $editDocumentController->main();
                $editDocumentController->printContent();
            break;

            case 'alt_file_navframe.php':
                GeneralUtility::deprecationLog(
                    'The entry point to the folder tree was moved to an own module. Please use BackendUtility::getModuleUrl(\'file_navframe\') to link to alt_file_navframe.php.'
                );

                $fileSystemNavigationFrameController = GeneralUtility::makeInstance(Controller\FileSystemNavigationFrameController::class);
                $fileSystemNavigationFrameController->initPage();
                $fileSystemNavigationFrameController->main();
                $fileSystemNavigationFrameController->printContent();
            break;

            case 'browser.php':
                GeneralUtility::deprecationLog(
                    'The entry point to the file/record browser window was moved to an own module. Please use BackendUtility::getModuleUrl(\'browser\') to link to browser.php.'
                );

                $elementBrowserFramesetController = GeneralUtility::makeInstance(\TYPO3\CMS\Recordlist\Controller\ElementBrowserFramesetController::class);
                $elementBrowserFramesetController->main();
                $elementBrowserFramesetController->printContent();
            break;

            case 'db_new.php':
                GeneralUtility::deprecationLog(
                    'The entry point to create a new database entry was moved to an own module. Please use BackendUtility::getModuleUrl(\'db_new\') to link to db_new.php.'
                );

                $newRecordController = GeneralUtility::makeInstance(Controller\NewRecordController::class);
                $newRecordController->main();
                $newRecordController->printContent();
            break;

            case 'dummy.php':
                GeneralUtility::deprecationLog(
                    'The entry point to the dummy window was moved to an own module. Please use BackendUtility::getModuleUrl(\'dummy\') to link to dummy.php.'
                );

                $dummyController = GeneralUtility::makeInstance(Controller\DummyController::class);
                $dummyController->main();
                $dummyController->printContent();
            break;

            case 'init.php':
                GeneralUtility::deprecationLog(
                    'Usage of typo3/init.php is deprecated. Use index.php with Routing or the Backend Application class directly. See index.php for usage'
                );
            break;

            case 'login_frameset.php':
                GeneralUtility::deprecationLog(
                    'Login frameset is moved to an own module. Please use BackendUtility::getModuleUrl(\'login_frameset\') to link to login_frameset.php.'
                );

                // Make instance:
                $loginFramesetController = GeneralUtility::makeInstance(Controller\LoginFramesetController::class);
                $loginFramesetController->main();
                $loginFramesetController->printContent();
            break;

            case 'logout.php':
                GeneralUtility::deprecationLog(
                    'The entry point to logout was moved to an own module. Please use BackendUtility::getModuleUrl(\'logout\') to link to logout.php.'
                );

                $logoutController = GeneralUtility::makeInstance(Controller\LogoutController::class);
                $logoutController->logout();
                // do the redirect
                $redirect = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('redirect'));
                $redirectUrl = $redirect ?: 'index.php';
                \TYPO3\CMS\Core\Utility\HttpUtility::redirect($redirectUrl);
            break;

            case 'mod.php':
                GeneralUtility::deprecationLog(
                    'The entry point to mod.php was moved to index.php with "M" given. Please use BackendUtility::getModuleUrl(\'myModuleKey\') to link to a module.'
                );
            break;

            case 'move_el.php':
                GeneralUtility::deprecationLog(
                    'Moving an element is moved to an own module. Please use BackendUtility::getModuleUrl(\'move_element\') to link to move_el.php.'
                );

                $moveElementController = GeneralUtility::makeInstance(Controller\ContentElement\MoveElementController::class);
                $moveElementController->main();
                $moveElementController->printContent();
            break;

            case 'show_item.php':
                GeneralUtility::deprecationLog(
                    'The entry point to show_item was moved to an own module. Please use BackendUtility::getModuleUrl(\'show_item\') to link to show_item.php.'
                );

                $elementInformationController = GeneralUtility::makeInstance(Controller\ContentElement\ElementInformationController::class);
                $elementInformationController->main();
                $elementInformationController->printContent();
            break;

            case 'tce_db.php':
                GeneralUtility::deprecationLog(
                    'The entry point to data handling via DataHandler was moved to an own module. Please use BackendUtility::getModuleUrl(\'tce_db\') to link to tce_db.php / DataHandler.'
                );

                $simpleDataHandlerController = GeneralUtility::makeInstance(Controller\SimpleDataHandlerController::class);

                $formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
                if ($formProtection->validateToken(GeneralUtility::_GP('formToken'), 'tceAction')) {
                    $simpleDataHandlerController->initClipboard();
                    $simpleDataHandlerController->main();
                }
                $simpleDataHandlerController->finish();
            break;

            case 'tce_file.php':
                GeneralUtility::deprecationLog(
                    'File handling entry point was moved an own module. Please use BackendUtility::getModuleUrl(\'tce_file\') to link to tce_file.php.'
                );

                $fileController = GeneralUtility::makeInstance(Controller\File\FileController::class);

                $formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
                if ($formProtection->validateToken(GeneralUtility::_GP('formToken'), 'tceAction')) {
                    $fileController->main();
                }

                $fileController->finish();
            break;

            case 'thumbs.php':
                GeneralUtility::deprecationLog(
                    'thumbs.php is no longer in use, please use the corresponding Resource objects to generate a preview functionality for thumbnails.'
                );
                $GLOBALS['SOBE'] = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\View\ThumbnailView::class);
                $GLOBALS['SOBE']->init();
                $GLOBALS['SOBE']->main();
            break;

            default:
                throw new \RuntimeException('You cannot call this script directly.');
        }
    });
});

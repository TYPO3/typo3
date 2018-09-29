.. include:: ../../Includes.txt

====================================================
Deprecation: #86198 - Protected RecordListController
====================================================

See :issue:`86198`

Description
===========

The following properties changed their visibility from public to protected and should not be called any longer:

* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->id`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->pointer`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->table`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->search_field`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->search_levels`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->showLimit`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->returnUrl`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->clear_cache`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->cmd`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->cmd_table`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->perms_clause`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->pageinfo`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->MOD_MENU`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->content`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->body`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->imagemode`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->doc`

The following methods changed their visibility from public to protected and should not be called any longer:

* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->init()`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->menuConfig()`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->clearCache()`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->main()`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->getModuleTemplate()`

Additionally, the two hooks :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawHeaderHook']`
and :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawFooterHook']` changed their signature:
The second argument, an instance of the parent object :php:`RecordListController` will be dropped in TYPO3 v10. Use the instance of the PSR-7
:php:`ServerRequestInterface` is provided as array key :php:`request` of the first argument.

Furthermore, the assignment of an object instance of class :php:`RecordListController` as
:php:`GLOBALS['SOBE']` has been marked as deprecated and will not be set anymore in TYPO3 v10.


Impact
======

Calling one of the above methods or accessing above properties triggers a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Instances are usually only affected if an extension registers a hook for
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawHeaderHook']` or
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawFooterHook']`. They will
work as before in TYPO3 v9, but using a property or calling a method of the provided parent object triggers a PHP :php:`E_USER_DEPRECATED` error.


Migration
=========

Hooks registered should change their parent object usage and signature. An example can be found in the `sys_notes` extension
in class :php:`TYPO3\CMS\SysNote\Hook\RecordListHook`.

Code before::

    /**
     * Add sys_notes as additional content to the header of the list module
     *
     * @param array $params
     * @param RecordListController $parentObject
     * @return string
     */
    public function renderInHeader(array $params = [], RecordListController $parentObject)
    {
        $controller = GeneralUtility::makeInstance(NoteController::class);
        return $controller->listAction($parentObject->id, SysNoteRepository::SYS_NOTE_POSITION_TOP);
    }

Adapted hook usage::

    /**
     * Add sys_notes as additional content to the header of the list module
     *
     * @param array $params
     * @return string
     */
    public function renderInHeader(array $params): string
    {
        /** @var ServerRequestInterface $request */
        $request = $params['request'];
        $id = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);
        $controller = GeneralUtility::makeInstance(NoteController::class);
        return $controller->listAction($id, SysNoteRepository::SYS_NOTE_POSITION_TOP);
    }


.. index:: Backend, PHP-API, NotScanned, ext:recordlist

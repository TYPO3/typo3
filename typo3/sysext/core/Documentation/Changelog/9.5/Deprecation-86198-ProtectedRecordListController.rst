.. include:: /Includes.rst.txt

====================================================
Deprecation: #86198 - Protected RecordListController
====================================================

See :issue:`86198`

Description
===========

The following properties of class :php:`TYPO3\CMS\Recordlist\Controller\RecordListController` changed their visibility from public
to protected and should not be called any longer:

* :php:`id`
* :php:`pointer`
* :php:`table`
* :php:`search_field`
* :php:`search_levels`
* :php:`showLimit`
* :php:`returnUrl`
* :php:`clear_cache`
* :php:`cmd`
* :php:`cmd_table`
* :php:`perms_clause`
* :php:`pageinfo`
* :php:`MOD_MENU`
* :php:`content`
* :php:`body`
* :php:`imagemode`
* :php:`doc`

The following methods of class :php:`TYPO3\CMS\Recordlist\Controller\RecordListController` changed their visibility from public
to protected and should not be called any longer:

* :php:`init()`
* :php:`menuConfig()`
* :php:`clearCache()`
* :php:`main()`
* :php:`getModuleTemplate()`

Additionally, the two hooks

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawHeaderHook']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawFooterHook']`

changed their signature:
The second argument, an instance of the parent object :php:`RecordListController` will be removed in TYPO3 v10. Use the instance of the PSR-7
:php:`ServerRequestInterface` that is provided as array key :php:`request` of the first argument.

Furthermore, the assignment of an object instance of class :php:`RecordListController` as
:php:`GLOBALS['SOBE']` has been marked as deprecated and will not be set anymore in TYPO3 v10.


Impact
======

Calling one of the above methods or accessing above properties will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Instances are usually only affected if an extension registers a hook for
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawHeaderHook']` or
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawFooterHook']`. They will
work as before in TYPO3 v9, but using a property or calling a method of the provided parent object will trigger a PHP :php:`E_USER_DEPRECATED` error.


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

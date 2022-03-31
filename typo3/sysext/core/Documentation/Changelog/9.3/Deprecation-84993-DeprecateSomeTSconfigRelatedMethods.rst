.. include:: /Includes.rst.txt

=============================================================
Deprecation: #84993 - Deprecate some TSconfig related methods
=============================================================

See :issue:`84993`

Description
===========

Some user TSconfig related methods have been deprecated:

* :php:`TYPO3\CMS\core\Authentication\BackendUserAuthentication->getTSConfigVal()`
* :php:`TYPO3\CMS\core\Authentication\BackendUserAuthentication->getTSConfigProp()`

Changed method signatures:

* :php:`TYPO3\CMS\core\Authentication\BackendUserAuthentication->getTSConfig()`, no argument allowed any longer

Some page TSconfig related methods have been marked as deprecated:

* :php:`TYPO3\CMS\backend\Utility\BackendUtility::getModTSconfig()`
* :php:`TYPO3\CMS\backend\Utility\BackendUtility::unsetMenuItems()`
* :php:`TYPO3\CMS\backend\Tree\View\PagePositionMap->getModConfig()`
* :php:`TYPO3\CMS\core\DataHandling\DataHandler->getTCEMAIN_TSconfig()`

These properties have been set to protected, should not be used any longer and trigger a deprecation error on access:

* :php:`TYPO3\CMS\backend\Tree\View\PagePositionMap->getModConfigCache`
* :php:`TYPO3\CMS\backend\Tree\View\PagePositionMap->modConfigStr`


Impact
======

Calling the above methods will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Extensions with backend modules may call these methods. The extension scanner
will find affected code occurrences in extensions.


Migration
=========

Change user TSconfig related calls to use :php:`BackendUserAuthentication->getTSConfig()`
instead, it comes with a slightly changed return syntax.

:php:`getTSConfig()` without arguments simply returns the entire user TSconfig as array, similar to other
methods that return parsed TypoScript arrays. The examples below show some imaginary user TSConfig,
the full parsed array returned by :php:`getTSConfig()` and some typical access patterns with fallback. Note
it's almost always useful to use the null coalescence :php:`??` operator for a fallback value to suppress
PHP notice level warnings::

    // Incoming user TSconfig:
    // options.someToggle = 1
    // options.somePartWithSubToggles = foo
    // options.somePartWithSubToggles.aValue = bar

    // Parsed array returned by getTSConfig(), note the dot if a property has sub keys:
    // [
    //     'options.' => [
    //         'someToggle' => '1',
    //         'somePartWithSubToggles' => 'foo',
    //         'somePartWithSubToggles.' => [
    //             'aValue' => 'bar',
    //         ],
    //     ],
    // ],
    $userTsConfig = $backendUserAuthentication->getTSConfig():

    // Typical call to retrieve a sanitized value:
    $isToggleEnabled = (bool)($userTsConfig['options.']['someToggle'] ?? false);

    // And to retrieve a sub set, note the dot at the end:
    $subArray = $userTsConfig['options.']['somePartWithSubToggles.'] ?? [];

    // Switch an old getTSConfigVal() to getTSConfig(), note the parenthesis:
    $value = (bool)$backendUser->getTSConfigVal('options.someToggle');
    $value = (bool)($backendUser->getTSConfig()['options.']['someToggle'] ?? false);

    // Switch an old getTSConfigProp() to getTSConfig(), note the parenthesis and the trailing dot:
    $value = (array)$backendUser->getTSConfigProp('options.somePartWithSubToggles');
    $value = (array)($backendUser->getTSConfig()['options.']['somePartWithSubToggles.'] ?? []);


Change :php:`BackendUtility->getModTSconfig()` related calls to use :php:`BackendUtility::getPagesTSconfig($pid)` instead.
Note this method does not return the 'properties' and 'value' sub array as :php:`->getModTSconfig()` did::

    // Switch an old getModTSconfig() to getPagesTSConfig() for array of properties:
    $configArray = BackendUtility::getModTSconfig($pid, 'mod.web_list');
    $configArray['properties'] = BackendUtility::getPagesTSconfig($pid)['mod.']['web_list.'] ?? [];

    // Switch an old getModTSconfig() to getPagesTSConfig() for single value:
    $configArray = BackendUtility::getModTSconfig($pid, 'TCEFORM.sys_dmail_group.select_categories.PAGE_TSCONFIG_IDLIST');
    $configArray['value'] = BackendUtility::getPagesTSconfig($pid)['TCEFORM.']['sys_dmail_group.']['select_categories.']['PAGE_TSCONFIG_IDLIST'] ?? null;

Methods :php:`BackendUtility::unsetMenuItems()` and :php:`DataHandler->getTCEMAIN_TSconfig()` have been rarely used
and are dropped without substitution. Copy the code into consuming methods if you really need them.

.. index:: Backend, PHP-API, TSConfig, FullyScanned

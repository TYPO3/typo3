.. include:: ../../Includes.txt

=============================================================
Deprecation: #84993 - Deprecate some TSconfig related methods
=============================================================

See :issue:`84993`

Description
===========

Some user TSconfig related methods have been deprecated:

* :php:`TYPO3\CMS\core\Authentication\BackendUserAuthentication->getTSConfigVal()`
* :php:`TYPO3\CMS\core\Authentication\BackendUserAuthentication->getTSConfigProp()`


Impact
======

Calling the above methods logs a deprecation message.


Affected Installations
======================

Extensions with backend modules may call these methods. The extension scanner
will find affected code occurrences in extensions.


Migration
=========

Change the calls to use :php:`BackendUserAuthentication->getTSConfig()` instead, it
comes with a slightly changed return syntax.

:php:`getTSConfig()` without arguments simply returns the entire user TSconfig as array, similar to other
methods that return parsed TypoScript arrays. The examples below show some imaginary user TSConfig TypoScript,
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
    $value = (bool)($backendUser->getTSConfig()['options.']['someToggle] ?? false);

    // Switch an old getTSConfigProp() to getTSConfig(), note the parenthesis and the trailing dot:
    $value = (array)$backendUser->getTSConfigProp('options.somePartWithSubToggles');
    $value = (array)($backendUser->getTSConfig()['options.']['somePartWithSubToggles.'] ?? []);

.. index:: Backend, PHP-API, TSConfig, FullyScanned
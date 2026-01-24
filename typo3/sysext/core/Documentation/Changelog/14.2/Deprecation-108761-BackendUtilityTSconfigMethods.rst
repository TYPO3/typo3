..  include:: /Includes.rst.txt

..  _deprecation-108761-1769281290:

==============================================================
Deprecation: #108761 - BackendUtility TSconfig-related methods
==============================================================

See :issue:`108761`

Description
===========

The following methods in :php:`\TYPO3\CMS\Backend\Utility\BackendUtility` have
been deprecated:

* :php:`getTCEFORM_TSconfig()`
* :php:`getTSCpidCached()`
* :php:`getTSCpid()`

A new method :php:`BackendUtility::getRealPageId()` has been introduced that
returns the real page ID for a given record. Unlike the previous methods that
returned arrays with multiple values or used internal caching, this method
provides a cleaner API that returns either the page ID as an integer or
:php:`null` if the page cannot be determined.

Impact
======

Calling any of the deprecated methods will trigger a deprecation-level log
entry. The methods will be removed in TYPO3 v15.0.

The extension scanner reports usages as a **strong** match.

Affected installations
======================

Instances or extensions that directly call any of the deprecated methods are
affected.

Migration
=========

getTCEFORM_TSconfig()
---------------------

This method has been moved to :php:`FormEngineUtility`. If you need TSconfig
for TCEFORM, it is recommended to rely on FormEngine data providers instead.

getTSCpidCached() and getTSCpid()
---------------------------------

These methods returned an array with two values: the TSconfig PID and the
real PID. The new :php:`getRealPageId()` method returns only the real page ID.

Before
~~~~~~

..  code-block:: php

    // getTSCpidCached returned [$tscPid, $realPid]
    [$tscPid, $realPid] = BackendUtility::getTSCpidCached($table, $uid, $pid);

    // getTSCpid returned the same structure
    [$tscPid, $realPid] = BackendUtility::getTSCpid($table, $uid, $pid);

After
~~~~~

..  code-block:: php

    // getRealPageId() returns int|null
    $pageId = BackendUtility::getRealPageId($table, $uid, $pid);

    // If you need to ensure an integer (null becomes 0)
    $pageId = (int)BackendUtility::getRealPageId($table, $uid, $pid);

..  index:: PHP-API, FullyScanned, ext:backend

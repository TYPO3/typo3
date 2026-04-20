..  include:: /Includes.rst.txt

..  _deprecation-108568-1734962478:

===========================================================================================
Deprecation: #108568 - BackendUserAuthentication::recordEditAccessInternals() and $errorMsg
===========================================================================================

See :issue:`108568`

Description
===========

The method :php:`BackendUserAuthentication::recordEditAccessInternals()`
and the property :php:`BackendUserAuthentication::$errorMsg` of class
:php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication`
have been deprecated.

They represented an anti-pattern in which the method returned
a boolean value but communicated error details through a class property, making
the API difficult to use and test.

A new method :php:`checkRecordEditAccess()` has been introduced. It returns
an :php:`\TYPO3\CMS\Core\Authentication\AccessCheckResult` value object
containing both the access decision and any error messages.

Impact
======

Calling the deprecated method :php:`recordEditAccessInternals()` or accessing
the deprecated property :php:`$errorMsg` will trigger a deprecation-level log
entry and will stop working in TYPO3 v15.0.

The extension scanner reports usages as a **strong** match.

Affected installations
======================

Instances or extensions that directly call
:php:`recordEditAccessInternals()` or access the
:php:`$errorMsg` property.

Migration
=========

Replace calls to :php:`recordEditAccessInternals()` with
:php:`checkRecordEditAccess()`. The new method returns a
:php:`TYPO3\CMS\Core\Authentication\AccessCheckResult` object with two public
properties:

* :php:`isAllowed` - Boolean indicating whether access is granted
* :php:`errorMessage` - String containing the error message. It is empty if
  access is granted

Before
------

..  code-block:: php

    use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

    $backendUser = $this->getBackendUser();
    if ($backendUser->recordEditAccessInternals($table, $record)) {
        // Access granted
    } else {
        // Access denied, error message is in $backendUser->errorMsg
        $errorMessage = $backendUser->errorMsg;
    }

After
-----

..  code-block:: php

    use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

    $backendUser = $this->getBackendUser();
    $accessResult = $backendUser->checkRecordEditAccess($table, $record);
    if ($accessResult->isAllowed) {
        // Access granted
    } else {
        // Access denied
        $errorMessage = $accessResult->errorMessage;
    }

..  index:: PHP-API, FullyScanned, ext:core

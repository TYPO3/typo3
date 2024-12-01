..  include:: /Includes.rst.txt

..  _breaking-105733-1733018161:

=====================================================================================
Breaking: #105733 - FileNameValidator no longer accepts custom regex in __construct()
=====================================================================================

See :issue:`105733`

Description
===========

Class :php:`TYPO3\CMS\Core\Resource\Security\FileNameValidator` does not handle
a custom file deny pattern in :php:`__construct()` anymore. The service is now
stateless and can be injected without side effects.


Impact
======

A custom partial regex as first constructor argument when instantiating the
service is ignored. The service relies on :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern']`
configuration, and a hard coded constant as fallback.


Affected installations
======================

Instances with custom extensions using
:php:`GeneralUtility::makeInstance(FileNameValidator::class, 'some-custom-pattern');`
are affected. This is most likely a very rare case.


Migration
=========

Extensions that need to test with custom patterns that can not be declared
globally using :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern']`
should probably switch to an own service implementing the test, or inline
the code. The main worker code of the service is just four lines of code.

..  index:: PHP-API, NotScanned, ext:core

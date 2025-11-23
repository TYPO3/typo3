..  include:: /Includes.rst.txt

..  _breaking-105733-1733018161:

=====================================================================================
Breaking: #105733 - FileNameValidator no longer accepts custom regex in __construct()
=====================================================================================

See :issue:`105733`

Description
===========

The class :php-short:`\TYPO3\CMS\Core\Resource\Security\FileNameValidator` no
longer accepts a custom file deny pattern in :php:`__construct()`. The service
is now stateless and can be injected without side effects.

Impact
======

A custom partial regex passed as the first constructor argument when
instantiating the service is now ignored. The service relies on
:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern']` configuration and a
hard-coded constant as a fallback.

Affected installations
======================

Instances with custom extensions using
:php:`GeneralUtility::makeInstance(FileNameValidator::class, 'some-custom-pattern');`
are affected. This is expected to be a very rare case.

Migration
=========

Extensions that need to be tested with custom patterns that cannot be declared
globally using :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern']`
should implement their own service for this purpose or inline the necessary
code. The core implementation performing the check is only a few lines long.

..  index:: PHP-API, NotScanned, ext:core

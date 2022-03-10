.. include:: /Includes.rst.txt

.. _important-97111-1657214951:

======================================
Important: #97111 - Default URI scheme
======================================

See :issue:`97111`

Description
===========

Several places in the TYPO3 core fall back to using `http` as a protocol for
links in case none was given. In order to adjust this behavior the new
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultScheme']` setting has been
introduced, which uses `http` as default.

In order to adjust the default protocol, one has to add the following
assignment to their :file:`typo3conf/LocalConfiguration.php` settings:

:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultScheme'] = 'https'`

.. index:: LocalConfiguration, RTE, ext:core

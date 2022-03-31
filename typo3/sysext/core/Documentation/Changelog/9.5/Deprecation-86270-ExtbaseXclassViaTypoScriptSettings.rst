.. include:: /Includes.rst.txt

==============================================================================
Deprecation: #86270 - config.tx_extbase.objects and plugin.tx_%plugin%.objects
==============================================================================

See :issue:`86270`

Description
===========

The :typoscript:`config.tx_extbase.objects` and :typoscript:`plugin.tx_%plugin%.objects` configuration options
have been marked as deprecated.


Impact
======

Configuring class overrides using :typoscript:`config.tx_extbase.objects` or :typoscript:`plugin.tx_%plugin%.objects`
will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All installations that make use of the TypoScript :typoscript:`config.tx_extbase.objects` or :typoscript:`plugin.tx_%plugin%.objects`
configuration options are affected.


Migration
=========

Use XCLASSes configured in :file:`ext_localconf.php` using
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']`.

Register implementations in the Extbase object container instead if you need to override classes
that are processed by the :php:`PropertyMapper` like domain models or if you rely on additional
injections:

.. code-block:: php

   GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class)
       ->registerImplementation(Base::class, Override::class);

If you conditionally configured :typoscript:`config.tx_extbase.objects` or
:typoscript:`plugin.tx_%plugin%.objects`, then move that conditional logic into the
overriding class itself.

.. index:: TypoScript, NotScanned, ext:extbase

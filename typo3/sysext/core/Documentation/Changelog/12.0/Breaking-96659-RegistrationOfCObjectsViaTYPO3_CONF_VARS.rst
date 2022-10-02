.. include:: /Includes.rst.txt

.. _breaking-96659:

===============================================================
Breaking: #96659 - Registration of cObjects via TYPO3_CONF_VARS
===============================================================

See :issue:`96659`

Description
===========

Since TYPO3 v12.0. custom Content Objects such as `TEXT` or `HMENU`
are registered via the service configuration.

The previous way of registering custom Content Objects via
:php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']`
added in TYPO3 v7.2 (see :issue:`64386`) has been removed.

Impact
======

TYPO3 installations using the previous way of registering custom or overridden
Content Objects will not return the rendered frontend output for this specific
Content Object anymore, which is a very rare case.

Affected Installations
======================

TYPO3 installations with extensions registering custom Content Objects.

Migration
=========

Extensions registering custom Content Objects should now use the service
configuration:

..  code-block:: yaml

    MyCompany\MyPackage\ContentObject\CustomContentObject:
        tags:
            - name: frontend.contentobject
              identifier: 'MY_OBJ'

Extensions can be made compatible with TYPO3 v7 - v12 by keeping the "old"
way of registration in :file:`ext_localconf.php` and additionally add the new
registration way, without any further changes.

.. index:: Frontend, TypoScript, FullyScanned, ext:frontend

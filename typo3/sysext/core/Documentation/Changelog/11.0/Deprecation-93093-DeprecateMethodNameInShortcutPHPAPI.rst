.. include:: /Includes.rst.txt

.. _changelog-Deprecation-93093-DeprecateMethodNameInShortcutPHPAPI:

====================================================
Deprecation: #93093 - MethodName in Shortcut PHP API
====================================================

See :issue:`93093`

Description
===========

Since :issue:`92723` the TYPO3 backend uses symfony routing for resolving
internal endpoints, e.g. modules. This will allow human readable urls and also
deep-linking in the future. To achieve this, the shortcut PHP API had to
be reworked to be fully compatible with the new routing.
See :ref:`changelog-Breaking-93093-ReworkShortcutPHPAPI` for more information
regarding the rework.

In the course of the rework, following methods within :php:`ShortcutButton`
have been marked as deprecated:

* :php:`TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton->setModuleName()`
* :php:`TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton->getModuleName()`

Impact
======

Using those methods directly or indirectly will trigger PHP :php:`E_USER_DEPRECATED` errors.


Affected Installations
======================

Installations with custom extensions, adding a shortcut button in the module
header of their backend modules using the mentioned methods. The extension
scanner will find all PHP usages as weak match.


Migration
=========

Use the new methods :php:`ShortcutButton->setRouteIdentifier()` and
:php:`ShortcutButton->getRouteIdentifier()` as replacement. Please note
that these methods require the route identifier of the backend module
which may differ from the module name. To find out the route identifier,
the "Backend Routes" section within the configuration module can be used.

Before:

.. code-block:: php

   $shortCutButton = $buttonBar
       ->makeShortcutButton()
       ->setModuleName('web_list');

After:

.. code-block:: php

   $shortCutButton = $buttonBar
       ->makeShortcutButton()
       ->setRouteIdentifier('web_list');

.. index:: Backend, PHP-API, FullyScanned, ext:backend

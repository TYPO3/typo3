.. include:: ../../Includes.txt

============================================================
Feature: #77589 - EXT: syntax in PageRenderer and Compressor
============================================================

See :issue:`77589`

Description
===========

It is now possible to use the `EXT:` prefix for referencing files inside extensions within the `PageRenderer` and `ResourceCompressor`
PHP classes for adding JavaSyntax or StyleSheet files.

So you can streamline your code from

.. code-block:: php

   $this->pageRenderer->addJsFile(ExtensionManagementUtility::extRelPath('core') . 'Resources/Public/JavaScript/Contrib/bootstrap/bootstrap.js');

to

.. code-block:: php

   $this->pageRenderer->addJsFile('EXT:core/Resources/Public/JavaScript/Contrib/bootstrap/bootstrap.js');

.. index:: PHP-API

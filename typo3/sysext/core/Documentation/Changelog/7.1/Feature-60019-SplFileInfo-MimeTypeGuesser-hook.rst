
.. include:: ../../Includes.txt

===============================================================================
Feature: #60019 - New SplFileInfo implementation with new mimeTypeGuessers hook
===============================================================================

See :issue:`60019`

Description
===========

A new class `\TYPO3\CMS\Core\Type\File\FileInfo` which extends `SplFileInfo` is now
available as an API for fetching meta information from files.

Besides the native .. _SplFileInfo API: https://php.net/manual/en/class.splfileinfo.php,
it provides a new method `getMimeType()` to get the mime type of a file, e.g. text/html.
It uses the native PHP function `finfo_file()` and `mime_content_type()` as a fallback.

Example: Get the MIME type of a file

.. code-block:: php

  $fileIdentifier = '/tmp/foo.html';
  $fileInfo = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Type\File\FileInfo::class, $fileIdentifier);
  echo $fileInfo->getMimeType();
  // text/html

New Hook 'mimeTypeGuessers'
===========================

Custom implementations to determine the MIME type can be added with a hook. Register the hook as follows:

.. code-block:: php

  $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Type\File\FileInfo::class]['mimeTypeGuessers']


.. index:: PHP-API, FAL

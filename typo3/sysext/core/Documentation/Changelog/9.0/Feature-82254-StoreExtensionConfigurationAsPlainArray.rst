.. include:: /Includes.rst.txt

==============================================================
Feature: #82254 - Store extension configuration as plain array
==============================================================

See :issue:`82254`

Description
===========

There is no reason to save the extension configuration as serialized values instead of
an plain array. Arrays are easier to handle and there are already parts of the core
using arrays (for example the avatar provider registration).

Therefore the API has been changed to store the extension configuration as an array
in :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']`. The configuration is merged on save
with the default configuration and the full configuration is written to LocalConfiguration.


Impact
======

Extension configuration can now be accessed as array directly, without
calling unserialize(). The old and new API will co-exist in version 9.

Use a new API to retrieve extension configuration, examples:

.. code-block:: php

    // Retrieve a single key
    $backendFavicon = (bool)GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('backend', 'backendFavicon');

    // Retrieve whole configuration
    $backendConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('backend');


.. index:: LocalConfiguration, PHP-API

.. include:: /Includes.rst.txt

.. _feature-82809:

=======================================================================================
Feature: #82809 - Make ExtensionUtility::registerPlugin method return plugin signature.
=======================================================================================

See :issue:`82809`

Description
===========

The API method :php:`TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin()`
is used to register an Extbase plugin. The method is called in the
:file:`Configuration/TCA/Overrides/tt_content.php` file of an extension and
often followed by the definition of a FlexForm.

Such methods require the plugin signature to be provided. To support
extension authors and to reduce coding errors, the :php:`registerPlugin()`
method therefore now returns the generated plugin signature as :php:`string`.

Example
^^^^^^^

..  code-block:: php

    $pluginSignature = ExtensionUtility::registerPlugin(
        'indexed_search',
        'Pi2',
        'Testing'
    );

The above call returns the plugin signature: `indexedsearch_pi2`. This could
then be used for, e.g., adding a FlexForm:

..  code-block:: php

    ExtensionManagementUtility::addPiFlexFormValue(
        $pluginSignature,
        'FILE:EXT:indexed_search/Configuration/FlexForms/Form.xml'
    );

Impact
======

The API method :php:`TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin()`
now returns the plugin signature, which might be used to adjust TCA to
further needs, e.g. enabling FlexForms.

.. index:: PHP-API, ext:extbase

..  include:: /Includes.rst.txt

..  _important-105538-1730752784:

===========================================================================================
Important: #105538 - Plugin subtypes removed: Changes to configurePlugin() and TCA handling
===========================================================================================

See :issue:`105538`

Description
===========

Due to the removal of the plugin content element "Plugin" (list) and the
corresponding plugin subtype field :sql:`list_type`, the fifth parameter
:php:`$pluginType` of :php:`ExtensionUtility::configurePlugin()` is now unused
and can be omitted. It is only kept for backwards compatibility.

Be aware that passing any value other than :sql:`CType` will trigger an
:php:`\InvalidArgumentException`.

Please also note that due to the removal of the :sql:`list_type` field in
:sql:`tt_content`, passing `list_type` as the second parameter :php:`$field`
to :php:`ExtensionManagementUtility::addTcaSelectItemGroup()` will now, as for
any other non-existent field, trigger a :php:`\RuntimeException`.

..  index:: PHP-API, TCA, ext:core

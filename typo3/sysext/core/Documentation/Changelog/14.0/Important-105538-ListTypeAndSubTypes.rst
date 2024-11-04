..  include:: /Includes.rst.txt

..  _important-105538-1730752784:

============================================
Important: #105538 - list_type and sub types
============================================

See :issue:`105538`

Description
===========

Due to the removal of the plugin content element (:php:`list`) and the corresponding
plugin subtype field :php:`list_type` the fifth parameter :php:`$pluginType`
of :php:`ExtensionUtility::configurePlugin()` is now unused and can be omitted.
It is only kept for backwards compatibility. However, be aware that passing any
value other than :php:`CType` will trigger a :php:`\InvalidArgumentException`.

Please also note that due to the removal of the :sql:`list_type` field in
:sql:`tt_content`, passing `list_type` as second parameter :php:`$field` to
:php:`ExtensionManagementUtility::addTcaSelectItemGroup()` will now - as for
any other non-existent field - trigger a :php:`\RuntimeException`.

..  index:: PHP-API, TCA, ext:core

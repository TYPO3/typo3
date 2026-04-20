..  include:: /Includes.rst.txt

..  _important-109147-1741107600:

========================================================================
Important: #109147 - Move link validator module to Link Management group
========================================================================

See :issue:`109147`

Description
===========

The link validator module (:guilabel:`Check Links`) has been moved from the
:guilabel:`Status / Info` module group (`content_status`) to the
:guilabel:`Link Management` module group (`link_management`), alongside
Redirects and QR Codes.

Since the :guilabel:`Link Management` parent module does not provide a page
tree navigation component, the link validator module now brings its own
`navigationComponent` to retain the page tree.

Additionally, the module identifier has been changed from
`web_linkvalidator` to `linkvalidator_checklinks`.

Impact
======

The link validator module now appears under :guilabel:`Link Management` in the
backend module menu instead of :guilabel:`Status / Info`.

An upgrade wizard ensures that backend users and groups with
`web_linkvalidator` permissions are migrated to `linkvalidator_checklinks`
and automatically receive access to the `link_management` parent module.

..  index:: Backend, ext:linkvalidator

..  include:: /Includes.rst.txt

..  _breaking-106949-1750763567:

=======================================================================
Breaking: #106949 - Duplicate doktype restriction configuration removed
=======================================================================

See :issue:`106949`

Description
===========

The TSconfig option :tsconfig:`mod.web_list.noViewWithDokTypes` has been
removed, as it duplicated the existing configuration
:tsconfig:`TCEMAIN.preview.disableButtonForDokType`.

Since :issue:`96861`, the latter has been established as the single source of
truth for disabling the *View* button for specific :sql:`doktype` values.

Impact
======

The option :tsconfig:`mod.web_list.noViewWithDokTypes` is no longer evaluated.
Only the configuration :tsconfig:`TCEMAIN.preview.disableButtonForDokType`
is now respected.

Affected installations
======================

TYPO3 installations that still rely on
:tsconfig:`mod.web_list.noViewWithDokTypes` in Page TSconfig to control the
visibility of the *View* button in backend modules are affected.

Migration
=========

Remove any usage of :tsconfig:`mod.web_list.noViewWithDokTypes` from Page
TSconfig.

Use the existing configuration
:tsconfig:`TCEMAIN.preview.disableButtonForDokType` instead:

..  code-block:: typoscript
    :caption: EXT:site_package/Configuration/TSconfig/Page/TCEMAIN.tsconfig

    TCEMAIN.preview.disableButtonForDokType = 199, 254

This change ensures consistent behavior and avoids duplicate configuration.

..  index:: TSConfig, NotScanned, ext:backend

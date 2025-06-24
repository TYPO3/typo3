..  include:: /Includes.rst.txt

..  _breaking-106949-1750763567:

=======================================================================
Breaking: #106949 - Duplicate doktype restriction configuration removed
=======================================================================

See :issue:`106949`

Description
===========

The TSconfig option :typoscript:`mod.web_list.noViewWithDokTypes` has been
removed since it just duplicated the existing configuration
:typoscript:`TCEMAIN.preview.disableButtonForDokType`, which has been
established as the single source of truth for disabling the "View" button for
certain `doktype` values since :issue:`96861`.


Impact
======

Installations where :typoscript:`mod.web_list.noViewWithDokTypes` is still used
in TSconfig, the configuration will no longer have any effect. Only the
configuration :typoscript:`TCEMAIN.preview.disableButtonForDokType` will be
respected.


Affected installations
======================

TYPO3 installations that still rely on :typoscript:`mod.web_list.noViewWithDokTypes`
in Page TSconfig to control the visibility of the "View" button in the various
backend modules.


Migration
=========

Remove any usage of :typoscript:`mod.web_list.noViewWithDokTypes` from Page
TSconfig.

Instead, configure the equivalent behavior using:

.. code-block:: typoscript
   :caption: EXT:site_package/Configuration/TSconfig/Page/TCEMAIN.tsconfig

   TCEMAIN.preview.disableButtonForDokType = 199, 254

This change ensures consistent behavior and avoids duplicate configuration.

..  index:: TSConfig, NotScanned, ext:backend

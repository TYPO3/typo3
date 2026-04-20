..  include:: /Includes.rst.txt

..  _feature-108831-1738522284:

================================================================
Feature: #108831 - Extend workspace preview links to all records
================================================================

See :issue:`108831`

Description
===========

The :guilabel:`Workspaces` module previously generated shareable preview links
(with `ADMCMD_prev` token) for pages only. This change extends the functionality
to support any record type.

The preview page for non-page records is determined via existing user TSconfig
options:

*   :typoscript:`options.workspaces.previewPageId.<table>`
*   :typoscript:`TCEMAIN.preview.<table>.previewPageId`

Additional query parameters can be configured via:

*   :typoscript:`TCEMAIN.preview.<table>.fieldToParameterMap`
*   :typoscript:`TCEMAIN.preview.<table>.additionalGetParameters`

Example configuration for a custom record type:

..  code-block:: typoscript

    TCEMAIN.preview.tx_myext_domain_model_item {
        previewPageId = 42
        fieldToParameterMap {
            uid = tx_myext_pi1[item]
        }
        additionalGetParameters {
            type = 9818
        }
    }

Impact
======

The QR code and shareable link button in the :guilabel:`Workspaces` module now
work for all record types that have a preview configuration. This allows editors
to share workspace previews of custom records with colleagues or clients without
requiring them to have a backend login.

..  index:: Backend, TSConfig, ext:workspaces

.. include:: /Includes.rst.txt

.. _important-96218-1733990267:

============================================================================
Important: #96218 - Use proper surrounding "html" tags for Fluid SystemEmail
============================================================================

See :issue:`96218`

Description
===========

Due to usage of :html:`data-namespace-typo3-fluid="true"` in the
:html:`<html>` declaration of the file
:file:`EXT:core/Resources/Private/Layouts/SystemEmail.html`,
the whole :html:`<html>..</html>` structure is removed from a sent
HTML mail.

Validation and possibly utilities like SpamAssassin may fail
or negatively score these mails due to these tags being missing.

Since the :html:`xmlns` declaration of the ViewHelpers is semantically
not wrong, it can actually be included in the email by removing
the :html:`data-namespace-typo3-fluid` attribute, instead of requiring
the alternate more intrusive Fluid ViewHelper declaration.

Affected installations
======================

All setups with customizations of the file
:file:`EXT:core/Resources/Private/Layouts/SystemEmail.html` for sending
FluidEmails.

Migration
=========

Adjust custom copies of the file :file:`EXT:core/Resources/Private/Layouts/SystemEmail.html`
like this:

..  code-block:: html
    :caption: Before (EXT:your_extension/Resources/Private/Layouts/SystemEmail.html)
    :emphasize-lines: 6

    <html xmlns="http://www.w3.org/1999/xhtml"
          xmlns:v="urn:schemas-microsoft-com:vml"
          xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
          xmlns:core="http://typo3.org/ns/TYPO3/CMS/Core/ViewHelpers"
          data-namespace-typo3-fluid="true">

into this:

..  code-block:: html
    :caption: After (EXT:your_extension/Resources/Private/Layouts/SystemEmail.html)
    :emphasize-lines: 5

    <html xmlns="http://www.w3.org/1999/xhtml"
          xmlns:v="urn:schemas-microsoft-com:vml"
          xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
          xmlns:core="http://typo3.org/ns/TYPO3/CMS/Core/ViewHelpers">

.. index:: Fluid, Frontend, ext:code, NotScanned

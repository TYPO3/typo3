.. include:: ../../Includes.txt

======================================================
Important: #75591 - Partials/Honeypot.html has changed
======================================================

See :issue:`75591`

Description
===========

The partial :file:`EXT:form/Resources/Private/Frontend/Partials/Honeypot.html` has been changed. The
honeypot field now passes the accessibility tests WCAG 2.0 by adding an aria-hidden attribute.
All installations with the overwritten partial :file:`EXT:form/Resources/Private/Frontend/Partials/Honeypot.html`
are affected and should be migrated.

.. index:: Frontend, ext:form

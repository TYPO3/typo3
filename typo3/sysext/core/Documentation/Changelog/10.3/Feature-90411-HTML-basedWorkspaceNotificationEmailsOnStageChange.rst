.. include:: /Includes.rst.txt

==========================================================================
Feature: #90411 - HTML-based workspace notification emails on stage change
==========================================================================

See :issue:`90411`

Description
===========

When inside workspaces, it is possible to notify affected (or all)
users belonging to that workspace by sending out an email when
items have been moved to the next stage in the workflow process.

These emails have been limited in the past due to marker-based templating and plain-text only.

The emails have been reworked and migrated to Fluid-based templated
emails, allowing for administrators to customize the contents of
those emails.

The following TSconfig options have been added:

.. code-block:: typoscript

   # path where to look for templates / layouts / partials
   tx_workspaces.emails.layoutRootPaths.100 = EXT:myproject/...
   tx_workspaces.emails.partialRootPaths.100 = EXT:myproject/...
   tx_workspaces.emails.templateRootPaths.100 = EXT:myproject/...
   # valid formats are "text", "html" or "both"
   tx_workspaces.emails.format = html
   tx_workspaces.emails.senderEmail = workspaces@example.com
   tx_workspaces.emails.senderName = Your TYPO3 at Example.com

The template name is always called `StageChangeNotification`.

It is still possible to use the existing plain-text variant
by setting the format to "text" and using the previous email
contents, if applicable. It is however recommended to make use
of the Fluid-based variables to make output more efficient.

The old TSconfig options have been superseded for defining the template via XLF labels.


Impact
======

Stage Change Notification emails are now sent as HTML+text by
default with the email template given in :file:`EXT:workspaces/Resources/Private/Templates/Emails/StageChangeNotification`.

.. index:: TSConfig, ext:workspaces

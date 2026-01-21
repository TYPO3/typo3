..  include:: /Includes.rst.txt

..  _feature-108720-1769035130:

======================================================
Feature: #108720 - QR code button for frontend preview
======================================================

See :issue:`108720`

Description
===========

A new QR code button has been added next to the "View" button in various backend
modules. Clicking the button opens a modal displaying a scannable QR code for
the frontend preview URI.

The button is available in the following locations:

*   Page module (Layout view and Language Comparison view)
*   List module
*   Preview module
*   Workspaces module

When working in a workspace, the QR code contains a special preview URI that
works without backend authentication. This makes it easy to share workspace
previews with colleagues or clients, or to quickly check a draft version on a
mobile device by simply scanning the code.

The QR code can be downloaded as PNG or SVG directly from the modal.

Impact
======

Editors benefit from a streamlined workflow when sharing page previews or
testing pages on mobile devices. The workspace-aware preview URIs eliminate the
need to be logged in when scanning the QR code, making it particularly useful
for review processes involving external stakeholders.

..  index:: Backend, ext:backend, ext:workspaces

..  include:: /Includes.rst.txt

..  _feature-108720-1769035130:

=====================================================
Feature: #108720 - QR code button for frontend preview
=====================================================

See :issue:`108720`

Description
===========

A new QR code button has been added next to the :guilabel:`View` button in various backend
modules. Clicking the button opens a modal displaying a scannable QR code for
the frontend preview URI.

The button is available in the following locations:

*   :guilabel:`Content > Web` module (Layout view and Language Comparison view)
*   :guilabel:`Web > List` module
*   :guilabel:`Web > View` module
*   :guilabel:`Web > Workspaces` module

Inside a workspace a QR code contains a special preview URI that will
work without backend authentication. This makes it easy to share workspace
previews with colleagues and clients, or to quickly check draft versions on a
mobile device by scanning the code.

The QR code can be downloaded as PNG or SVG from the modal.

Impact
======

Editors can benefit from a streamlined workflow by sharing page previews and
testing pages on mobile devices. Workspace-aware preview URIs eliminate the
need to be logged in when scanning the QR code, making it particularly useful
for reviewing processes involving external stakeholders.

..  index:: Backend, ext:backend, ext:workspaces

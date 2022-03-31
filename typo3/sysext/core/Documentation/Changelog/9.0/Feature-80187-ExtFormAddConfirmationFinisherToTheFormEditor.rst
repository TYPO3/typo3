.. include:: /Includes.rst.txt

====================================================================
Feature: #80187 - Add the "Confirmation" finisher to the form editor
====================================================================

See :issue:`80187`

Description
===========

The "Confirmation Message" finisher is now editable through the form editor.
The "Confirmation Message" finisher is now able to render a content element as message.
The option "contentElementUid" can be used to render a content element:

.. code-block:: typoscript

    finishers:
      -
        identifier: Confirmation
        options:
          contentElementUid: 765

If contentElementUid is set, the option "message" will be ignored.

The option "typoscriptObjectPath" can be used to render the content element
through a typoscript lib (default: 'lib.tx_form.contentElementRendering')

.. code-block:: typoscript

    finishers:
      -
        identifier: Confirmation
        options:
          contentElementUid: 765
          typoscriptObjectPath: 'lib.tx_myext.customContentElementRendering'

Impact
======

The "Confirmation Message" finisher is now editable through the form editor.
The "Confirmation Message" finisher is now able to render a content element as message.

.. index:: Backend, Frontend, ext:form

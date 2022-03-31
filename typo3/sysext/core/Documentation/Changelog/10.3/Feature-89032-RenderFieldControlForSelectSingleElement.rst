.. include:: /Includes.rst.txt

=============================================================
Feature: #89032 - Render fieldControl for SelectSingleElement
=============================================================

See :issue:`89032`

Description
===========

The missing rendering for the :html:`fieldControl` option for SelectSingleElements was added.


Impact
======

It is now possible to use the :html:`fieldControl` option for SelectSingleElements
to add nodes and wizards.

For example, add a link popup button to a select called "field_name" of the pages table:

.. code-block:: php

   $GLOBALS['TCA']['pages']['columns']['field_name']['config']['fieldControl']['linkPopup'] = [
    'renderType' => 'linkPopup',
   ];


.. index:: Backend, TCA, ext:backend

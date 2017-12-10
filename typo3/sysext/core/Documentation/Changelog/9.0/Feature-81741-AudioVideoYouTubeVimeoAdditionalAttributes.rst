.. include:: ../../Includes.txt

===============================================================================================
Feature: #81741 - Render additional and data-* attributes in media renderer for MediaViewHelper
===============================================================================================

See :issue:`81741`

Description
===========

Render :html:`additionalAttributes` and :html:`data-*` attributes in Audio-, VideoTag, YouTube, Vimeo and
if set in the Fluid MediaViewHelper.

Basic Usage
===========

.. code-block:: html

   <f:media additionalAttributes="{parameter:'argument'}" data="{src:'test'}"/>

Example Output
==============

.. code-block:: html

   <video controls data-src="test" parameter="argument">[...]</video>

.. index:: Fluid, Frontend

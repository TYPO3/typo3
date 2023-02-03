.. include:: /Includes.rst.txt

.. _feature-99285-1670321970:

==========================================
Feature: #99285 - Add Fluid TrimViewHelper
==========================================

See :issue:`99285`

Description
===========

A trim ViewHelper to trim strings is now available.

Possible sides are:

*   `both` Strip whitespace (or other characters) from the beginning and end of a string
*   `left` Strip whitespace (or other characters) from the beginning of a string
*   `right` Strip whitespace (or other characters) from the end of a string


Examples
--------

Trim from both sides
~~~~~~~~~~~~~~~~~~~~

..  code-block:: html

    #<f:format.trim>   String to be trimmed.   </f:format.trim>#

Results in the output:

..  code-block:: text

    #String to be trimmed.#

Trim only ony side
~~~~~~~~~~~~~~~~~~

..  code-block:: html

    #<f:format.trim side="right">   String to be trimmed.   </f:format.trim>#

Results in the output:

..  code-block:: text

    #   String to be trimmed.#

Trim special characters
~~~~~~~~~~~~~~~~~~~~~~~

..  code-block:: html

    #<f:format.trim characters=" St.">   String to be trimmed.   </f:format.trim>#

Results in the output:

..  code-block:: text

    #ring to be trimmed#


Impact
======

The new ViewHelper can be used in all new projects. There is no interference
with any part of existing code.

.. index:: Fluid, Frontend, ext:fluid

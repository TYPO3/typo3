.. include:: /Includes.rst.txt

=======================================================
Breaking: #78759 - Fluidification of EditFileController
=======================================================

See :issue:`78759`

Description
===========

While moving all HTML from PHP code to an own Fluid template the HTML string given to the hook after compiling the output is different now.


Impact
======

The HTML string given to the hook after compiling the output now contains the closing form tag :html:`</form>`.


Affected Installations
======================

All installations that append text to the HTML code in the hook after compiling the output.


Migration
=========

The hook code has to be changed to insert additional code before the closing form tag.

.. index:: Backend, Fluid

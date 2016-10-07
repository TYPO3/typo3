
.. include:: ../../Includes.txt

=======================================================================
Breaking: #75237 - Removal of div ce-bodytext might cause layout issues
=======================================================================

See :issue:`75237`

Description
===========

If neither bodytext nor header were entered in the TextMedia element, the div element containing the class ce-bodytext will be suppressed.


Impact
======

The missing div could cause layout problems, if the layout and CSS depend on it.


Affected Installations
======================

All installations relying on <div class="ce-bodytext">


Migration
=========

Either change the CSS or use a custom template without all the conditions.

.. index:: Fluid, ext:fluid_styled_content

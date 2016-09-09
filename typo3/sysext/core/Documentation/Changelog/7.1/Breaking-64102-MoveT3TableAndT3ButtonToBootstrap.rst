
.. include:: ../../Includes.txt

===========================================================
Breaking: #64102 - Move t3-table and t3-button to bootstrap
===========================================================

See :issue:`64102`

Description
===========

In transition to full bootstrap coverage and streamlining the backend, we are dropping the support for the CSS classes
.t3-table and .t3-button. We are replacing them with the corresponding bootstrap CSS classes for tables and buttons.
See http://getbootstrap.com/css/#tables and http://getbootstrap.com/css/#buttons for more details.


Impact
======

Custom implementations of tables and buttons in backend modules will lose the TYPO3 default styling.


Affected installations
======================

Extensions that provide custom backend modules that are using the css classes `.t3-table` or `.t3-button`.


Migration
=========

For tables we recommend the usage of the CSS class combination `table table-striped table-hover` instead of `t3-table`.

For buttons we recommend the usage of the CSS class combination `btn btn-default` instead of `t3-button`.

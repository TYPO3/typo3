
.. include:: /Includes.rst.txt

=========================================================
Important: #71521 - Internal changes in CommandController
=========================================================

See :issue:`71521`

Description
===========

The `CommandController::processRequest()` method has been changed to initialize arguments and output.

If this method was overridden without calling the parent method, these changes must be copied to prevent errors.

.. index:: PHP-API, ext:extbase

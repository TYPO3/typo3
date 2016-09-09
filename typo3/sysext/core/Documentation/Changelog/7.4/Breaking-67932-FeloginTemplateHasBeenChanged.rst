
.. include:: ../../Includes.txt

=======================================================================
Breaking: #67932 - felogin template has been changed for RSA encryption
=======================================================================

See :issue:`67932`

Description
===========

Due to the introduction of the new rsaauth API the felogin template has been changed.

A new HTML data-attribute had to be added to the password field in order to enable the RSA encryption Javascript code.


Impact
======

If rsaauth is enabled and the template is not updated, no login is possible.


Affected Installations
======================

Any installation using a custom felogin template and having rsaauth enabled for frontend.


Migration
=========

The template has to be adjusted and a `data-rsa-encryption` attribute has to be added to the password field in `<!--###TEMPLATE_LOGIN###-->`

The field definition in your template has to be updated to like this:

.. code-block:: html

	<input type="password" id="pass" name="pass" value="" data-rsa-encryption="" />

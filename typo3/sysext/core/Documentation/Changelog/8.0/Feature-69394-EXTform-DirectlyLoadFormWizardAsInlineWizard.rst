
.. include:: /Includes.rst.txt

=======================================================================
Feature: #69394 - EXT:form - Directly load form wizard as inline wizard
=======================================================================

See :issue:`69394`

Description
===========

The wizard of EXT:form is loaded directly as inline wizard. There is no need anymore
to save and reload the newly created content element in order to be able to open the
wizard. This is a huge usability improvement. Additionally there is no need to provide
individual doc headers. Instead, the centralized doc headers of the module template
API are used.

The whole integration utilizes the nodeRegistry of formEngine and registers the wizard
as new render type.

Furthermore, all JavaScript is loaded via require.js.

Since integrators and editors had massive problems with overridden form configuration
the wizard cannot be deactivated anymore. Instead, the integrator can configure whether
to load the form wizard by default or not. The following UserTS is integrated by default:

`setup.default.tx_form.showWizardByDefault = 1`

.. index:: Backend, ext:form

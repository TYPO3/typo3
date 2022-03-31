
.. include:: /Includes.rst.txt

=================================================================================
Feature: #58033 - Enable label override of checkbox and radio buttons by TSconfig
=================================================================================

See :issue:`58033`

Description
-----------

Use TSconfig to override labels of radio buttons and checkboxes used in FormEngine.

For single checkboxes the key `default` is used:

.. code-block:: typoscript

	TCEFORM.pages.hidden.altLabels.default = individual label

.. code-block:: typoscript

	TCEFORM.pages.hidden.altLabels.default = LLL:path/to/languagefile.xlf:individualLabel

For fields with multiple checkboxes, the value or the corresponding numeration (0,1,2,3) of the checkbox is used:

.. code-block:: typoscript

	TCEFORM.pages.l18n_cfg.altLabels.0 = individual label for the first checkbox
	TCEFORM.pages.l18n_cfg.altLabels.1 = individual label for the second checkbox

The same functionality works on radio buttons, where the "key" is the value of the radio button.

As seen in the example, hard-coded strings or references to language files are allowed.

Impact
------

The feature enables even more customization for FormEngine for any custom crafted backend instance.


.. index:: TSConfig, Backend

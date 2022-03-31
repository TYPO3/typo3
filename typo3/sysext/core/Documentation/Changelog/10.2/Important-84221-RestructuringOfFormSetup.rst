.. include:: /Includes.rst.txt

===============================================
Important: #84221 - Restructuring of form setup
===============================================

See :issue:`84221`

Description
===========

The setup of the "form" extension has been restructured. Till now the following files were used:

* :file:`BaseSetup.yaml`: setup shared in all contexts
* :file:`FormEditorSetup.yaml`: setup of the Form Editor backend module
* :file:`FormEngineSetup.yaml`: setup of the Form plugin flexForm configuration

From now on only a single file is used:

* :file:`FormSetup.yaml`: basic setup including imports of the configuration for validators, form
  elements and finishers.

All previously used inheritances and mixins have been resolved which makes it very easy to
understand the entire configuration.

Consequently the entries in :typoscript:`yamlConfigurations` have changed:

* :typoscript:`10` is now :file:`FormSetup.yaml`
* :typoscript:`20` and :typoscript:`30` have been dropped

Customizations of those entries must be adjusted accordingly.

.. index:: Backend, FlexForm, Frontend, TypoScript, ext:form

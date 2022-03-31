.. include:: /Includes.rst.txt

==========================================================
Important: #84420 - Properly escape reserved chars in YAML
==========================================================

See :issue:`84420`

Description
===========

If dealing with YAML files in the TYPO3 system - for instance to configure forms
using the `form` extension or if configuring `ckeditor` - integrators should properly
quote strings containing special characters like `@` or `%` to be upwards compatible
with the version 4 symfony YAML parser.

More information can be found in the Symfony_ docs.

.. _Symfony: http://symfony.com/doc/current/components/yaml/yaml_format.html#strings


.. index:: Backend, Frontend, ext:form, ext:rte_ckeditor

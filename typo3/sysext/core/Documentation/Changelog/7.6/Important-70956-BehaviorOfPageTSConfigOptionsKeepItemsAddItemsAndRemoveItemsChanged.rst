
.. include:: /Includes.rst.txt

=================================================================================================
Important: #70956 - Behavior of Page TSconfig options keepItems, addItems and removeItems changed
=================================================================================================

See :issue:`70956`

Description
===========

The behavior of Page TSconfig options `keepItems`, `addItems` and `removeItems`
has been restored to state of TYPO3 CMS 6.2-7.4 and the execution order of these
options has been formalized.

The first option to be evaluated is `keepItems`, followed in turn by `addItems`
and `removeItems`. All three options are evaluated after items have been added to
the configuration by sources like folders or foreign tables.

.. index:: TSConfig, Backend

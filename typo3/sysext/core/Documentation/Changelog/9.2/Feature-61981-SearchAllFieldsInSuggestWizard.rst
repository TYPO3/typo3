.. include:: /Includes.rst.txt

=====================================================
Feature: #61981 - Search all fields in Suggest Wizard
=====================================================

See :issue:`61981`

Description
===========

Suggest Wizard search terms are split by `+`.
This allows to search for a combination of strings in any given field.


Impact
======

Searching for the term "elements+basic" will find the following results:

* elements basic
* elements rte basic
* basic rte elements

.. index:: Backend, TCA

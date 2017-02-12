.. include:: ../../Includes.txt

==============================================
Important: #78899 - displayCond strict parsing
==============================================

See :issue:`78899`

Description
===========

The parser handling :code:`displayCond` in :code:`TCA` fields is now strict and throws exceptions if the
documented condition syntax is not valid or if referenced fields are not found. This should help
debugging faulty conditions a lot.

.. index:: Backend, FlexForm, TCA
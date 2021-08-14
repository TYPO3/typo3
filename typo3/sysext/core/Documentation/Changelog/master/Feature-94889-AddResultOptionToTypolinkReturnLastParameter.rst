.. include:: ../../Includes.txt

======================================================================
Feature: #94889 - Add "result" option to typolink returnLast parameter
======================================================================

See :issue:`94889`

Description
===========

This change introduces a new :php:`LinkResult` object along with an
interface, containing the base result of a generated link by TypoLink.

This object should contain all information needed to put together
an :html:`<a>` tag or return a URL in the future.

For the time being this new class is used to build links from
:php:`AbstractTypolinkBuilder` implementations, and in addition
should be able to be returned fully by :ts:`typolink` in the future.

In addition, this object helps to build links needed
for e.g. JSON responses to contain all information
of the link to be serialized.

Impact
======

This feature allows user to handle link's data in more consistent way, also
simplifies typolink handling in different outputs than HTML, like i.e. JSON

.. index:: PHP-API, TypoScript, ext:frontend

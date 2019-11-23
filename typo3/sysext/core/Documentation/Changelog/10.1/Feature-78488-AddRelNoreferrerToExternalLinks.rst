.. include:: ../../Includes.txt

========================================================
Feature: #78488 - Add rel="noreferrer" to external links
========================================================

See :issue:`78488`

Description
===========

All links processed by :ts:`typolink` with external links or using :html:`_blank`
have been extended to contain :html:`rel="noreferrer"`.


Impact
======

This property improves the security of the site:

:html:`noreferrer`
   This property prevents the browser, when navigating to another page, to send the page address, or any other value,
   as referrer in according HTTP header. :html:`noreferrer` also implies the property :html:`noopener`, which instructs
   the browser to open the link without granting the new browsing context access to the document that opened it.


.. index:: Frontend

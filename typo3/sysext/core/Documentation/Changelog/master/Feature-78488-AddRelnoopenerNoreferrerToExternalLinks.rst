.. include:: ../../Includes.txt

=================================================================
Feature: #78488 - Add rel="noopener noreferrer" to external links
=================================================================

See :issue:`78488`

Description
===========

All links processed by `TypoLink` with external links or using `_blank` have been extended to add `rel="noopener noreferrer"`.


Impact
======

Both properties improve the security of the site:

- `noopener`: This property instructs the browser to open the link without granting the new browsing context access to the document that opened it.
- `noreferrer`: This property prevents the browser, when navigating to another page, to send the page address, or any other value, as referrer via the Referer HTTP header.

.. index:: Frontend

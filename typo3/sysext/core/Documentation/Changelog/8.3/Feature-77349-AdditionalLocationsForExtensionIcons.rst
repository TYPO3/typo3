
.. include:: /Includes.rst.txt

==========================================================
Feature: #77349 - Additional locations for extension icons
==========================================================

See :issue:`77349`

Description
===========

Extensions can now hold their extension icons in additional locations to the existing ones (ext_icon.png, ext_icon.svg, ext_icon.gif):

- `Resources/Public/Icons/Extension.png`
- `Resources/Public/Icons/Extension.svg`
- `Resources/Public/Icons/Extension.gif`

This makes it possible to restrict access to more directories, thus hardening the TYPO3 instance.

.. index:: Backend

.. include:: /Includes.rst.txt

================================================================
Feature: #87200 - Send plaintext and HTML mails in EmailFinisher
================================================================

See :issue:`87200`

Description
===========

The :php:`EmailFinisher` of EXT:form now sends mails with a plaintext and an HTML part.
A new option :yaml:`addHtmlPart` has been added to configure if a HTML part should be added to mails.


Impact
======

Mails now contain both plaintext and HTML parts to support a wider audience.

Enforcing a plaintext-only mail increases reception and security.

.. index:: ext:form

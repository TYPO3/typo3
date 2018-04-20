.. include:: ../../Includes.txt

=============================================
Feature: #84798 - Add SEO fields to Pages TCA
=============================================

See :issue:`84798`

Description
===========

This feature adds SEO fields to Pages TCA. A new tab SEO now exists in the Page module which contains SEO related
metadata. Other non-SEO metadata is still on the Metadata tab.


Impact
======

Integrators can add both Open Graph and Twitter Card metadata for each page.

New fields added to Pages table:
- seo_title
- no_index
- no_follow
- og_title
- og_description
- og_image
- twitter_title
- twitter_description
- twitter_image

.. index:: Backend, Database, TCA, ext:core

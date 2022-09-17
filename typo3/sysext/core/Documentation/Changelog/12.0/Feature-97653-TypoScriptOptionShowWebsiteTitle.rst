.. include:: /Includes.rst.txt

.. _feature-97653-1652873318:

======================================================
Feature: #97653 - TypoScript Option "showWebsiteTitle"
======================================================

See :issue:`97653`

Description
===========

A new TypoScript option :typoscript:`config.showWebsiteTitle` has been added.

The option allows to define whether the website title, which is defined
in the site configuration, should be added to the page title, which is
e.g. used for the :html:`<title>` tag.

By default, the website title is added. To omit the website title, the
option has to be set to `0`.

Impact
======

It is now possible to influence the rendering of the website title in the
website's title tag by setting the :typoscript:`config.showWebsiteTitle`
option in TypoScript, which is enabled by default.

.. index:: Frontend, TypoScript, ext:frontend

.. include:: /Includes.rst.txt

.. _breaking-96520:

=====================================================================
Breaking: #96520 - Enforce non-empty configuration in cObj::parseFunc
=====================================================================

See :issue:`96520`

Description
===========

Invoking :php:`ContentObjectRenderer::parseFunc` without configuration
or TypoScript reference is not possible anymore and in general did not
make much sense.

Calling this method without any instructions led to various
side-effects, e.g. unintentionally enforcing `typo3/html-sanitizer`.
This problem was amplified when using :html:`<f:format.html parseFuncTSPath="">`
with an explicitly empty reference which actually did not do anything
and behaved the same as :html:`<f:format.raw>`.

This change enforces that parseFunc is only invoked with actual
instructions. An empty configuration will throw a :php:`\LogicException` and
requires corresponding source code or Fluid templates to be adjusted.

Impact
======

Still invoking :php:`ContentObjectRenderer::parseFunc` without configuration
will throw a :php:`\LogicException` in the frontend rendering process.

Affected Installations
======================

All installations using one of the following examples

PHP
---

..  code-block:: php

    /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj */
    $cObj->parseFunc($content, []);
    $cObj->parseFunc($content, [], '');
    $cObj->parseFunc($content, [], '< null.this.does.not.exist');

TypoScript
----------

..  code-block:: typoscript

    # `1` is considered a TypoScript reference which
    # most probably does not exist
    stdWrap.parseFunc = 1

    # non-existing TypoScript reference leading to empty configuration
    stdWrap.parseFunc =< null.this.does.not.exist

Fluid Templates
---------------

..  code-block:: html

    <!-- empty TypoScript reference leading to empty configuration -->
    <f:format.html parseFuncTSPath="">{content}</f:format.html>

    <!-- non-existing TypoScript reference leading to empty configuration -->
    <f:format.html parseFuncTSPath="null.this.does.not.exist">{content}</f:format.html>

Migration
=========

Invocations of `parseFunc` in PHP and TypoScript without using
any configuration or TypoScript reference have to be removed.

In Fluid templates :html:`<f:format.html parseFuncTSPath="">`
has the same effect as :html:`<f:format.raw>` which can be used
as replacement. However content is used "as-is" without further
sanitizing against cross-site scripting.

In case of the need for just replacing links with typolink,
it is recommended to use :html:`<f:transform.html>` ViewHelper.

Thus, any occurrence of the new :php:`\LogicException` mentioned above,
is also an indicator of some missing processing that has been unseen in
custom source code or template instructions.

.. index:: Frontend, TypoScript, NotScanned, ext:frontend

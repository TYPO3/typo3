.. include:: /Includes.rst.txt

.. _breaking-97091:

=======================================================
Breaking: #97091 - TSFE->clear_preview has been removed
=======================================================

See :issue:`97091`

Description
===========

The method :php:`clear_preview` of the :php:`TypoScriptFrontendController` has been removed.

Impact
======

Calling the method will result in a PHP Fatal Error.

Affected Installations
======================

All installations calling the :php:`clear_preview` method

Migration
=========

Build your own :php:`clear_preview` method:

..  code-block:: php

    $context = GeneralUtility::makeInstance(Context::class);
    $GLOBALS['SIM_EXEC_TIME'] = $GLOBALS['EXEC_TIME'];
    $GLOBALS['SIM_ACCESS_TIME'] = $GLOBALS['ACCESS_TIME'];
    $context->setAspect(
        'frontend.preview',
        GeneralUtility::makeInstance(PreviewAspect::class)
    );
    $context->setAspect(
        'date',
        GeneralUtility::makeInstance(
            DateTimeAspect::class,
            new \DateTimeImmutable('@' . $GLOBALS['SIM_EXEC_TIME'])
        )
    );
    $context->setAspect(
        'visibility',
        GeneralUtility::makeInstance(VisibilityAspect::class)
    );

.. index:: Frontend, PHP-API, PartiallyScanned, ext:frontend

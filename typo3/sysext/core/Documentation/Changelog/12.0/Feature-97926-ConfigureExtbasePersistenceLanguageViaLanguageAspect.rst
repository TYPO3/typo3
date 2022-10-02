.. include:: /Includes.rst.txt

.. _feature-97926-1657726554:

===========================================================================
Feature: #97926 - Configure Extbase Persistence Language via LanguageAspect
===========================================================================

See :issue:`97926`

Description
===========

Extbase's persistence functionality allows to configure the ORM queries via QuerySettings.

QuerySettings now accept to use a custom LanguageAspect (known from the Context API)
to define the language ID and the overlay behaviour.

This is more consistent to other places within TYPO3 Core to define translation behaviour when
querying records.

Impact
======

You can now specify a custom Language Aspect per query as defined in the query settings
in any Repository class:

Example to use the fallback to the default language when working with overlays:

..  code-block:: php

    $query = $this->createQuery();
    $query->getQuerySettings()->setLanguageAspect(
        new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED)
    );

.. index:: PHP-API, ext:extbase

.. include:: /Includes.rst.txt

====================================================
Feature: #89526 - FeatureFlag: betaTranslationServer
====================================================

See :issue:`89526`

Description
===========

The feature switch `betaTranslationServer` makes it possible for installations to fetch translations from the new translation server (beta status).
The new translation server is building labels from Crowdin (https://crowdin.com/project/typo3-cms) instead of the current translation server based on Pootle (https://translation.typo3.org/).

The integration is currently work in progress but will be finished before the LTS release of version 10.
Once the work has been stabilized and tested well, the feature flag will be removed for 10 and backported for 9.

If you are interested in this topic, join the Crowdin Initiative. All information can be found at https://typo3.org/community/teams/typo3-development/initiatives/localization-with-crowdin/.


Impact
======

Be aware that using this translation server is currently experimental. This means:

- Translations are incomplete and might be removed and added anytime
- Translations of community extensions are currently not available

.. index:: Backend, Frontend, ext:core

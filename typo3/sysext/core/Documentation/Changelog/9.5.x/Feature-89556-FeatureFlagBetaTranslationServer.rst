.. include:: ../../Includes.txt

===================================================
Feature: #89526 - FeatureFlag: newTranslationServer
===================================================

See :issue:`89556`

Description
===========

The feature switch `newTranslationServer` makes it possible for installations to fetch translations from the new translation server.
The new translation server is building labels from Crowdin (https://crowdin.com/project/typo3-cms) instead of the previous translation server based on Pootle (https://translation.typo3.org/).

If you are interested in this topic, join the Crowdin Initiative. All information can be found at https://typo3.org/community/teams/typo3-development/initiatives/localization-with-crowdin/.

It is very simple to provide translations by registering at Crowdin and suggest translations online.

Impact
======

The feature is enabled by default for new installations.

.. index:: Backend, Frontend, ext:core

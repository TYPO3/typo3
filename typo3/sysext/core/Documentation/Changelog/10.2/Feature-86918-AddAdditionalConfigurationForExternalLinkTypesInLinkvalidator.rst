.. include:: /Includes.rst.txt

=======================================================================================
Feature: #86918 - Add additional configuration for external link types in Linkvalidator
=======================================================================================

See :issue:`86918`

Description
===========

Additional configuration is added for crawling external links:

.. code-block:: typoscript

    mod.linkvalidator {

        linktypesConfig {
           external {

              # User-Agent string is filled with information about the crawling site
              httpAgentName = TYPO3 Linkvalidator
              httpAgentUrl =
              httpAgentEmail =

              headers {
              }

              method = HEAD

              range = 0-4048
           }
        }

For a description of the fields, see the linkvalidator documentation:
https://docs.typo3.org/c/typo3/cms-linkvalidator/main/en-us//Configuration/Index.html

It is recommended to fill out `httpAgentUrl` and `httpAgentEmail` so that the User-Agent
string is filled for crawling external URLs.

For headers, method and range it is recommended to stick with the default values.

Impact
======

It is possible to configure information for the 'User-Agent' string as is customary
for crawlers.

The settings 'headers', 'method' and 'range' are advanced settings. They can be used to
optimize the crawling.

Recommendation
==============

Set the settings httpAgentUrl and httpAgentEmail. Details can be found in the
linkvalidator documentation.



.. index:: ext:linkvalidator

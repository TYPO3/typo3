
.. include:: ../../Includes.txt

===============================================================================================
Feature: #67808 - Introduce Application classes for entry points and equivalent RequestHandlers
===============================================================================================

See :issue:`67808`

Description
===========

All entry points are encapsulating all previous initialization code in an Application class depending on the TYPO3_MODE
and several context-dependant constraints. Each Application class registers Request Handlers to the TYPO3 Bootstrap to
run a certain request type (e.g. eID or TSFE-logic, or AJAX requests in the Backend). Each Application is handed
over the Class Loader provided by Composer.

There are four types of Applications provided by the TYPO3 Core:

TYPO3\CMS\Frontend\Http\Application
-----------------------------------
All incoming web requests coming to index.php in the main directory, handling all TSFE and eID requests.
The Application sets TYPO3_MODE=FE very early.
The Application checks if all configuration is given, otherwise redirects to the TYPO3 Install Tool.

TYPO3\CMS\Backend\Http\Application
----------------------------------
All incoming web requests for any regular Backend call inside typo3/\*. This handles three types of Request Handlers:

- The AJAX Request Handler, which is triggered on requests with an "ajaxID" GET Parameter given.
- The Backend Module Request Handler, which handles all types of modules triggered on requests with an "M" GET Parameter
- The regular Request handler for typical other backend calls on index.php.

The Application checks if all configuration is given, otherwise redirects to the TYPO3 Install Tool.

\TYPO3\CMS\Backend\Console\Application
--------------------------------------
All CLI Requests handled by cli_dispatch.php. Only executes the parts that are necessary for Backend CLI Scripts used
with the cliKey syntax. The typical CliRequestHandler is used for handling requests set up by this Application.

\TYPO3\CMS\Install\Http\Application
-----------------------------------
The install tool Application only runs with a very limited bootstrap set up with a Failsafe Package Manager not taking
the ext_localconf.php scripts of installed extensions into account.

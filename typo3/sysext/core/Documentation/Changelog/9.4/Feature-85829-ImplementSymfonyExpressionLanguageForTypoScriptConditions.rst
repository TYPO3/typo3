.. include:: /Includes.rst.txt

=================================================================================
Feature: #85829 - Implement symfony expression language for TypoScript conditions
=================================================================================

See :issue:`85829`

Description
===========

The `symfony expression language <https://symfony.com/doc/current/components/expression_language.html>`__
has been implemented for TypoScript conditions in both frontend and backend.
The existing conditions are available as variables and/or functions. Please check the following tables in detail.

General Usage
-------------

To learn the full power of the symfony expression language please check the `documentation for the common expression syntax <https://symfony.com/doc/current/components/expression_language/syntax.html>`__.
Here are some examples to understand the power of the expression language:

.. code-block:: typoscript

   [page["uid"] in 18..45]
   # This condition matches if current page uid is between 18 and 45
   [END]

   [frontend.user.userId in [1,5,7]]
   # This condition matches if current logged in frontend user has the uid 1, 5 or 7
   [END]

   [not ("foo" matches "/bar/")]
   # This condition does match if "foo" **not** matches the regExp: `/bar/`
   [END]

   [applicationContext == "Production" && userId == 15]
   # This condition matches if application context is "Production" AND logged in user has the uid 15
   # Note that the old syntax with two blocks combined with && is deprecated
   # and will not work in v10:
   # [applicationContext == "Production"] && [userId == 15]
   [END]

   [request.getNormalizedParams().getHttpHost() == 'typo3.org']
   # This condition matches if current hostname is typo3.org
   [END]

   [like(request.getNormalizedParams().getHttpHost(), "*.devbox.local")]
   # This condition matches if current hostname is any subdomain of devbox.local
   [END]

   [request.getNormalizedParams().isHttps() == false]
   # This condition matches if current request is **not** https
   [END]

   [request.getPageArguments().get('foo_id') > 0]
   # This condition matches if the GET parameter foo_id is greater than 0.
   # getPageArguments() contains resolved route parts from enhancers which
   # request.getQueryParams() does not contain.
   [END]

   [traverse(request.getQueryParams(), 'tx_news_pi1/news') > 0]
   # This condition matches if current query parameters have tx_news_pi[news] set to a value greater than zero
   [END]


Variables
---------

The following variables are available. The values are context related.

+---------------------+------------+------------------------------------------------------------------------------+
| Variable            | Type       | Description                                                                  |
+=====================+============+==============================================================================+
| applicationContext  | String     | current application context as string                                        |
+---------------------+------------+------------------------------------------------------------------------------+
| page                | Array      | current page record as array                                                 |
+---------------------+------------+------------------------------------------------------------------------------+
| {$foo.bar}          | Constant   | Any TypoScript constant is available like before.                            |
|                     |            | Depending on the type of the constant you have to use                        |
|                     |            | different conditions, see examples below:                                    |
|                     |            |                                                                              |
|                     |            | * if constant is an integer: `[{$foo.bar} == 4711]`                          |
|                     |            | * if constant is a string put constant in quotes: `["{$foo.bar}" == "4711"]` |
+---------------------+------------+------------------------------------------------------------------------------+
| tree                | Object     | object with tree information                                                 |
|                     |            |                                                                              |
| .level              | Integer    | current tree level                                                           |
|                     |            |                                                                              |
| .rootLine           | Array      | array of arrays with uid and pid                                             |
|                     |            |                                                                              |
| .rootLineIds        | Array      | an array with UIDs of the root line                                          |
|                     |            |                                                                              |
| .rootLineParentIds  | Array      | an array with parent UIDs of the root line                                   |
+---------------------+------------+------------------------------------------------------------------------------+
| backend             | Object     | object with backend information (available in BE only)                       |
|                     |            |                                                                              |
| .user               | Object     | object with current backend user information                                 |
|                     |            |                                                                              |
| .user.isAdmin       | Boolean    | true if current user is admin                                                |
|                     |            |                                                                              |
| .user.isLoggedIn    | Boolean    | true if current user is logged in                                            |
|                     |            |                                                                              |
| .user.userId        | Integer    | UID of current user                                                          |
|                     |            |                                                                              |
| .user.userGroupList | String     | comma list of group UIDs                                                     |
+---------------------+------------+------------------------------------------------------------------------------+
| frontend            | Object     | object with frontend information (available in FE only)                      |
|                     |            |                                                                              |
| .user               | Object     | object with current frontend user information                                |
|                     |            |                                                                              |
| .user.isLoggedIn    | Boolean    | true if current user is logged in                                            |
|                     |            |                                                                              |
| .user.userId        | Integer    | UID of current user                                                          |
|                     |            |                                                                              |
| .user.userGroupList | String     | comma list of group UIDs                                                     |
+---------------------+------------+------------------------------------------------------------------------------+
| workspace           | Object     | object with workspace information                                            |
|                     |            |                                                                              |
| .workspaceId        | Integer    | id of current workspace                                                      |
|                     |            |                                                                              |
| .isLive             | Boolean    | true if current workspace is live                                            |
|                     |            |                                                                              |
| .isOffline          | Boolean    | true if current workspace is offline                                         |
+---------------------+------------+------------------------------------------------------------------------------+
| typo3               | Object     | object with TYPO3 related information                                        |
|                     |            |                                                                              |
| .version            | String     | TYPO3_version (e.g. 9.4.0-dev)                                               |
|                     |            |                                                                              |
| .branch             | String     | TYPO3_branch (e.g. 9.4)                                                      |
|                     |            |                                                                              |
| .devIpMask          | String     | :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']`                       |
+---------------------+------------+------------------------------------------------------------------------------+


Functions
---------

Functions take over the logic of the old conditions which do more than a simple comparison check.
The following functions are available in **any** context:

+------------------------+-----------------------+-------------------------------------------------------------------------+
| Function               | Parameter             | Description                                                             |
+========================+=======================+=========================================================================+
| request                | Custom Object         | This object provides 6 methods                                          |
|                        |                       |                                                                         |
| .getQueryParams()      |                       | `[request.getQueryParams()['foo'] == 1]`                                |
|                        |                       |                                                                         |
| .getParsedBody()       |                       | `[request.getParsedBody()['foo'] == 1]`                                 |
|                        |                       |                                                                         |
| .getHeaders()          |                       | `[request.getHeaders()['Accept'] == 'json']`                            |
|                        |                       |                                                                         |
| .getCookieParams()     |                       | `[request.getCookieParams()['foo'] == 1]`                               |
|                        |                       |                                                                         |
| .getNormalizedParams() |                       | `[request.getNormalizedParams().isHttps()]`                             |
|                        |                       |                                                                         |
| .getPageArguments()    |                       | `[request.getPageArguments().get('foo_id') > 0]`                        |
+------------------------+-----------------------+-------------------------------------------------------------------------+
| date                   | String                | Get current date in given format.                                       |
|                        |                       | Examples:                                                               |
|                        |                       |                                                                         |
|                        |                       | * true if day of current month is 7: `[date("j") == 7]`                 |
|                        |                       | * true if day of current week is 7: `[date("w") == 7]`                  |
|                        |                       | * true if day of current year is 7: `[date("z") == 7]`                  |
|                        |                       | * true if current hour is 7: `[date("G") == 7]`                         |
+------------------------+-----------------------+-------------------------------------------------------------------------+
| like                   | String                | This function has two parameters:                                       |
|                        |                       | the first parameter is the string to search in                          |
|                        |                       | the second parameter is the search string                               |
|                        |                       | Example: `[like("foobarbaz", "*bar*")]`                                 |
+------------------------+-----------------------+-------------------------------------------------------------------------+
| traverse               | Array and String      | This function has two parameters:                                       |
|                        |                       | - first parameter is the array to traverse                              |
|                        |                       | - second parameter is the path to traverse                              |
|                        |                       | Syntax: <array-key>[/<array-key>]*                                      |
|                        |                       | Example: `[traverse(request.getQueryParams(), 'tx_news_pi1/news') > 0]` |
+------------------------+-----------------------+-------------------------------------------------------------------------+
| ip                     | String                | Value or Constraint, Wildcard or RegExp possible                        |
|                        |                       | special value: devIP (match the devIPMask)                              |
+------------------------+-----------------------+-------------------------------------------------------------------------+
| compatVersion          | String                | version constraint, e.g. `9.4` or `9.4.0`                               |
+------------------------+-----------------------+-------------------------------------------------------------------------+
| loginUser              | String                | value or constraint, wildcard or RegExp possible                        |
|                        |                       | Examples:                                                               |
|                        |                       |                                                                         |
|                        |                       | * `[loginUser('*')]` // any logged in user                              |
|                        |                       | * `[loginUser(1)]` // user with uid 1                                   |
|                        |                       | * `[loginUser('1,3,5')]` // user 1, 3 or 5                              |
|                        |                       | * `[loginUser('*') == false]` // not logged in                          |
+------------------------+-----------------------+-------------------------------------------------------------------------+
| getTSFE                | Object                | TypoScriptFrontendController (:php:`$GLOBALS['TSFE']`)                  |
|                        |                       |                                                                         |
|                        |                       | Conditions based on `getTSFE()` used in a context where                 |
|                        |                       | TSFE is not available will always evaluate to false.                    |
+------------------------+-----------------------+-------------------------------------------------------------------------+
| getenv                 | String                | PHP function: :php:`getenv()`                                           |
+------------------------+-----------------------+-------------------------------------------------------------------------+
| usergroup              | String                | value or constraint, wildcard or RegExp possible                        |
+------------------------+-----------------------+-------------------------------------------------------------------------+


The following functions are only available in **frontend** context:

+--------------------+------------+-----------------------------------------------------------------+
| Function           | Parameter  | Description                                                     |
+====================+============+=================================================================+
| session            | String     | Get value from session                                          |
|                    |            |                                                                 |
|                    |            | Example, matches if session value = 1234567                     |
|                    |            | `[session("session:foo|bar") == 1234567]`                       |
+--------------------+------------+-----------------------------------------------------------------+
| site               | String     | get value from site configuration, or null if                   |
|                    |            | no site was found or property does not exist                    |
|                    |            |                                                                 |
|                    |            | Example, matches if site identifier = foo                       |
|                    |            | `[site("identifier") == "foo"]`                                 |
|                    |            |                                                                 |
|                    |            | Example, matches if site `base = http://localhost`              |
|                    |            | `[site("base") == "http://localhost"]`                          |
+--------------------+------------+-----------------------------------------------------------------+
| siteLanguage       | String     | get value from siteLanguage configuration, or                   |
|                    |            | null if no site was found or property does not exist            |
|                    |            |                                                                 |
|                    |            | Example, match if siteLanguage locale = foo                     |
|                    |            | `[siteLanguage("locale") == "de_CH"]`                           |
|                    |            |                                                                 |
|                    |            | Example, match if siteLanguage title = Italy                    |
|                    |            | `[siteLanguage("title") == "Italy"]`                            |
+--------------------+------------+-----------------------------------------------------------------+


Extending the expression language with own functions (like old userFunc)
------------------------------------------------------------------------

It is possible to extend the expression language with own functions like before userFunc in the old conditions.
An example could be :php:`TYPO3\CMS\Core\ExpressionLanguage\FunctionsProvider\TypoScriptConditionFunctionsProvider` which implements
the most core functions.

Please read .. _the introduction: https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.4/Feature-85828-MoveSymfonyExpressionLanguageHandlingIntoEXTcore.html first.

Add new methods by implementing own providers which implement the :php:`ExpressionFunctionProviderInterface` and
register the provider for the key `typoscript` in your own :file:`Configuration/ExpressionLanguage.php` file:

.. code-block:: php

      return [
         'typoscript' => [
            \TYPO3\CMS\MyExt\ExpressionLanguage\MyCustomProvider::class,
         ]
      ];


The code above will extend the TypoScript condition configuration with your own provider, which provide your own functions.

.. index:: Backend, Frontend, TypoScript, ext:core

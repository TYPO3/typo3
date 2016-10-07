
.. include:: ../../Includes.txt

================================================================
Feature: #74365 - Add Linkservice for unified referencing syntax
================================================================

See :issue:`74365`

Description
===========

Resources within TYPO3 have been referenced using multiple, different forms of syntax
in the past.

TYPO3 now supports a modern and future-proof way of referencing resources using an
extensible and expressive syntax which is easy to understand.

In order to understand the syntax, we will guide you through using a simple page
link.

`t3://page?uid=13&campaignCode=ABC123`

The syntax consists of three main parts, much like parts on an URL:

Syntax Namespace (t3://)
   The namespace is set to `t3://` to ensure the `LinkService` should be called to
   parse the URL.
   This value is fixed and mandatory.

Resource handler key (page)
   The resource handler key is a list of available handlers that TYPO3 can work
   with. At the time of writing these handlers are:

   * page
   * file
   * folder

   More keys can be added via `$TYPO3_CONF_VARS['SYS']['linkHandler']` in an associative
   array where the key is the handler key and the value is a class implementing
   the LinkHandlerInterface.

Resource parameters(?uid=13&campaignCode=ABC123)
   These are the specific identification parameters that are used by any handler.
   Note that these may carry additional parameters in order to configure the
   behavior of any handler.

Handler syntax
==============

page
----

The page identifier is a compound string based on several optional settings.

uid
   **int**:
   The **uid** of a page record.

   `t3://page?uid=13`
alias
   **string**:
   The **alias** of a page record (as an alternative to the UID).

   `t3://page?alias=myfunkyalias`
type
   **int** *(optional)*:

   `t3://page?uid=13&type=3` will reference page 13 in type 3.
parameters
   **string** *(optional, prefixed with &)*:

   `t3://page?uid=1313&my=param&will=get&added=here`
fragment
   **string** *(optional, prefixed with #)*:

   `t3://page?alias=myfunkyalias#c123`

   `t3://page?uid=13&type=3#c123`

   `t3://page?uid=13&type3?my=param&will=get&added=here#c123`

file
----

uid
   **int**: The UID of a file within the FAL database table `sys_file`.
   `t3://file?uid=13`

identifier
   **int**: The identifier of a file when not indexed in FAL.
   `t3://file?identifier=folder/myfile.jpg`

folder
------

identifier
   **string**: The identifier of a given folder.
   `t3://folder?identifier=fileadmin`

storage
   **string**: The FAL storage to the given folder (optional).
   `t3://folder?storage=1&identifier=myfolder`


Examples:
=========

Linking to a page in RTE
------------------------

The old way of linking to a page in the RTE resulted in the following code in the
database:
`<link 13?campaignCode=ABC123 _blank class="linkMe" #c1234>Text</link>`

The new way would be the following code in the database:
`<a href="t3://page?uid=13&campaignCode=ABC123#c1234" target="_blank" class="linkMe">Text</a>`

As you can see, the syntax is more in line with known markup, thus removing the
demand of data processing from or to the RTE component.

Referencing an image in RTE
---------------------------

`<img src="t3://file?uid=134&renderAs=png" width="200" height="200">`

In this example we illustrate a **fictional** usecase of identifier configuration (mind the "renderAs" part).

Impact
======

Currently the impact is rather low, since a fallback mechanism will still be able to
work with the old syntax.

.. index:: RTE, Backend

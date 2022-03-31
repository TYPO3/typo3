.. include:: /Includes.rst.txt

==================================================================================================
Important: #84144 - RootlineUtility is enriching only properly selected relational database fields
==================================================================================================

See :issue:`84144`

Description
===========

The main functionality for fetching the whole rootline of a page previously fetched all relational
fields defined in TCA of a page record. This led to massive performance problems with large menus,
as not all fields are necessary in root line records.

Now, the rootline fetching only looks up relational data of fields which have been added to
:php:`$GLOBALS[TYPO3_CONF_VARS][FE][addRootLineFields]`. The field `pages.media` is added per
default since it is a predefined value.

.. index:: Frontend, ext:frontend

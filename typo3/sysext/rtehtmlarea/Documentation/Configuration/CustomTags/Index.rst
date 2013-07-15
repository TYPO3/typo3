.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _custom-tags:

Configuring custom tags
-----------------------

In order for Internet Explorer in versions prior to Internet Explorer
9 to handle custom tags correctly, the following setting is required
in Page TSconfig:

::

   RTE.default.customTags = list-of-custom-tags

where  *list-of-custom-tags* is the list of all custom tags that may
be used in content.

For the custom tags to be preserved by the RTE transformation, the
following is required:

::

   RTE.default.proc.allowTags:= addToList(list-of-custom-tags)
   RTE.default.proc.entryHTMLparser_db.allowTags:= addToList(list-of-custom-tags)



.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _objects:

Objects rendering
^^^^^^^^^^^^^^^^^

This chapter describes how the rendering of each type of content element
is configured in css\_styled\_content. Some types are very simple and rely
purely on standard TypoScript objects. Other types use a configuration that
is specific to css\_styled\_content.

All content types rendering definitions contain at least two levels of nested
TypoScript object. The first level is **always** a :ref:`COA <t3tsref:cobj-coa-int>`.
Inside that object there is always the following configuration::

   10 = < lib.stdheader


This means that the :ref:`standard rendering of content element headers <setup-lib-stdheader>`
is referenced inside every content element types, ensuring that headers are rendered
consistently no matter what the type.


.. _objects-default-message:

Default Message
"""""""""""""""

When no rendering definition can be found for a content element type,
some default message is displayed. This message is based on a standard
:ref:`t3tsref:cobj-text` object and prints out some error message
in a yellow box::

	tt_content.default = TEXT
	tt_content.default {
		field = CType
		wrap = <p style="background-color: yellow;"><b>ERROR:</b> Content Element type "|" has no rendering definition!</p>

		prefixComment = 2 | Unknown element message:
	}


.. _objects-rendering-reference:

Rendering reference
"""""""""""""""""""

The following sections describe the rendering of each element type, with a reference
to all properties, when specific ones exist:

.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:

   Header/Index
   Text/Index
   Image/Index
   TextImage/Index
   BulletList/Index
   Table/Index
   Uploads/Index
   Mailform/Index
   Search/Index
   Menu/Index
   InsertRecords/Index
   Plugin/Index
   Divider/Index
   Html/Index


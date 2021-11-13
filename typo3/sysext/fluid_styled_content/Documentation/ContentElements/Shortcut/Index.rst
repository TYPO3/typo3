.. include:: /Includes.rst.txt

.. _content-element-shortcut:

==============
Insert Records
==============

.. include:: /Images/AutomaticScreenshots/ContentOverview/InsertRecordsPageContent.rst.txt

Ever have content on one page that you want to reference on another page? But you don't want to have to
maintain both and keep them both in sync? And you don't want to show the whole content from one
page on another. Using insert records you can add one content element from a page or all
the content elements from a page. You can also add content elements from several pages.

.. include:: /Images/AutomaticScreenshots/ContentElements/InsertRecordsBackend.rst.txt

Just select the content elements you want to display and if necessary, put them in the
right order.

In the frontend the referenced content elements will show up the same as the original one
(if the styling is not different for that page)

.. note::

   This is the only content element still using a small amount of TypoScript in the rendering
   process. This is done because you can add different rendering for records from
   different tables. Take a look at :typoscript:`tt_content.shortcut.20`.

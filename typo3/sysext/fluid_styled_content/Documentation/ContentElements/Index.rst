.. include:: /Includes.rst.txt

.. _content-elements:

====================
The content elements
====================

.. include:: /Images/AutomaticScreenshots/ContentOverview/TypicalPageContent.rst.txt

This chapter describes the default set of content elements provided by TYPO3's
Core. It will show you a description and screenshots of the backend fields.

.. _content-elements-general:

General fields
==============

These are fields which are used by (almost) every content element.


.. _content-elements-general-header:

Header
------

Almost every content element can contain a header, which consists of the following parts:

Header
   The text of the header

Type
   The type of heading (heading 1, heading 2, heading 3 ... or if the header is hidden).
   When not choosing an option, the default heading will be used, which can be configured
   at :ref:`constant-editor`.

Date
   Have the possibility to group the header with a date

Link
   Link wrapping the header text. This can be a TYPO3 CMS page, an external page, an email
   address or a link to a file.

The header fields can be found in the :guilabel:`General` tab of a content element.


.. include:: /Images/AutomaticScreenshots/ContentElements/HeaderBackend.rst.txt


.. _content-elements-general-show-in-section-menus:

Show in Section Menus
----------------------

Using this option will only be visible when using menu's based on sections. This will be
described in the chapter :ref:`content-element-menu`.

This field can be found in the :guilabel:`Appearance` tab.

.. include:: /Images/AutomaticScreenshots/ContentElements/ShowInSectionMenus.rst.txt

A :guilabel:`Section Menu`, which is in turn a content element itself
produces an output including the headlines of all content elements
with the flag :guilabel:`Show in Section Menus` set.

.. _content-elements-general-link-to-top:

Append with Link to Top of Page
-------------------------------

.. include:: /Images/AutomaticScreenshots/ContentElements/AppendWithLinkToTopOfPage.rst.txt

When checked, this will render a link below the content element to bring the visitor the
top of the page. This will be very convenient for your visitors when having long pages.

.. _content-elements-general-access:

Access
------

.. include:: /Images/AutomaticScreenshots/ContentElements/AccessTab.rst.txt

These fields define if and when a visitor has access to this content element. The access
fields all reside in the :guilabel:`Access` tab:

Visibility of content element
   By checking this option the content element will not be visible to any visitor.

Publish Date
   The date on which the content has to be published, which means making visible at a
   certain date.

Expiration Date
   The date on which the content will be expired, which means the content will be hidden
   on a certain date

Usergroup Access Rights
   Here you can select whether the content element only is available to a certain
   frontend user group, if it has to be visible only when the visitor is logged in or if
   it has to be hidden at a login.

.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:

   Header/Index
   TextMedia/Index
   Bullets/Index
   Table/Index
   Uploads/Index
   Menu/Index
   Shortcut/Index
   List/Index
   Div/Index
   Html/Index

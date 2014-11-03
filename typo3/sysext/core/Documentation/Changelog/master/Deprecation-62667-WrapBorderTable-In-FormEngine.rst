===============================================================
Deprecation: #62667 Additional "WrapBorder" Table In FormEngine
===============================================================

Description
===========

In previous versions the FormEngine always wrapped fields around an additional HTML table element.
This was done in a separate method called "wrapBorder" utilizing the SECTION_WRAP subpart
of the FormEngine template.
As styling is now done completely via LESS/CSS, all calls to the method are removed.
The wrapBorder method and sectionWrap property of FormEngine are now marked as deprecated
for removal with CMS 8.

Impact
======

Custom extensions using the wrapBorder() method will not have the additional table wrap.

Affected installations
======================

Installations using FormEngine and the wrapBorder() method or custom FormEngine templates
in their own extensions.
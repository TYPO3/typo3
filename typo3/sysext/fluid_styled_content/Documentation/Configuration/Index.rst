.. include:: ../Includes.txt

.. _configuration:

=============
Configuration
=============

Configuring content elements can be done for the frontend and for the backend.

The easiest way to change the appearance of content elements in the frontend is by using
the "Constant Editor". These settings are global, which means they are not configurable in
a single content element. Constants are predefined.

TYPO3 CMS is using TypoScript as a configuration language and is used by the frontend
rendering. By overriding TypoScript you can influence the rendering of most of the
frontend.

For the backend, fields can be shown or hidden, depending on the fields you are using or
the fields an editor is allowed to use. Configuration like this is done using
"Page TSconfig" or "User TSconfig".

.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:

   ConstantEditor/Index
   TypoScript/Index
   PageTsConfig/Index
   OverridingFluidTemplates/Index


.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt



.. _drop-down-list-and-button:

Drop-down list and button
^^^^^^^^^^^^^^^^^^^^^^^^^

A drop-down list of languages is provided that allows authors:

- to add the lang attribute,

- to change the value of the lang attribute,

- to remove the lang attribute.

The name of the languages in the dorp-down list are shown in the
author's BE language. The drop-down list uses ISO language codes and
refers to the language table provided by extension Static Info Tables
and its companion localization extensions. Additionally, the ISO code
value may be shown before or after the natural name of the language.

As the attribute values are not visible in WYSIWYG mode, a button is
provided that allows to show/hide the presence of a lang attibute. For
Internet Explorer, this feature will only work for versions >= 7 as
version 6 is not capable of attribute selectors. In browsers other
than Internet Expolorer, the value of the language attribute is also
shown in front of the marked text.

The language mark, if any, is also displayed in the status bar as:
element[language-code].

Site developers/admins have the ability to configure:

- whether lang or xml:lang or both are used as language attibute(s);

- which languages are available in the drop-down list.


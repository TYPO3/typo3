.. include:: /Includes.rst.txt

.. _tca:
.. _renderType:

===================
Use t3editor in TCA
===================

Extensions may configure backend fields to use the t3editor by TCA. The editor
is only available for fields of type `text`. By setting the
:ref:`renderType to t3editor <t3tca:columns-text-renderType-t3editor>` the
syntax highlighting can be activated.

By setting the property :ref:`format <t3tca:columns-text-properties-format>`
the mode for syntax highlighting can be chosen. Allowed values:
`css`, `html`, `javascript`, `php`, `typoscript`, `xml` and any
:ref:`custom mode <register_mode>` registered by an extension.

.. versionadded:: 11.3
    TCA fields of renderType :php:`t3editor` support the
    :php:`'readOnly' => true` option. If set, syntax highlighting
    is applied as usual, but the corresponding text can not be edited.


.. _tca_examples:

Examples
========

.. include:: /CodeSnippets/Automatic/T3editor1.rst.txt

Displays an edior like the following:

.. include:: /Images/AutomaticScreenshots/T3editor1.rst.txt



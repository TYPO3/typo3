..  include:: /Includes.rst.txt

..  _feature-107710-1234567890:

==========================================================
Feature: #107710 - Support for XLIFF 2.x translation files
==========================================================

See :issue:`107710`

Description
===========

TYPO3 now supports both XLIFF 1.2 and XLIFF 2.x translation file formats.
The XLIFF loader automatically detects which version is used and parses the
file accordingly, making the transition seamless for integrators and extension
authors.

XLIFF (XML Localization Interchange File Format) is an XML-based format for
storing translatable content. While TYPO3 has traditionally used XLIFF 1.2,
the XLIFF 2.x standard brings improvements in structure and simplification.


Version Detection
-----------------

The XLIFF loader automatically detects the file version by examining:

1. The XML namespace (``urn:oasis:names:tc:xliff:document:2.0`` for XLIFF 2.0)
2. The ``version`` attribute in the root element

No configuration or manual intervention is required - both formats work
transparently side by side.


Key Differences Between XLIFF 1.2 and XLIFF 2.x
------------------------------------------------

For integrators working with translation files, here are the main structural
differences:

**XLIFF 1.2 Structure:**

*   Uses ``<trans-unit>`` elements directly within ``<body>``
*   Translation approval via ``approved`` attribute (``yes``/``no``)
*   Namespace: ``urn:oasis:names:tc:xliff:document:1.2``

Example XLIFF 1.2:

..  code-block:: xml

    <?xml version="1.0" encoding="UTF-8"?>
    <xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
        <file source-language="en" target-language="de">
            <body>
                <trans-unit id="button.submit" approved="yes">
                    <source>Submit</source>
                    <target>Absenden</target>
                </trans-unit>
            </body>
        </file>
    </xliff>

**XLIFF 2.0 Structure:**

*   Uses ``<unit>`` elements containing ``<segment>`` elements
*   Translation state via ``state`` attribute on ``<target>`` (``initial``,
    ``translated``, ``reviewed``, ``final``)
*   More granular and modern structure
*   Namespace: ``urn:oasis:names:tc:xliff:document:2.0``

Example XLIFF 2.0:

..  code-block:: xml

    <?xml version="1.0" encoding="UTF-8"?>
    <xliff version="2.0" xmlns="urn:oasis:names:tc:xliff:document:2.0"
           srcLang="en" trgLang="de">
        <file id="f1">
            <unit id="button.submit">
                <segment>
                    <source>Submit</source>
                    <target state="final">Absenden</target>
                </segment>
            </unit>
        </file>
    </xliff>


Translation Approval Handling
------------------------------

TYPO3's ``requireApprovedLocalizations`` configuration is respected for both
formats:

*   **XLIFF 1.2**: Translations with ``approved="no"`` are skipped when
    approval is required
*   **XLIFF 2.x**: Translations with ``state="initial"`` or ``state="translated"``
    are treated as not approved, while ``state="final"`` and ``state="reviewed"``
    are considered approved


Impact
======

Extension authors and integrators can now use either XLIFF 1.2 or XLIFF 2.x
format for their translation files. Existing XLIFF 1.2 files continue to work
without any changes, while new projects can leverage the more modern XLIFF 2.x
standard.

This also improves compatibility with modern translation tools and services
that have adopted the XLIFF 2.0 standard.

..  index:: Backend, Frontend, Localization, ext:core

.. include:: /Includes.rst.txt

.. _important-99609-1674123952:

=========================================
Important: #99609 - Streamline flag icons
=========================================

See :issue:`99609`

Description
===========

We streamlined the flag icons and make them easier to handle.
The Core provides a range of flag icons that are representing countries,
regions, movements, islands, and more. The flags are mostly used in
conjunction with languages.

We agree that a flag does not represent a language, but having a visual
identifier attached to languages makes it easier for editors to
identify the language they want to edit or translate.

New flags added in this patch will express that we understand both,
the issue and the need to differentiate languages. We chose simple
colored flags to achieve this. It still allows differentiation while
variants like de-DE and de-CH can be maintained and identified.

New flags: black, blue, cyan, green, indigo, orange, pink, purple, red,
teal, white, yellow, rainbow.

Flags of historic countries have been removed:

-   AN, Netherlands Antilles (until 2010)
-   CS, State Union of Serbia and Montenegro (until 2006)

Flags for language codes have been removed:

-   kl, Greenlandic
-   mi, Māori

Flags for country regions have been aligned:

-   Spain, Catalonia: catalonia -> es-ct
-   Canada, Quebec: qc -> ca-qc

Please adjust your site configuration if you are using one of
the removed or renamed flag icons.

.. index:: Backend, ext:backend

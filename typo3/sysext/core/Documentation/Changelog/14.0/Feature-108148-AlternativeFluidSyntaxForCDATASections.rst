..  include:: /Includes.rst.txt

..  _feature-108148-1763288461:

==============================================================
Feature: #108148 - Alternative Fluid Syntax for CDATA Sections
==============================================================

See :issue:`108148`

Description
===========

A long-standing issue in Fluid templates has been that the Fluid variable
and inline ViewHelper syntax collides with inlined CSS or JavaScript code.
This issue has now been addressed with Fluid 5: A new alternative syntax
has been introduced that makes collisions between CSS/JavaScript and Fluid
far less likely.

The normal inline and variable syntax uses single curly braces `{ }` as tokens
in Fluid templates. In `<![CDATA[ ]]>` sections, this syntax is now ignored. Instead,
three curly braces `{{{ }}}` can be used to call Fluid ViewHelpers or to access
variables. The tag-based syntax is disabled altogether in CDATA sections.

Example:

..  code-block:: html

    <f:variable name="color" value="red" />
    <style>
    <![CDATA[
        @media (min-width: 1000px) {
            p {
                background-color: {{{color}}};
            }
        }
    ]]>
    </style>

The Fluid Explained documentation contains several practical examples of how
this new feature can be used:
`Avoiding syntax collision with JS and CSS <https://docs.typo3.org/permalink/fluid:escaping-workarounds>`_.

Note that it's still considered bad practice to put inline CSS or JavaScript
code in Fluid templates. Consider using a dedicated API endpoint, `data-*` attributes
or CSS custom properties (also known as CSS variables) to pass dynamic values
to JavaScript and CSS.


Impact
======

Inline CSS and JavaScript can now be wrapped in CDATA in Fluid templates, which
prevents syntax collision with Fluid's inline syntax.

CDATA sections are thus no longer removed from Fluid templates before rendering, see
`Breaking: #108148 - CDATA Sections No Longer Removed <https://docs.typo3.org/permalink/changelog:breaking-108148-1763289953>`_.

..  index:: Fluid, ext:fluid

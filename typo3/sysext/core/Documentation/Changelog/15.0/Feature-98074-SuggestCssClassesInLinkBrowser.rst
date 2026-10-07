..  include:: /Includes.rst.txt

..  _feature-98074-1791406656:

=====================================================
Feature: #98074 - Suggest CSS classes in link browser
=====================================================

See :issue:`98074`

Description
===========

The CSS class field of the link browser for TCA fields of type
:ref:`link <t3tca:columns-link>` can now offer a list of predefined CSS
classes. Editors pick a class from the list or still enter a custom one,
similar to the existing choices of the target field.

The classes are configured via page TSconfig. Each entry consists of a
`value`, which is the CSS class (or several space-separated classes), and an
optional `label`, which can be a plain text or a localization reference. If
no label is set, the value is shown.

Global configuration (applies to all link types):

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/page.tsconfig

    TCEMAIN.linkHandler.properties.cssClass.items {
        10 {
            value = btn btn-primary
            label = my_extension.messages:linkClass.buttonPrimary
        }
        20 {
            value = link-arrow
            label = Arrow link
        }
    }

Handler-specific configuration replaces the global list for that link type:

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/page.tsconfig

    TCEMAIN.linkHandler.file.cssClass.items {
        10 {
            value = link-download
            label = Download
        }
    }

The option complements `cssClass.default`, introduced with
:ref:`feature-107081-1752377084`, which preselects a class for new links.

The CSS class field of the link browser in the rich text editor is not
affected, its classes are still configured in the CKEditor configuration.

Impact
======

Integrators can provide the CSS classes editors are supposed to use for links
in TCA link fields, while editors can still enter any other class.

..  index:: Backend, TSConfig, ext:backend

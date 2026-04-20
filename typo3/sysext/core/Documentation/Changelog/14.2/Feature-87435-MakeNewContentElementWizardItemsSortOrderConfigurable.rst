.. include:: /Includes.rst.txt

.. _feature-87435:

================================================================================
Feature: #87435 - Make new content element wizard items' sort order configurable
================================================================================

See :issue:`87435`

Description
===========

It is now possible to set the order of content elements inside the wizard tabs
in the new content element wizard by setting `before` and `after` values in
Page TSconfig.

Previously, the order of tabs could be configured but not the order of
individual content elements inside a tab. This feature extends the existing
ordering mechanism to individual content elements, following the same pattern as
tab ordering.

This eliminates the need for workarounds, such as creating custom wizard groups
to reorder elements.

Example
-------

..  code-block:: typoscript

    mod.wizards.newContentElement.wizardItems {
        default.elements {
            textmedia {
                after = header
            }
            mask_article_card {
                after = textmedia
            }
            # Multiple elements can be specified (comma-separated)
            mask_article_list {
                after = header,textmedia
            }
            # Or use before
            header {
                before = textmedia
            }
        }
    }

Impact
======

Integrators and developers can now set the exact order of content elements
inside each wizard tab using Page TSconfig, without needing to create custom
wizard groups. The ordering mechanism follows the same syntax and behavior as
the existing tab ordering feature (:issue:`71876`), ensuring consistency and
familiarity.

This feature works alongside existing tab ordering: tabs can be ordered using
`before` and `after` at the group level, and content elements inside each tab
can be ordered using `before` and `after` at the element level.

If `before` and `after` configuration is not specified for elements, they retain
their default order, typically the order defined in TCA.

.. index:: Backend, TSConfig, ext:backend

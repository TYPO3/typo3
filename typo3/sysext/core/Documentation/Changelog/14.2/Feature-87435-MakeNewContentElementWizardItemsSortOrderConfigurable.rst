
.. include:: /Includes.rst.txt

.. _feature-87435:

==============================================================================
Feature: #87435 - Make new content element wizard items sort order configurable
==============================================================================

See :issue:`87435`

Description
===========

It is now possible to influence the order of content elements within wizard tabs
in the new content element wizard by setting `before` and `after` values in Page TSconfig.

Previously, only the order of tabs could be configured, but not the order of individual
content elements within a tab. This feature extends the existing ordering mechanism
to individual content elements, following the same pattern as tab ordering.

This eliminates the need for workarounds like creating custom wizard groups to
reorder elements.

Example
-------

.. code-block:: typoscript

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

Integrators and developers can now control the exact order of content elements
within each wizard tab using Page TSconfig, without needing to create custom wizard
groups. The ordering mechanism follows the same syntax and behavior as the existing
tab ordering feature (:issue:`71876`), ensuring consistency and familiarity.

The feature works alongside the existing tab ordering: tabs can be ordered using
`before` and `after` at the group level, and content elements within each tab
can be ordered using `before` and `after` at the element level.

If no `before` or `after` configuration is specified for elements, they will retain
their default order (typically the order defined in TCA).

.. index:: Backend, TSConfig, ext:backend


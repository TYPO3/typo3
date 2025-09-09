.. include:: /Includes.rst.txt

.. _important-104839-1726124400:

================================================================================
Important: #104839 - RTE processing YAML configuration now respects `removeTags`
================================================================================

See :issue:`104839`

Description
===========

..  important::

    Short version: With this bugfix, any save process
    to the contents of an existing RTE element will now properly apply
    the :yaml:`removeTags` default configuration (unless configured otherwise).

    To prevent a breaking change, the tags :html:`center`, :html:`font`, :html:`strike` and
    :html:`u` are now allowed to be saved by default (like it was with the bug in
    effect). This is planned to be changed with TYPO3 v14 as a breaking change.

    The unexpected tags :html:`link`, :html:`meta`, :html:`o:p`, :html:`sdfield`,
    :html:`style`, :html:`title` will now be removed.

    This behaviour can always be customized by setting :yaml:`removeTags` appropriately.

TYPO3 allows to configure which HTML tags are allowed to be persisted
to the database in case of Richtext-elements. This can be configured
either within the CKEditor YAML context, or via Page TSconfig:

..  code-block:: yaml
    :caption: EXT:rte_ckeditor/Configuration/RTE/Processing.yaml

    processing:
      HTMLparser_db:
        # previous default: center, font, link, meta, o:p, sdfield,
        #                   style, title, strike, u
        removeTags: [link, meta, o:p, sdfield, style, title]

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/TypoScript/page.tsconfig

    RTE.default.proc {
      HTMLparser_db {
        removeTags = link, meta, o:p, sdfield, style, title
      }
    }

Due to a bug in interpreting the YAML configuration, the syntax using
an array was actually never in effect.

This means, any implementation relying on such a YAML configuration (without
providing Page TSconfig), would not have removed the listed tags.

Due to TYPO3's internal processing, from those tags listed above,
the previous default tags :html:`center`, :html:`font`, :html:`strike` and
 :html:`u` were persisted to the database and also later evaluated in the frontend.

The other tags :html:`link`, :html:`meta`, :html:`o:p`, :html:`sdfield`,
:html:`style` and :html:`title` were displayed as HTML encoded entities
due to other sanitizing in the output (but still stored as HTML tags in the database).

These tags will no longer be stored by default now, and is considered a non-breaking
bugfix, because these tags should not occur within an RTE.

This wrong parsing has now been fixed, so that now both an `array` syntax as
well as `string` syntax is allowed in the YAML processing and will
be applied. Adapting the :yaml:`removeTags` setting allows to change
the now applied defaults to any tag configuration needed.

..  hint::

    Custom YAML configuration that used a `string` representation of :yaml:`removeTags`
    (instead of an `array`) was already properly evaluated.

    This bugfix has not been backported to TYPO3 v11 installations, to prevent
    a change of behaviour in a security-maintenance-only environment. If this fix
    is needed, you can convert the CKEditor array syntax by removing the square
    brackets in a :file:`Processing.yaml` override:

    ..  code-block:: yaml

        removeTags: [center, font, link, meta, o:p, sdfield, strike, style, title, u]

    to:

    ..  code-block:: yaml

        removeTags: center, font, link, meta, o:p, sdfield, strike, style, title, u

Affected installations
======================

TYPO3 setups with RTE YAML configurations utilizing either a custom
:yaml:`removeTags` processing directive or the default, defined via `array`
notation instead of `string`.

Migration
=========

Adjust the RTE YAML configuration processing directive `removeTags`
to suit the expected tag removal, or accept the new defaults.

.. index:: RTE, TSConfig, Backend, NotScanned

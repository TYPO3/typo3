.. include:: /Includes.rst.txt

.. _important-104827-1725611875:

======================================================================
Important: #104827 - Allow to use Regular Expressions in CKEditor YAML
======================================================================

See :issue:`104827`

Description
===========

The CKEditor plugin can now be configured with YAML syntax utilizing
Regular Expression objects for certain keys. By defining a Regular Expression,
the CKEditor replacement/transformation functionality feature is now fully
usable.

The CKEditor 5 configuration API allows to specify
Regular Expression JavaScript objects, for example in
:javascript:`editor.config.typing.transformations.extra.from` or
:javascript:`editor.config.htmlSupport.allow.name`:

..  code-block:: javascript
    :caption: Example CKEditor JavaScript configuration excerpt

    // part of `editor.config`
    {
      typing: {
        transformations: {
          extra: {
            from: /(tsconf|t3ts)$/,
            to: 'TYPO3 TypoScript TSConfig'
          }
        }
      }
      htmlSupport: {
        allow: {
          name: /^(div|section|article)$/
        }
      }
    }

When TYPO3 passes YAML configuration of the CKEditor forward
to JavaScript, it uses a html-entity encoded representation,
which does not allow to utilize Regular Expression objects,
and also the CKEditor API method `buildQuotesRegExp()` is not
usable in this scenario.

This was remedied already for the configuration key :yaml:`htmlSupport`
with its sub-keys, so that when a YAML key named :yaml:`pattern`
was found, TYPO3 automatically converted that to a proper JavaScript
Regular Expression:

..  code-block:: yaml
    :caption: Example YAML RTE configuration excerpt

    editor:
      config:
        htmlSupport:
          allow:
            - { name: { pattern: '^(div|section|article)$', flags: '' } }

..  important::

    Please note that the `/` character from the beginning and end
    of the regular expression must not be specified manually in YAML.
    Also take care of the ending `$` character, which is vital to CKEditor's
    proper parsing of a rule. The :yaml:`flags` key can contain Regular
    Expression flags, and can also be omitted.

This is now also possible for the `editor.config.typing.transformations`
structure:

..  code-block:: yaml
    :caption: Example YAML RTE configuration excerpt

    editor:
      config:
        typing:
          transformations:
            extra:
              - { from: { pattern: '(tsconf|t3ts)$', flags: '' }, to: 'TYPO3 TypoScript TSConfig' }

This conversion of Regular Expressions must be explicitly applied to
CKEditor configuration keys within the TYPO3 API, and cannot be used
generally for every key.

Thus, using a :yaml:`pattern` sub-key is currently applied only to the following
configuration structures (and recursively their sub-structures):

*   :yaml:`editor.config.typing.transformations`
*   :yaml:`editor.config.htmlSupport`

..  hint::

    This means, that the `pattern` sub-key can be used for all of:

    *   :yaml:`editor.config.htmlSupport.[...].name`
    *   :yaml:`editor.config.htmlSupport.[...].styles`
    *   :yaml:`editor.config.htmlSupport.[...].classes`
    *   :yaml:`editor.config.htmlSupport.[...].attributes`
    *   :yaml:`editor.config.typing.transformations.extra[...].from`


.. index:: RTE, , ext:rte_ckeditor

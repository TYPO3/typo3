..  include:: /Includes.rst.txt

..  _important-107063-1752056295:

===========================================================================
Important: #107063 - CKEditor 5 v46.1.0: TypeScript imports and CSS changes
===========================================================================

See :issue:`107063`

Description
===========

With the upgrade to CKEditor 5 v46.1.0, three relevant changes are made:

#.  The API naming of TypeScript type imports has changed a lot. Any custom
    CKEditor 5 plugin using TypeScript type imports in their build chain will
    need to be adapted to match these imports.
    See `https://ckeditor.com/docs/ckeditor5/latest/updating/nim-migration/migrating-imports.html`_
    for a large table of "before->after" renames.
    This is not considered a breaking change in context of TYPO3 integration,
    because existing JavaScript modules will continue to work, as TypeScript
    type imports are not part of the final output. Runtime imports that are
    exposed by the `@ckeditor5/ckeditor-*` modules have not been changed
    and will continue to work.

#.  A new opinionated default CSS is used by CKEditor to apply some
    improved styling over contents displayed within the RTE interface.
    Most of these are overruled by TYPO3's default CSS integration though.
    Possible customizations need to respect this.

#.  A few CSS classes have been renamed, see
    `https://ckeditor.com/docs/ckeditor5/latest/updating/guides/update-to-46.html`_.
    These are for example referenced in custom CKEditor YAML configurations like
    the following diff, and need to replace the `color` subkey:

    ..  code-block:: diff
        :caption: Configuration/RTE/Full.yaml - Before/After

         - {
             model: 'yellowMarker',
             class: 'marker-yellow',
             title: 'Yellow marker',
             type: 'marker',
        -    color: 'var(--ck-highlight-marker-yellow)'
        +    color: 'var(--ck-content--highlight-marker-yellow)'
           }
         - {
             model: 'greenMarker',
             class: 'marker-green',
             title: 'Green marker',
             type: 'marker',
        -    color: 'var(--ck-highlight-marker-green)'
        +    color: 'var(--ck-content-highlight-marker-green)'
           }
         - {
             model: 'redPen',
             class: 'pen-red',
             title: 'Red pen',
             type: 'pen',
        -    color: 'var(--ck-highlight-pen-red)'
        +    color: 'var(--ck-content-highlight-pen-red)'
           }


Affected installations
======================

TYPO3 installation relying on custom or third-party CKEditor 5 TypeScript build chains,
or CSS adaptations that no longer match the CKEditor 5 naming.


Possible Migration
==================

Follow the CKEditor 5 upgrade guide to change CSS class names and TypeScript imports.

..  index:: Backend, RTE, NotScanned

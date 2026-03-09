..  include:: /Includes.rst.txt

..  _important-109176-1773075613:

============================================================================
Important: #109176 - CKEditor hardened iframes by enforcing sandbox behavior
============================================================================

See :issue:`109176`

Description
===========

Security-related patches from CKEditor5 v47.6.0 have been back-ported to the
TYPO3 12.4.x branch. The patches harden the usage of iframes by enforcing the
sandbox_ behaviour in the General HTML Support feature's editing area. In
TYPO3 13.4 and later, the full CKEditor5 v47.6.0 upgrade_ is used instead.

Installations that already allow iframes in their RTE configuration (e.g. for
integrating Google Maps widgets) may need to explicitly allow scripts via the
RTE YAML configuration for interactive iframes to work inside the HTML editing
area:

..  code-block:: yaml

    editor:
      config:
        htmlSupport:
          # If you already allow iframes in content area...
          allow:
            - { name: 'iframe', attributes: { src: true } }
          # ...you may add `htmlIframeSandbox` to control the
          # `<iframe sandbox="…">` when rendered by CKEditor
          htmlIframeSandbox: [ 'allow-scripts', 'allow-same-origin' ]


This does not influence what is rendered in the frontend output, but only
affects the sandbox behaviour inside the CKEditor editing area.


..  _sandbox: https://ckeditor.com/docs/ckeditor5/latest/features/html/general-html-support.html#iframe-sandbox
..  _upgrade: https://ckeditor.com/blog/ckeditor-47-6-0-release-highlights/

..  index:: Backend, RTE, ext:rte_ckeditor

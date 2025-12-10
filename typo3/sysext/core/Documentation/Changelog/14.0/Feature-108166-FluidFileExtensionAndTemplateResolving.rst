..  include:: /Includes.rst.txt

..  _feature-108166-1763400992:

==============================================================
Feature: #108166 - Fluid file extension and template resolving
==============================================================

See :issue:`108166`

Description
===========

Fluid 5 introduces a specific file extension for template files.
The extension is used in combination with a generic extension,
which means that existing syntax highlighting in code editors
still works.

+-----------------+-----------------------+
| Before          | After                 |
+=================+=======================+
| MyTemplate.html | MyTemplate.fluid.html |
+-----------------+-----------------------+
| MyTemplate.xml  | MyTemplate.fluid.xml  |
+-----------------+-----------------------+
| ...             | ...                   |
+-----------------+-----------------------+

As stated in the Fluid release notes, this new file extension is
**entirely optional**. Existing template files will continue to work.
A fallback mechanism is in place, which checks for template files in
the following order:

..  code-block::

     templateRootPath: templates/
     template: myTemplate
     format: html

     1. templates/myTemplate.fluid.html
     2. templates/myTemplate.html
     3. templates/myTemplate
     4. templates/MyTemplate.fluid.html
     5. templates/MyTemplate.html
     6. templates/MyTemplate

This means that `*.fluid.*` files are preferred over files without the
new extension if both files exist in the same folder.

Another noteworthy change in Fluid is that template files no longer
need to start with an uppercase character: The user-provided spelling
of the template name will be tried first, the uppercase variant will be
used as a fallback.

Consequences for template overloading
-------------------------------------

If multiple template paths are provided (for example if an extension
overloads templates or partials of another extension), this new fallback
chain for template file names will be executed *per template path*.
In practice this means the following:

*   A TYPO3 community extension can ship `*.html` files (without the new
    file extension), which can be overloaded by a sitepackage extension
    that already uses `*.fluid.html` files.
*   A TYPO3 community extension can ship `*.fluid.html` files, which can
    still be overloaded by a sitepackage extension that uses `*.html`
    files.
*   `MyTemplate.html` will still overload `MyTemplate.html` like before,
    and the same applies to `*.fluid.html` files.

However, since older Fluid versions do not consider `*.fluid.*` files,
it is **not supported** to use the new file extension in a TYPO3
community extension that still supports TYPO3 versions below 14.

For the TYPO3 Core this means that all template files can safely be
renamed to the new file extension because Fluid 5 is always present.
To overload templates from the TYPO3 Core, both the new Fluid file
extension and the old file extension can be used, which allows TYPO3
community extensions to remain compatible with multiple TYPO3 Core
versions.

Edge case: Templates with file extension specified
--------------------------------------------------

Note that the described file extension fallback chain only works
if the file extension is not specified explicitly, but rather derived
from the template's format. If the file extension is part of the requested
template name, Fluid can't reliably add the `*.fluid.*` file extension
automatically and the template needs to be adjusted.

One use case of this would be a template in format `json` that calls a
partial in format `html`:

..  code-block:: html
    :caption: MyTemplate.fluid.json

    <!-- Won't work if template file is called MyTemplate.fluid.html: -->
    <f:render partial="MyTemplate.html" />

    <!-- Needs to be adjusted like this: -->
    <f:render partial="MyTemplate.fluid.html" />

Impact
======

While the TYPO3 Core can already switch to the new `*.fluid.*` file
extension, TYPO3 community extensions will probably continue to use
`*.html` for an extended period. However, on a v14 project, the new
file extension can already be used, both for individual development and
for the integration of TYPO3 community extensions.

Projects and extension authors willing to switch to the new file
extension can use the `fluid-rename <https://github.com/s2b/fluid-rename/>`_
utility extension, which has already been used for the TYPO3 Core.

This new file extension opens new possibilities because it is now
easily recognizable which files will be interpreted by Fluid. This will
enable better IDE integration and tooling support in the future.

..  index:: Fluid, ext:fluid

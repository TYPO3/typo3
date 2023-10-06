.. include:: /Includes.rst.txt

.. _breaking-97816-1656350406:

============================================
Breaking: #97816 - TypoScript syntax changes
============================================

See :issue:`97816`

Description
===========

TYPO3 v12 comes with a new TypoScript syntax parser that is more performant,
more robust and allows better tooling in the Backend.

The new parser is more forgiving in many places, but some seldom used syntax
details have been removed, too. This documentation explains details that may
be breaking for existing instances.

Also see :ref:`the feature documentation <feature-97816-1656350667>`
for an overview of syntax improvements.

Impact
======

Using one of the constructs below stops working in v12 and needs
TypoScript adaptions.

Affected installations
======================

Instances using TypoScript as outlined below.

Migration
=========

Streamlined constants usage
---------------------------

It has never been fully documented in which context "constants" :typoscript:`{$foo}`
shall be used and which exact capabilities they have. The main TypoScript constants
documentation within the :ref:`TypoScript Reference <t3tsref:typoscript-syntax-constants>` was partially
outdated, and the :ref:`TSconfig documentation <t3tsconfig:Syntax>` claimed TSconfig
is not constants aware at all, which isn't fully the case anymore. Let's sort out
some details:

* Nesting constants is **not** possible and never has been. A construct like
  this is invalid syntax and is treated as string literal: :typoscript:`{$foo{$bar}}`

* Recursive constants were possible with the old parser but are not supported with the new
  parser anymore. This was never documented, the Backend Template module never showed them as
  resolved, only the Frontend parsed recursive constants. The simple rule is now: Never
  access a constant within another constant. Instances using a construct like the below one
  need to untie constants.

  ..  code-block:: typoscript

      constants:
      foo = fooValue
      # This does not resolve to "fooValue" but is kept as string literal "{$foo}"
      bar = {$foo}

      setup:
      # This does NOT resolve to "fooValue", but to the string literal "{$foo}"
      myValue = {$bar}

* Constants are now restricted to "assignments" and "conditions". Using a constant to
  substitute an "identifier" / "object path" is no longer allowed. This has never been
  clarified in the docs before and instances abusing constants to specify object paths
  should be seldom and need to resolve the situation with the new parser now:

  This is supported:

  ..  code-block:: typoscript

      # Simple constant usage as assignment value:
      foo = {$bar}
      # Compiling a value with string literals and constants:
      foo = I am {$bar}
      # Using a constant in a condition:
      [ myValue = {$bar} ]
      # Using constant(s) in multiline assignments:
      foo (
          I am {$bar} and {$baz}
      )

  These constructs are *not* supported:

  ..  code-block:: typoscript

      # Using a constant as object path specification
      {$bar} = myValue
      # This is an object path specification, too, and not supported:
      foo < {$bar}

* PageTsConfig *does* support constant substitution: Site constants can be used
  in PageTsconfig. This has been introduced with TYPO3 v10, see
  :ref:`feature-91080-1657827157` for details.

File includes are always top level
----------------------------------

File includes with :typoscript:`@import` and :typoscript:`<INCLUDE_TYPOSCRIPT:` within
curly braces are not relative anymore. A construct like this is invalid:

..  code-block:: typoscript

    page = PAGE
    page {
        @import 'EXT:my_extension/Configuration/TypoScript/bar.typoscript'
        20 = TEXT
        20.value = bar
    }

With :file:`EXT:my_extension/Configuration/TypoScript/bar.typoscript` having this content:

..  code-block:: typoscript

    10 = TEXT
    10.value = foo

This *no longer* leads to this TypoScript:

..  code-block:: typoscript

    page = PAGE
    page.10 = TEXT
    page.10.value = foo
    page.20 = TEXT
    page.20.value = bar

Instead, the following TypoScript will be calculated:

..  code-block:: typoscript

    page = PAGE
    10 = TEXT
    10.value = foo
    20 = TEXT
    20.value = bar

This means :typoscript:`@import` and :typoscript:`<INCLUDE_TYPOSCRIPT:` basically break
any curly braces level, resetting current scope to top level. While inclusion of files has
never been documented to be valid within braces assignments, it still worked until TYPO3 v11.
This is now disallowed and must not be used anymore.

:typoscript:`<INCLUDE_TYPOSCRIPT:` with :typoscript:`DIR:` and relative paths
always assumes the :file:`public/` directory as base directory now.
(Formerly it was relative to the file holding the include statement.)

@import is more restrictive with wildcards
------------------------------------------

The previous implementation of :typoscript:`@import` relied on Symfony Finder. This turned out
to be a performance bottleneck, the new implementation is based on "native" PHP file and directory
lookup logic. For performance, security and best practice considerations, :typoscript:`@import`
is now a bit more restrictive than before, especially with wildcard :typoscript:`*` handling.

Integrators are encouraged to switch from :typoscript:`<INCLUDE_TYPOSCRIPT:` to
:typoscript:`@import` in TYPO3 v12 projects: The :typoscript:`<INCLUDE_TYPOSCRIPT:`
is more complex and harder to handle, but a bit more permissive. Note :typoscript:`@import`
can be placed within conditions bodies now: :typoscript:`@import` lines are only considered
if the condition matches. This did not work with TYPO3 v11. It is likely that
:typoscript:`<INCLUDE_TYPOSCRIPT:` will be deprecated with TYPO3 v13, integrators
should adapt to :typoscript:`@import` when upgrading to TYPO3 v12 already.

The following rules apply to :typoscript:`@import`:

* Files *must* reside in extensions, the lookup pattern *must* start with :typoscript:`EXT`
  if absolute. Including TypoScript snippets, for instance, from :file:`fileadmin` is *not* allowed
  and never has been for :typoscript:`@import`.

* File includes *may* be relative to the current file, and *must* be prefixed with :file:`./`
  in this case. Subdirectories are allowed, path traversal using :file:`../` is not allowed.

* Files *must* end with :file:`.typoscript` in frontend TypoScript. With TSconfig, both
  :file:`.tsconfig` and :file:`.typoscript` are allowed, but :file:`.tsconfig` should be
  preferred.

* Directory includes are *not* recursive.

* Directory traversal using :file:`../` is *not* allowed.

* Wildcards for directories are *not* allowed. This has never been documented as working, and
  is considered an unplanned side-effect of Symfony Finder. Few people used this undocumented
  feature, it should be possible to restructure existing uses relatively easily.

* Only a single wildcard :typoscript:`*` is allowed for filename patterns.

Valid examples:

..  code-block:: typoscript

    @import 'EXT:my_extension/Configuration/TypoScript/bar.typoscript'

    # Import all files in directory, ending with :file:`.typoscript`, or additionally
    # :file:`.tsconfig` in TSconfig scope, in native operating system ascending order.
    @import 'EXT:my_extension/Configuration/TypoScript/'

    @import 'EXT:my_extension/Configuration/TypoScript/*.typoscript'
    @import 'EXT:my_extension/Configuration/TypoScript/*.setup.typoscript'

    # Import setupFoo.typoscript, setup.foo.typoscript and similar
    @import 'EXT:my_extension/Configuration/TypoScript/setup*.typoscript'
    @import 'EXT:my_extension/Configuration/TypoScript/setup*'

    # If this is in file 'EXT:my_extension/Configuration/TypoScript/foo.typoscript',
    # file 'EXT:my_extension/Configuration/TypoScript/bar.typoscript is included
    @import './bar.typoscript`
    # Relative sub directories includes are supported
    @import './SubDirectory/bar.typoscript`
    # Relative sub directories with wildcards are supported,
    # this will include ./SubDirectory/foo.typoscript
    @import './SubDirectory/*'

Invalid examples:

..  code-block:: typoscript

    # fileadmin and friends not allowed
    @import 'fileadmin/foo.typoscript'

    # Tries to include foo.txt.typoscript, *not* foo.txt
    @import 'EXT:my_extension/Configuration/TypoScript/foo.txt'

    # Directory traversal is not allowed
    @import 'EXT:my_extension/Configuration/TypoScript/Foo/../Bar/bar.typoscript'

    # Directory wildcards are not allowed
    @import 'EXT:my_extension/Configuration/TypoScript/*/foo.typoscript'

    # Multiple wildcards in filename pattern are not allowed
    @import 'EXT:my_extension/Configuration/TypoScript/foo.*.*.typoscript'


UTF-8 BOM in TypoScript files
-----------------------------

The new TypoScript parser no longer ignores `UTF-8 BOM <https://en.wikipedia.org/wiki/Byte_order_mark>`_
in included files: Having a Byte-order-mark in TypoScript files may create undesired
results. They should be removed. UTF-8 BOM is disallowed in various other languages,
for instance JSON and PHP. The new parser follows here. Modern editors typically don't
add an UTF-8 BOM anymore.

Instances can check if they use UTF-8 BOM with a Unix shell command:

..  code-block:: bash

    # find affected files
    find . -type f -print0 | xargs -0 -n1 file {} | grep 'UTF-8 Unicode (with BOM)'
    # remove UTF-8 BOM from a single file
    sed -i '1s/^\xEF\xBB\xBF//' affectedFile.typoscript

Support for \\n and \\r\\n linebreaks only
------------------------------------------

TypoScript sources must terminate single lines with either "\\n" (Unix ending: LineFeed),
or "\\r\\n" (Windows ending: Carriage return, LineFeed). Ancient Mac, prior to Mac OS X
used "\\r" as single linebreak character. This old linebreak type is no longer detected
when parsing TypoScript and may lead to funny results, but chances are very low any
instance is affected by this.

Operator matching has higher precedence
---------------------------------------

The new parser looks for valid operators first, then parses things behind it.
Consider this example:

..  code-block:: typoscript

    lib.nav.wrap =<ul id="nav">|</ul>

This is ambiguous: The above :typoscript:`=<ul` could be interpreted both as an
assignment :typoscript:`=` of the value :typoscript:`<ul`, or as a reference
:typoscript:`=<` to the identifier :typoscript:`ul`.

While the old parser interpreted this as an assignment, the new parser treats it
as a reference.

The above example aims for an assignment, though, which can be achieved by adding
a whitespace between :typoscript:`=` and :typoscript:`<`:

..  code-block:: typoscript

    lib.nav.wrap = <ul id="nav">|</ul>


.. index:: Backend, Frontend, TSConfig, TypoScript, NotScanned, ext:core

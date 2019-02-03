.. include:: ../Includes.txt


.. _config-concepts:

======================
Configuration Concepts
======================


Configuration Overview
======================

The main principles of configuring a Rich Text Editor in TYPO3
apply to editing with any Rich Text Editor (`rte_ckeditor`, `rtehtmlarea`, ...).

Some of the functionality (for example the RTE transformations) is
embedded in the TYPO3 core and not specific to `rte_ckeditor`.

There are three main parts relevant for rich text editing with TYPO3:

#. **Editor configuration:** This covers how the actual editor (in this case CKEditor)
   should behave, what buttons should be shown, what options are available.
#. **RTE transformations:** This defines how the information is processed when saved
   from the Rich Text Editor to the database and when loaded from the database into
   the Rich Text Editor
#. **Frontend output configuration**: The information fetched from the database may need to
   be processed for the frontend. The configuration of the
   frontend output is configured via TypoScript.

.. todo: diagram: overview with DB <-> RTE, DB -> FE etc.

This section mainly covers editor configuration and RTE transformations, as for
TypoScript the TypoScript reference handles output of HTML content and
has everything preset (see :ref:`t3tsref:parsefunc`).


.. tip::

   Before you start, have a look at the :ref:`config-best-practices`.


.. _config-editor:

Editor Configuration
====================

Yaml
----

For TYPO3 v8 the ability to configure editor-related configuration and transformations
via Yaml (Yet-Another-Markup-Language) was made available and is usable for both CKEditor
and HtmlArea, although for the latter it is recommended to use the existing configuration
when having special setups.

.. todo: add link to general information about configuration with Yaml, once available

Yaml Basics
~~~~~~~~~~~

* Yaml is case sensitive
* Indenting level reflects hierarchy level and indenting must be used consistently
  (indent with 2 spaces in `rte_ckeditor` configuration).
* Comments begin with a `#`.
* White space is important, use a space after `:`.

This is a dictionary (associative array):

.. code-block:: yaml

   key1: value
   key2: value

A dictionary can be nested, for example:

.. code-block:: yaml

   key1:
     key1-2: value

This is a list:

.. code-block:: yaml

   - list item 1
   - list item 2

A dictionary can be combined with a list:

.. code-block:: yaml

   key:
     key2:
       - item 1
       - item 2


.. configuration-presets:

Configuration Presets
---------------------

Presets are the heart of having custom configuration per record type, or
page area. A preset consists of a name and a reference to the location
of a Yaml file.

TYPO3 ships with three RTE presets, “default”, “minimal” and “full”. The
"default" configuration is active by default.

It is possible for extensions to ship their own preset like “news”, or “site_xyz”.

Registration of a preset happens within :file:`LocalConfiguration.php`,
:file:`AdditionalConfiguration.php` or within
:file:`ext_localconf.php` of an extension:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['default'] = 'EXT:rte_ckeditor/Configuration/RTE/Default.yaml';

This way, it is possible to override the default preset, for example by using
the configuration defined in a custom extension:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['default'] = 'EXT:my_extension/Configuration/RTE/Default.yaml';


TYPO3 uses the “default” preset for all Rich-Text-Element fields. To use
a different preset throughout an installation or a branch of the website,
see :ref:`override-configuration-via-page-tsconfig`.

Selecting a specific preset for bullet lists can be done via TCA
configuration of a field. The following example shows the TCA configuration
for the sys_news database table, which can be found in
:file:`EXT:core/Configuration/TCA/sys_news.php`.

.. code-block:: php

   'content' => [
      'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.text',
      'config' => [
         'type' => 'text',
         'cols' => 48,
         'rows' => 5,
         'enableRichtext' => true,
         'richtextConfiguration' => 'default',
      ],

Enabling Rich Text Parsing itself is done via :ref:`t3tca:columns-text-properties-enableRichtext`,
and a specific configuration
can be set via :ref:`t3tca:columns-text-properties-richtextConfiguration`, setting it to for example
“news”.

.. _override-configuration-via-page-tsconfig:

Overriding Configuration via Page TSconfig
------------------------------------------

Instead of overriding all TCA fields to use a custom preset, it is possible
to override this information via Page TSconfig.

The option :typoscript:`RTE.default.preset = news` can also be set on a per-field
and per-type basis:

.. code-block:: typoscript

   # per-field
   RTE.config.tt_content.bodytext.preset = minimal

   # per-type
   RTE.config.tt_content.types.bullets.bodytext.preset = bullets

* line #2: This sets the "minimal" preset for all bodytext fields of
  content elements.
* line #4: This sets the "bullets" preset for all bodytext fields of
  content elements with Content Type “Bullet list” (CType=bullets).

Of course, any other specific option set via Yaml can be overridden via
Page TSconfig as well.

.. todo: real world example usages

For more examples, see :ref:`t3tsconfig:pageTsRte` in "TSconfig Reference".


.. _config-rte-transformations:

RTE Transformations
===================

Transformations are directives for parsing HTML markup. They are executed by the
TYPO3 Core every time a RTE-based field is saved to the TYPO3 database or fetched
from the database for the Rich Text Editor to render. This way, there are always
two ways / two transformations applied.

There are several advantages for transformations, the most prominent reason is to
not inject bad HTML code into the database which in turn would be used for output.
Transformations from the RTE towards the database can filter out HTML tags or attributes.

.. todo: diagram rte -> DB -> RTE
   todo: add examples for transformations
   todo: possibly move most of this part to TYPO3 explained:
     https://docs.typo3.org/typo3cms/CoreApiReference/latest/ApiOverview/Rte/Transformations/Index.html

A Brief Dive Into History
-------------------------

Back in the very old days of TYPO3, there was an RTE which only worked inside Microsoft
Internet Explorer 4 (within the system extension “`rte`”). All other editors of TYPO3 had
to write HTML by hand, which was very complicated with all the table-based layouts available.
Links were not set with a :html:`<a>` tag, but with a so-called :html:`<typolink 23,13 _blank>`
tag. Further tags were :html:`<typolist>` and :html:`<typohead>`, which were stored in the database
1:1. Since RTEs did not understand these special tags, they had to transform these special tags into
valid HTML tags. Additionally, TYPO3 did not store regular :html:`<p>` or :html:`<div>` tags but
treated every line without a surrounding HTML block element as :html:`<p>` tag. The frontend rendering
then added `<p>` tags for each line when parsing (see below).

Transformations were later used to allow :html:`<em>`/:html:`<strong>` tags instead of :html:`<b>`/:html:`<i>`
tags, while staying backwards-compatible.

A lot of transformation options have been dropped for TYPO3 v8, and the default configuration
for these transformations acts as a solid base. CKEditor itself includes features that work as
another security layer for disallowing injecting of certain HTML tags in the database.

For TYPO3 v8, the :html:`<typolink>` tag was migrated to proper :html:`<a>` tags with a special
:html:`<a href="t3://page?id=23">` syntax when linking to pages to ensure HTML valid output.
Additionally, all records that are edited and stored to the database now contain proper
<p> tags, and transformations for paragraph tags are only applied when not set yet.

Transformations for invalid links and images (still available in HtmlArea) are still in place.

Most logic related to transformations can be found within :php:`TYPO3\CMS\Core\Html\RteHtmlParser`.


.. _transformations-vs-acf:

Transformations vs. CKEditor’s Advanced Content Filter
------------------------------------------------------

TYPO3’s HtmlParser transformations were used to transform readable semi-HTML
code to a full-blown HTML rendering ready for the RTE and vice versa. Since
TYPO3 v8, magically adding :html:`<p>` tags or transforming :html:`<typolink>`
tags is not necessary anymore, which leaves transformations almost obsolete.

However, they can act as an extra fallback layer of security to filter out
disallowed tags when saving. TYPO3 v8 configuration ships with a generic
transformation configuration, which is mainly based on legacy functionality
shipped with TYPO3 nowadays.

However, CKEditor comes with a separate strategy of allowing which HTML tags
and attributes are allowed, and can be configured on an editor-level.
This configuration option is called “allowedContent”, the feature itself is
named `Advanced Content Filter <http://docs.ckeditor.com/#!/guide/dev_advanced_content_filter>`__
(ACF).

Activating CKEditor’s table plugin allows to add :html:`<table>`, :html:`<tr>`
tags etc. Enabling the link picker enables the usage of :html:`<a>` tags. CKEditor
cleans content right away which was e.g. copy-pasted from MS Word and does not
match the allowed tags.


.. _config-frontend:

Frontend Output Configuration
=============================

Mostly due to historical reasons, the frontend output added :html:`<p>` tags to each
line which is not wrapped in HTML. Additionally the :html:`<typolink>` tag was replaced
by :html:`<a>` tags and checked if e.g. if a link was set to a specific page within
TYPO3 is actually accessible for this specific visitor.

The latter part is still necessary, so the :html:`<a href="t3://page?id23">` HTML snippet
is replaced by a speaking URL which the power of typolink will still take care of.
There are, of course, more options to it, like default “target” attributes for
external links or spam-protecting links to email addresses, which all happens within the
typolink logic, the master for generating a link in the TYPO3 Frontend rendering process.

.. todo: [DIAGRAM DB => FE]


TypoScript
----------

As with every content that is rendered via TYPO3, this processing for the frontend
output of Rich-Text-Editing fields is done via TypoScript, more specifically within
the stdWrap property :ref:`t3tsref:parsefunc`. With Fluid Styled Content and CSS Styled
Content comes :typoscript:`lib.parseFunc` and :typoscript:`lib.parseFunc_RTE` which add
support for parsing :html:`<a>` and :html:`<link>` tags and dumping them into the typolink
functionality. The shipped TypoScript code looks like this:

.. code-block:: typoscript

   lib.parseFunc.tags {
      a = TEXT
      a {
         current = 1
         typolink {
            parameter.data = parameters:href
            title.data = parameters:title
            ATagParams.data = parameters:allParams
            target.data = parameters:target
            extTarget = {$styles.content.links.extTarget}
            extTarget.override.data = parameters:target
         }
      }
   }


If you already use Fluid Styled Content and CSS Styled Content and
you haven’t touched that area of TypoScript yet, you’re good to go
by including the TypoScript template.

Fluid
-----

Outputting the contents of a RTE-enabled database field within Fluid can
be achieved by adding :html:`<f:format.html>{record.myfield}</f:format.html>`
which in turn calls :typoscript:`stdWrap.parseFunc` with :typoscript:`lib.parseFunc_RTE`
thus applying the same logic. Just ensure that the :typoscript:`lib.parseFunc_RTE`
functionality is available.

You can check if this TypoScript snippet is loaded by using
:guilabel:`Web > Template` and use the TypoScript Object Browser (Setup)
to see if :typoscript:`lib.parseFunc_RTE` is filled.

.. todo: [SCREENSHOT of TSOB having lib.parseFunc_RTE open]

.. tip::
   In some cases it is an advantage to use the fluid inline notation to output the contents
   of a RTE-enabled database field: :html:`{record.myfield -> f:format.html()}`. This makes
   it easier to process the output further (e.g. by chaining Fluid ViewHelpers).




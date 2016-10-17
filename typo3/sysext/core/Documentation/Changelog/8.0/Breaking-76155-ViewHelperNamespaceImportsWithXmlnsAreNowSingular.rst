
.. include:: ../../Includes.txt

=============================================================================
Breaking: #76155 - ViewHelper Namespace imports with `xmlns` are now singular
=============================================================================

See :issue:`76155`

Description
===========

Fluid templates which use **multiple** ´<div xmlns:xyz="...">` in one template file are affected. Only the first
such node is now detected and respected. The reasons for this new limitation are the reworked internals of Fluid since
the standalone version.

Before, namespace nodes were handled internally by the TemplateParser. They worked in a way that the namespace
dictated by the node would **only apply inside that node**.

After, namespace nodes are handled by template pre-processing and handed off to the ViewHelperResolver without going
through the TemplateParser first. Since it happens in pre-processing, namespaces dictated by such nodes now **apply
across the entire template file**.

This has two effects on template parsing:

1. Extraction of `xmlns:xyz` style imports happens **once** and detects only a single node.
2. Nesting no longer matters; a namespace imported with this method will apply to the entire template file since it
   is extracted during pre-processing and is not recursive.

Most templates will not be affected by this since the norm is already to include a single such namespace import and
put it as the outermost surrounding tag, then add all imported namespaces on that single node. This usage is still
fully supported.


Impact
======

The change affects template files which fulfill one or both of the following conditions:

1. Any template file which contains multiple nodes with `xmlns:xyz` imports will see only the first node detected.
2. Any template file which assumes an imported namespace is removed when the enclosing tag is closed and uses a
   previously imported namespace after the closing node will likely see errors with `ViewHelper could not be resolved`
   since Fluid will attempt to translate matching XHTML nodes with namespace prefixes to ViewHelper classes.


Affected Installations
======================

TYPO3 8.0 and above, any site matching conditions stated in `Impact`.


Migration
=========

There is one migration for each of the conditions above:

1. Templates with multiple `xmlns:xyz` nodes can be migrated by combining all those nodes into one.
2. Templates which assume a closing container tag removes the namespace will have to migrate by extracting the XHTML
   that collides with the Fluid namespace and placing it in a separate template file (e.g. Partial template).


.. index:: Fluid

..  include:: /Includes.rst.txt

..  _important-106192-1739862406:

==========================================================================
Important: #106192 - Add 'center' and 'font' to YAML processing removeTags
==========================================================================

See :issue:`106192`

Description
===========

The HTML tags :html:`<font>` and :html:`<center>` are officially deprecated for
some time: see `<https://developer.mozilla.org/en-US/docs/Web/HTML/Element/font>`__
and `<https://developer.mozilla.org/en-US/docs/Web/HTML/Element/center>`__.

The default YAML processing configuration file
:file:`EXT:rte_ckeditor/Configuration/RTE/Processing.yaml`
has been changed to remove these HTML tags :html:`<font>` and
:html:`<center>` by default when saving a RTE field content
to the database.

This new default is adjusted with the option `processing.HTMLparser_db.removeTags`,
which now also lists these two tags.

A stored input like :html:`<p><font face="Arial">My text</font></p>`
will - when saved - be changed to :html:`<p>My text</p>`.

Affected installations
----------------------

All installations having :html:`<font>` and :html`<center>`
stored in their database fields, and where no custom RTE
YAML configuration is in place that allows these tags.

Please note that due issue :issue:`104839`, this
`removeTags` option was never properly applied previously, so the chances
are that an installation never had output for `font` and
`center` properly working anyways.

Also take note that the CKEditor by default uses :html:`<span style="...">`
tags to apply font formatting when using the `Full` preset.

Thus, real-life impact should be low, but for legacy installations
you may want to convert existing data to replace `font/html` tags
with their appropriate modern counterparts.

Migration
---------

Either accept the removal of these tags, and use specific
HTML tags like :html:`<span>` and :html:`<div>` to apply formatting.

Or adapt the RTE Processing via TypoScript/YAML configuration
to not have `center` and `font` to the `processing.HTMLparser_db.removeTags`
list.

If the tags `center` and `font` have been configured via the `editor.conf.style.definitions`
YAML option (not set by default), CKEditor would allow to use these tags,
but they will now be removed both when saving, or when being rendered in the
frontend. So these style definitions should be removed and/or adapted to
:html:`<span style="...">` configurations.

..  index:: Backend, Database, Frontend, RTE, YAML, NotScanned, ext:rte_ckeditor

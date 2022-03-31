.. include:: /Includes.rst.txt

=====================================================================
Breaking: #79300 - Removed RTE proc.transformBoldAndItalicTags option
=====================================================================

See :issue:`79300`

Description
===========

The RTE processing TSconfig option `RTE.default.proc.transformBoldAndItalicTags` has been removed from the processing
functionality.

It was a shortcut to change all :html:`<b>` and :html:`<i>` tags coming from the database to :html:`<strong>` and :html:`<em>` when loading the RTE. In return
when storing the content again from the RTE, the :html:`<strong>` and :html:`<em>` tags were moved to :html:`<b>` and :html:`<i>` again.

If an integrator wanted to explicitly disable this functionality (basically having :html:`<strong>` and :html:`<em>` in the database), he/she needed
to explicitly disable the option (setting it to "0", not just unsetting the option via PageTSconfig).


Impact
======

Setting this option does not transform the tags anymore when loading the RTE or storing in DB. Instead, :html:`<strong>` and :html:`<em>` are stored
in the database when editing a record.


Affected Installations
======================

Any installation having custom RTE configuration and explicitly setting this option without having a proper HTMLparser replacement
mapping in place.


Migration
=========

Any default configuration of RTEHtmlArea that was in place before 8.6.0 has a simple replacement to ensure the same functionality now:

This code does the same as having :typoscript:`proc.transformBoldAndItalicTags=1`:

.. code-block:: typoscript

	RTE.default.proc {
	    # make <strong> and <em> tags when sending to the RTE
	    HTMLparser_rte {
	        tags {
	            b.remap = strong
	            i.remap = em
	        }
	    }
	    # make <b> and <i> tags when sending to the DB
	    HTMLparser_db {
	        tags {
	            strong.remap = B
	            em.remap = I
	        }
	    }
	}

If having the option explicitly turned off (allowing strong, b, em, and i tags) is what is wanted the configuration should look like this:

.. code-block:: typoscript

	RTE.default.proc {
	    # no remapping should happen, tags should stay as they are
	    HTMLparser_rte {
	        tags {
	            b.remap >
	            i.remap >
	        }
	    }
	    # no remapping should happen, tags should stay as they are
	    HTMLparser_db {
	        tags {
	            strong.remap >
	            em.remap >
	        }
	    }
	}

Please note that this migration is only necessary if custom RTE options are in place, as the default RTE HTMLArea configuration does that
automatically.

.. index:: RTE, TSConfig

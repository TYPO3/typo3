..  include:: /Includes.rst.txt

..  _important-111434-1794567511:

=================================================================
Important: #111434 - Date form element values are set to midnight
=================================================================

See :issue:`111434`

Description
===========

The :yaml:`Date` form element of :composer:`typo3/cms-form` submits a plain
date without a time. Before, the submitted value was converted into a
:php:`\DateTime` object carrying the time of the request, unless a
:yaml:`DateRange` validator was configured, which already set the time to
midnight.

The value is now always converted to midnight (`00:00:00`) of the submitted
date. Finishers that store the value, for example the :yaml:`SaveToDatabase`
finisher with its default :yaml:`dateFormat` `U`, now store the timestamp of
midnight instead of the time of submission.

..  index:: Frontend, YAML, ext:form

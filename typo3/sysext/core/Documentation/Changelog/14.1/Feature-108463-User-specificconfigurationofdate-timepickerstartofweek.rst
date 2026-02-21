..  include:: /Includes.rst.txt

..  _feature-108463-1765370503:

=======================================================================================
Feature: #108463 - User-specific configuration of date-timepicker's first day of a week
=======================================================================================

See :issue:`108463`

Description
===========

Previously, the date-time picker used the selected locale of a backend user.

However, certain locale configurations might rather be a user preference.
For example, users may prefer an english backend but want to use a weekday
start on "Monday" instead of "Sunday" due to their cultural habits.

Thus, the "first day of a week" has been decoupled from the locale selection
and can be configured on a per-user setting. By default, it still inherits
the locale's default setting, if not changed (for example, english=sunday
and german=monday).

Inside the user settings and tab panel :guilabel:`Backend appearance`, a new dropdown
:guilabel:`First day of week in calendar popups` appears.

The setting is stored in both the persisted :sql:`be_users.uc` preference blob,
as well as on the JavaScript-persistence side.

Impact
======

Editors can now choose the first day of a week as a user preference, independent
from locale selection.

..  index:: Backend, NotScanned, ext:core

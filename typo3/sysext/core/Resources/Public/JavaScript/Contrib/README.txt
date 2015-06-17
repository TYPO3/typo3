This directory contains all packaged third-party frontend libraries needed
for the TYPO3 CMS Core. They are mostly managed via Grunt or have been adapted
to fit our needs.

Please make sure to never reference any file directly here, rather copy
a file needed in your own extension or reference it via RequireJS instead of
using the Path to this Contrib/ directory.

Libraries not handled by bower/Grunt:

- prototype.js
prototype.js cannot be handled via bower as it conflicts with RequireJS when
the sources are fetched via bower.

- bootstrap/bootstrap.js
Twitter Bootstrap 3 is not shipped as an AMD module, and has been adapted to be
wrapped as an AMD module called "bootstrap".

- typeahead.js
The typeahead.js AMD still has a bug that the AMD module is called wrongly, thus
it is changed manually by TYPO3 to be called "typeaheadjs", and used like that in
the Core.

Benni, March 2015.

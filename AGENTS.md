# Repository Guidelines

Guidance for coding agents working in the TYPO3 CMS Core monorepo. Personal or
machine-specific preferences do not belong here — put those in an untracked
`CLAUDE.local.md` (already gitignored).

## Context

- Main code locations: `typo3/sysext/`, `Build/Sources/`.
- Documentation: `typo3/sysext/core/Documentation/`, <https://docs.typo3.org>.
- Issue tracker: <https://forge.typo3.org> (Redmine). Query it through the JSON API
  with `curl -H "Accept: application/json"` and the **default curl user agent** —
  browser-like user agents hit the Anubis bot check. Useful endpoints:
  `issues/<id>.json` (add `?include=journals` for comments),
  `search.json?q=<query>`, `issues.json?project_id=27` (27 = TYPO3 Core).
- Code review: <https://review.typo3.org> (Gerrit), open changes at
  <https://review.typo3.org/q/project:Packages/TYPO3.CMS>.
- Contribution walkthrough for humans: `CONTRIBUTING.md` and
  <https://docs.typo3.org/core-contribution>.

## Working mode

- When a bug is reported or reproduced, **do not start by fixing it**. First write a
  test that reproduces it, then fix and prove it with that test passing. The only
  exception is a purely mechanical fix with no testable behaviour, such as a typo
  in a label, comment or documentation.
- Only add code comments when they add meaning; otherwise leave them out.
- Follow the TYPO3 Coding Guidelines (CGL), and run
  `PHP_CS_FIXER_IGNORE_ENV=1 ./Build/Scripts/cglFixMyCommit.sh` after committing.

## Project structure & modules

- `typo3/sysext/<ext>/` — the system extensions, each a Composer package
  (`typo3/cms-<ext>`) with its own `composer.json`, a PSR-4 namespace under
  `TYPO3\CMS\...` mapped in the root `composer.json`, and the same internal shape:
  `Classes/`, `Configuration/`, `Resources/{Private,Public}/`,
  `Tests/{Unit,Functional}/`, `ext_localconf.php`, `ext_tables.sql`, `Documentation/`.
- `Build/` — all tooling: `Scripts/` (test dispatcher + integrity checks), `phpunit/`,
  `phpstan/`, `php-cs-fixer/`, `Sources/` (frontend sources), `tests/` (Playwright
  specs and fixture extensions), `Gruntfile.js`, `package.json`.
- `index.php` — the single web entry point: `SystemEnvironmentBuilder::run()` →
  `Bootstrap::init()` → `Core\Http\Application`, falling back to
  `Install\Http\Application` when the full container cannot be built.
- `typo3conf/`, `typo3temp/`, `vendor/` — local/generated, not versioned.

Frontend sources live in `Build/Sources/TypeScript/<ext-key>/` and
`Build/Sources/Sass/`; the build compiles them into
`typo3/sysext/<ext>/Resources/Public/{JavaScript,Css}/`. **Never edit files under
`Resources/Public/JavaScript` or `Resources/Public/Css` — they are build output.**
Mind the naming shift: TypeScript folders use dashes (`rte-ckeditor`), sysexts use
underscores (`rte_ckeditor`).

## Commands

Everything runs through one container-based dispatcher,
`./Build/Scripts/runTests.sh` (podman by default, `-b docker` to switch). Do not
invoke `phpunit`, `phpstan`, `php-cs-fixer`, `npm` or `grunt` directly — the wrapper
supplies the PHP version, database service and bootstrap. `-h` is the authoritative,
always-current list of suites and options; the examples below are a curated subset.

```bash
# PHP tests (-p selects the PHP version; -h shows which ones this branch accepts)
CI=true ./Build/Scripts/runTests.sh -s unit
CI=true ./Build/Scripts/runTests.sh -s unit typo3/sysext/core/Tests/Unit/RegistryTest.php
CI=true ./Build/Scripts/runTests.sh -s unit -- --filter someTestMethodName
CI=true ./Build/Scripts/runTests.sh -s unitRandom       # random order, exposes test isolation bugs
CI=true ./Build/Scripts/runTests.sh -s functional -d sqlite typo3/sysext/core/Tests/Functional/Database
CI=true ./Build/Scripts/runTests.sh -s functional -d postgres -i 17   # also: mariadb, mysql
CI=true ./Build/Scripts/runTests.sh -s functional -c 3/13             # run chunk 3 of 13

# static analysis / style
./Build/Scripts/runTests.sh -s cgl                # fix; add -n for dry-run
./Build/Scripts/runTests.sh -s cglGit             # CGL of the last commit only
PHP_CS_FIXER_IGNORE_ENV=1 ./Build/Scripts/cglFixMyCommit.sh
./Build/Scripts/runTests.sh -s phpstan            # custom rules, baseline in Build/phpstan/
./Build/Scripts/runTests.sh -s checkIntegrityPhp  # registered PHP integrity rules
./Build/Scripts/runTests.sh -s listExceptionCodes # also reports duplicate/missing exception codes

# frontend
CI=true ./Build/Scripts/runTests.sh -s build      # TypeScript + Sass + assets
./Build/Scripts/runTests.sh -s unitJavascript     # web-test-runner
./Build/Scripts/runTests.sh -s lintTypescript     # add -- --fix
./Build/Scripts/runTests.sh -s lintScss
./Build/Scripts/runTests.sh -s npm -- ci

# end-to-end (Playwright)
./Build/Scripts/runTests.sh -s e2e
./Build/Scripts/runTests.sh -s e2e-install -d sqlite
./Build/Scripts/runTests.sh -s e2e-prepare        # boot an instance for manual poking
./Build/Scripts/runTests.sh -s e2e-browser        # same specs with the GUI on http://127.0.0.1:43837

# docs / integrity gates that CI enforces (more check*/lint* suites via -h)
./Build/Scripts/runTests.sh -s checkRst
./Build/Scripts/runTests.sh -s watchRst core interactive   # live RST preview / changelog wizard
./Build/Scripts/runTests.sh -s checkExtensionScannerRst
./Build/Scripts/runTests.sh -s lintServicesYaml
./Build/Scripts/runTests.sh -s lintYaml            # all YAML except Services.yaml
./Build/Scripts/runTests.sh -s checkIntegrityXliff # add -s normalizeXliff to fix .xlf formatting

# composer inside the container
./Build/Scripts/runTests.sh -s composer -- install
```

Functional tests default to SQLite, which is by far the fastest feedback loop; only
switch DBMS when the change touches SQL/Doctrine specifics.

Two options worth knowing: `-x` forwards xdebug to a listening IDE (port 9003, change
with `-y`), and `-u` updates the `typo3/core-testing-*` images — the first thing to try
when a suite fails in ways the code cannot explain.

## Architecture: how the pieces connect

These five mechanisms explain most of the codebase.

**1. Packages + bootstrap.** `Core\Package\PackageManager` discovers active
extensions; `Core\Core\Bootstrap` builds a Symfony DI container that is compiled and
cached. Extensions contribute via `Configuration/Services.yaml` (+ optional
`Services.php` for compiler passes). A second, minimal *failsafe* container exists for
the install tool / broken configuration, assembled from the handful of
`Classes/ServiceProvider.php` files (core, backend, install, fluid, dashboard) — code
that must work in the installer needs manual wiring there, autowiring does not apply.

**2. Dependency injection.** `Services.yaml` uses
`autowire: true, autoconfigure: true, public: false` with a `resource: '../Classes/*'`
sweep, so classes are autowired by default; only entries needing tags, factories or
non-shared behaviour are listed explicitly. Extension points are wired with *tagged
services* (e.g. `mfa.provider`, `softreference.parser`, `fal.file_renderer`) consumed
via `!tagged_iterator`. Prefer constructor injection —
`GeneralUtility::makeInstance()` is legacy and is actively being reduced.

Fluid ViewHelpers take part in DI as well: a compiler pass in
`fluid/Configuration/Services.php` makes every `ViewHelperInterface` public and
non-shared, so `ViewHelperResolver` resolves them through the container. Inject only
stateless singletons (never `ContentObjectRenderer`, Extbase's `UriBuilder`, `Context`
or other request-scoped objects), never from `static` methods, and remember the
failsafe container: a ViewHelper used in install-tool templates that gains required
constructor arguments must be wired by hand in `install/Classes/ServiceProvider.php`.

**3. PSR-15 request pipeline.** Each request type (`frontend`, `backend`) is a
middleware stack declared per extension in `Configuration/RequestMiddlewares.php`,
with `before`/`after` ordering keys resolved by `Http\MiddlewareStackResolver` and run
by `Http\MiddlewareDispatcher`. Backend routes come from
`Configuration/Backend/{Routes,AjaxRoutes,Modules}.php`; frontend routing is
site-configuration driven (`Core\Routing`, `Core\Site`).

**4. PSR-14 events.** Hooks are legacy; new extension points are events dispatched
through `Core\EventDispatcher`. Register listeners with the `#[AsEventListener]`
attribute (`Core\Attribute\AsEventListener`) or an `event.listener` service tag. When
replacing a hook, add both the event class and a `Feature-*.rst` changelog entry.

**5. TCA and the schema layer.** Table/field configuration lives in
`Configuration/TCA/<table>.php` (own tables) and
`Configuration/TCA/Overrides/<table>.php` (additions to foreign tables). Do not read
`$GLOBALS['TCA']` in new code: `Core\Schema\TcaSchemaFactory` turns TCA into typed
`TcaSchema` / field / capability / relation objects, and that API is what new code
should consume. `Core\DataHandling\DataHandler` remains the central write path for
records (versioning, workspaces, localization).

Extbase (`typo3/sysext/extbase`) and Fluid (`typo3/sysext/fluid`, wrapping
`typo3fluid/fluid`) sit on top as the MVC/templating layer; several sysexts (belog,
beuser, form, indexed_search, …) are Extbase-based backend modules.

## Coding style

- PHP: PSR-12, 4-space indents, `declare(strict_types=1);`, one class per file under
  `Classes/` matching the namespace, and the standard GPL file header (enforced by
  `-s cglHeader`).
- JS/TS/SCSS: 2-space indents, ESLint and Stylelint configs in `Build/`.
- TypoScript/TSconfig: consistent casing and indentation, under `Configuration/` as in
  existing extensions.
- `.editorconfig` is authoritative for indentation per file type (JSON uses tabs, RST
  and Markdown wrap at 80 columns).
- Every thrown exception carries a **unique** integer code — the unix timestamp of the
  moment it was written, e.g.
  `throw new SiteNotFoundException('No site found …', 1521668882);`. Never copy a code
  from existing code: `-s listExceptionCodes` runs
  `Build/Scripts/duplicateExceptionCodeCheck.sh`, which reports duplicate and missing
  codes.
- Deprecations are marked twice: a docblock line in the form
  `@deprecated since v<current major>, will be removed in v<next major>` and a
  `trigger_error('…', E_USER_DEPRECATED)` in the deprecated code path. Deprecate in
  one major version, remove in the next, and never break API on a release branch.
  See the changelog section for the required RST and extension-scanner entries.
- Labels live in `Resources/Private/Language/*.xlf`. Only the English source files are
  edited here — translations are maintained by the translation teams on Crowdin and are
  not part of this repository.
- Do not silence new static-analysis findings in `Build/phpstan/phpstan-baseline.neon`;
  the baseline is meant to shrink. Regenerate it (`-s phpstanGenerateBaseline`) only
  after a PHPStan update.

## Testing guidelines

- Unit tests extend `TYPO3\TestingFramework\Core\Unit\UnitTestCase`; functional tests
  extend `...\Core\Functional\FunctionalTestCase` and resolve services with
  `$this->get(SomeClass::class)`.
- Test classes are `final`; use PHPUnit attributes (`#[Test]`, `#[DataProvider]`), not
  docblock annotations. Unit/functional files end in `Test.php`.
- Fixture extensions go under `Tests/Functional/Fixtures/Extensions/`; DB fixtures are
  CSV files loaded with `importCSVDataSet()`.
- Each functional test case gets a real TYPO3 instance provisioned below
  `typo3temp/var/tests/`, which is why the suite is slow and why leftovers there can be
  deleted at any time.
- `importCSVDataSet()` and the `pathsToProvideInTestInstance` keys do **not** resolve
  `EXT:` paths — they are plain file lookups, so use `__DIR__`-relative paths.
- Prefer small, focused tests. Filter with `-- --filter <TestName>`.

## Changelog (RST) requirements

User-facing changes need an entry in
`typo3/sysext/core/Documentation/Changelog/<version>/`, where `<version>` is the
release currently in development on this branch. The authoritative source is
`\TYPO3\CMS\Core\Information\Typo3Version`: its `BRANCH` constant is the changelog
folder name and the current major for deprecation docblocks, and `VERSION` (e.g.
`15.0.0-dev`) names the exact release under development. The `branch-alias` in
`composer.json` mirrors the same information. Name it
`{Breaking,Deprecation,Feature,Important}-<issue>-<ShortDescription>.rst`.

- Scan neighbouring files in `Changelog/*/` and follow `Changelog/Howto.rst`.
- Start with `.. include:: /Includes.rst.txt`, then a reference label, then a headline
  with an active phrasing whose `===` underline is **exactly** as long as the headline.
- Always a Description section and a closing `.. index::`. Sections are type-dependent:
  everything except `Important` has "Impact"; `Deprecation` and `Breaking` additionally
  need "Affected installations" and "Migration".
- Deprecations and breaking changes the extension scanner should detect must also be
  referenced from `typo3/sysext/install/Configuration/ExtensionScanner/Php/` —
  `-s checkExtensionScannerRst` verifies the link.

## Commits & review

**Only create or modify commits when explicitly asked.**

- Reviews happen in Gerrit, not GitHub; the GitHub repo is a mirror and pull requests
  are moved into Gerrit automatically.
- Run `composer gerrit:setup` once to install the commit-msg hook that adds the
  `Change-Id` line. Omit that line when writing a message — the hook adds it. When
  amending a commit that already has one, keep it byte-identical.
- Subject tags: `[BUGFIX]`, `[FEATURE]`, `[TASK]`, `[DOCS]`, `[SECURITY]`. Imperative,
  concise subject; body explains rationale and impact. **No line of the message may
  reach 72 characters** — the hook rejects the commit, footer trailers included.
- The commit-msg hook validates the footer and complains if it is incomplete. Use this
  order:

  ```
  Resolves: #12345
  Releases: main, 13.4
  Signed-off-by: Your Name <you@example.org>
  Change-Id: I0123456789abcdef0123456789abcdef01234567
  ```

  `Resolves:` (or `Fixes:`) with a Forge issue number and `Releases:` are both
  mandatory; use `Related: #12345` for further issues.
- **Every commit needs its own Forge issue.** Do not reuse one issue number across the
  commits of a series, and do not squash separate steps into one commit to avoid
  opening issues: in Gerrit every commit is reviewed, merged and backported on its
  own, and `Resolves:` is what closes the issue. The number is needed in two places —
  the trailer and the RST changelog filename — so look it up (or ask for it) before
  writing either. Prepare code and tests first, then get the number.
- `Releases:` lists the branches a change is meant for. Features go to `main` only;
  bugfixes are backported to the maintained release branches. Push the change for the
  branch that needs review first — normally `main` — with
  `git push origin HEAD:refs/for/main`, and submit the backports once that patch is
  agreed. Pushing the same change to several branches in parallel wastes review time
  and usually has to be redone.
- Sign off every commit — `git commit -s` appends the `Signed-off-by:` trailer, or set
  `git config format.signOff true` to get it automatically. It certifies that you wrote
  the patch, or otherwise have the right to submit it under the project's licence
  (Developer Certificate of Origin). The hook preserves the trailer and only ignores it
  when computing the `Change-Id`.
- Do not credit tooling or assistants in commit messages.
- Before pushing: unit tests, functional tests, CGL and the JS build should be green.

## Security & configuration

- Never commit secrets; follow `SECURITY.md` for reporting vulnerabilities, and contact
  security@typo3.org for potential security issues rather than filing them publicly.
- Local config and runtime data belong in `typo3conf/` and `typo3temp/` (not
  versioned). Use the containers via `Build/Scripts/runTests.sh` for a reproducible
  environment.

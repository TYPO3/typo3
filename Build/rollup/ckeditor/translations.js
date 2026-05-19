import { join, resolve } from 'path';
import { existsSync, readdirSync } from 'fs';
import { pathToFileURL } from 'url';

/**
 * This script assembles full locales from all CKEditor 5 packages that are
 * bundled into EXT:rte_ckeditor (see the package list in ../ckeditor.js).
 *
 * Since CKEditor 5 v48, translations no longer ship as gettext `.po` files,
 * but as prebuilt ES modules in `dist/translations/<language>.js`, each
 * exporting `{ [language]: { dictionary, getPluralForm } }`.
 *
 * The dictionaries of all bundled packages are merged per language and
 * emitted in the established `window.CKEDITOR_TRANSLATIONS` format, which
 * is still supported by the CKEditor 5 translation service and is loaded
 * as a side effect module, see
 * \TYPO3\CMS\RteCKEditor\Form\Element\RichTextElement::getLanguageIsoCodeOfContent()
 */

function translationModulesPerLanguage(packages) {
  const languages = new Map();

  for (const pkg of packages) {
    const translationsDir = resolve(`./node_modules/@ckeditor/${pkg}/dist/translations`);
    if (!existsSync(translationsDir)) {
      continue;
    }
    for (const fileName of readdirSync(translationsDir)) {
      if (!fileName.endsWith('.js') || fileName.endsWith('.umd.js')) {
        continue;
      }
      const language = fileName.replace(/\.js$/, '');
      if (!languages.has(language)) {
        languages.set(language, []);
      }
      languages.get(language).push(join(translationsDir, fileName));
    }
  }

  return languages;
}

async function compileLanguage(language, modulePaths) {
  const dictionary = {};
  let getPluralForm;

  for (const modulePath of modulePaths) {
    const { default: translations } = await import(pathToFileURL(modulePath).href);
    Object.assign(dictionary, translations[language]?.dictionary);
    getPluralForm = getPluralForm ?? translations[language]?.getPluralForm;
  }

  // Stringify translations and remove unnecessary `""` around property names.
  const stringifiedTranslations = JSON.stringify(dictionary)
    .replace(/"([\w_]+)":/g, '$1:');

  // `getPluralForm` is defined as an object method shorthand
  // (`getPluralForm(n) {…}`) in the CKEditor 5 translation modules, which is
  // not a valid standalone expression – normalize to a function expression.
  const getPluralFormExpression = getPluralForm ?
    getPluralForm.toString().replace(/^[\w$]+\s*\(/, 'function(') :
    null;

  return (
    '(function(d){' +
    `	const l = d['${language}'] = d['${language}'] || {};` +
    '	l.dictionary=Object.assign(' +
    '		l.dictionary||{},' +
    `		${stringifiedTranslations}` +
    '	);' +
    (getPluralFormExpression ? `l.getPluralForm=${getPluralFormExpression};` : '') +
    '})(window.CKEDITOR_TRANSLATIONS||(window.CKEDITOR_TRANSLATIONS={}));'
  );
}

/**
 * Helper function to build rollup bundling configuration for all existing
 * CKEditor translations
 */
export const translations = (packages) => Array.from(translationModulesPerLanguage(packages))
  .map(([language, modulePaths]) => ({
    language,
    modulePaths,
    // Not a glob(!), just a path for the human readable output,
    // translation content is provided by the content provider plugin below
    input: `@ckeditor/*/dist/translations/${language}.js`,
    virtual: `\0virtual:translations/${language}.js`,
  }))
  .map(({ language, modulePaths, input, virtual }) => ({
    input,
    output: {
      compact: true,
      file: `../typo3/sysext/rte_ckeditor/Resources/Public/Contrib/translations/${language}.js`,
      format: 'es',
    },
    plugins: [
      {
        name: 'content provider',
        load: (id) => id === virtual ? compileLanguage(language, modulePaths) : null,
        resolveId: (id) => id === input ? virtual : null,
      }
    ],
  }))

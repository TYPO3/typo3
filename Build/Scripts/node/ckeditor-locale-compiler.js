const { resolve, join } = require('path')
const { readdirSync, existsSync, readFileSync, mkdirSync, writeFileSync } = require('fs')
const PO = require('pofile')

/**
 * This script assembles full locales from all plugins found in node_modules/@ckeditor/
 *
 * Parts of this script are based on @ckeditor/ckeditor5-dev-translations/lib/multiplelanguagetranslationservice.js
 *
 * Subject to following license terms:
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see @ckeditor/ckeditor5-dev-translations/LICENSE.md.
 */

const _languages = new Set();
const _translationDictionaries = {};
const _pluralFormsRules = {};

function loadPackage(packagePath) {
  if (!existsSync(packagePath)) {
    return;
  }

  const translationPath = getTranslationPath(packagePath);

  if (!existsSync(translationPath)) {
    return;
  }

  for (const fileName of readdirSync(translationPath)) {
    if (!fileName.endsWith('.po') ) {
      continue;
    }

    const language = fileName.replace( /\.po$/, '' );
    const pathToPoFile = join(translationPath, fileName);

    _languages.add(language);
    loadPoFile(language, pathToPoFile);
  }
}

function getTranslationPath(packagePath) {
  return join(packagePath, 'lang', 'translations');
}

function loadPoFile(language, pathToPoFile) {
  if (!existsSync(pathToPoFile)) {
    return;
  }

  const parsedTranslationFile = PO.parse(readFileSync(pathToPoFile, 'utf-8'));

  _pluralFormsRules[language] = _pluralFormsRules[language] || parsedTranslationFile.headers['Plural-Forms'];

  if (!_translationDictionaries[language]) {
    _translationDictionaries[language] = {};
  }

  const dictionary = _translationDictionaries[language];

  for (const item of parsedTranslationFile.items) {
    dictionary[item.msgid] = item.msgstr;
  }
}

function getTranslationAssets(outputDirectory, languages) {
  return languages.map(language => {
    const outputPath = join(outputDirectory, `${language}.js`);

    if ( !_translationDictionaries[language]) {
      return { outputBody: '', outputPath };
    }

    const translations = getTranslations(language);

    // Examples of plural forms:
    // pluralForms="nplurals=3; plural=(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<12 || n%100>14) ? 1 : 2)"
    // pluralForms="nplurals=3; plural=n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<12 || n%100>14) ? 1 : 2"

    /** @type {String} */
    const pluralFormsRule = _pluralFormsRules[language];

    let pluralFormFunction;


    if (!pluralFormsRule) {

    } else {
      const pluralFormFunctionBodyMatch = pluralFormsRule.match(/(?:plural=)(.+)/);

      // Add support for ES5 - this function will not be transpiled.
      pluralFormFunction = `function(n){return ${pluralFormFunctionBodyMatch[1]};}`;
    }

    // Stringify translations and remove unnecessary `""` around property names.
    const stringifiedTranslations = JSON.stringify(translations)
      .replace(/"([\w_]+)":/g, '$1:');

    const outputBody = (
      '(function(d){' +
      `	const l = d['${language}'] = d['${language}'] || {};` +
      '	l.dictionary=Object.assign(' +
      '		l.dictionary||{},' +
      `		${stringifiedTranslations}` +
      '	);' +
      (pluralFormFunction ? `l.getPluralForm=${pluralFormFunction};` : '' ) +
      '})(window.CKEDITOR_TRANSLATIONS||(window.CKEDITOR_TRANSLATIONS={}));'
    );

    return { outputBody, outputPath };
  });
}

function getTranslations(language) {
  const langDictionary = _translationDictionaries[language];
  const translatedStrings = {};

  for ( const messageId of Object.keys(langDictionary)) {
    const translatedMessage = langDictionary[messageId];

    // Register first form as a default form if only one form was provided.
    translatedStrings[messageId] = translatedMessage.length > 1 ?
      translatedMessage :
      translatedMessage[0];
  }

  return translatedStrings;
}

const ckeditorNamespacePath = resolve('./node_modules/@ckeditor/');
for (const packagePath of readdirSync(ckeditorNamespacePath)) {
  loadPackage(`${ckeditorNamespacePath}/${packagePath}/`);
}

if (!existsSync('./ckeditorLocales/')) {
  mkdirSync('./ckeditorLocales/');
}

const assets = getTranslationAssets('./ckeditorLocales/', Array.from(_languages));

for (const asset of assets) {
  if (asset.outputBody !== undefined) {
    writeFileSync(asset.outputPath, asset.outputBody);
  }
}

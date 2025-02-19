import { resolve, dirname } from 'path';
import { existsSync } from 'fs';
import { parseAst } from 'rollup/parseAst';
import { walk } from 'estree-walker';
import MagicString from 'magic-string';

const suffix = '.js';

export const isContrib = (importValue) => {
  try {
    const fileName = import.meta.resolve(importValue);
    return existsSync(new URL(fileName));
  } catch (e) {
    return false;
  }
};

export const mapImport = (targetModule, context) => {
  if (
    targetModule.charAt(0) === '.' &&
    (context.indexOf('node_modules/lit') !== -1 || context.indexOf('node_modules/@lit/') !== -1 || context.indexOf('node_modules/@lit-labs/') !== -1)
  ) {
    return resolve(dirname(context), targetModule).replace(/^.*\/node_modules\//g, '');
  }

  if (isContrib(targetModule)) {
    return targetModule;
  }

  if (targetModule.charAt(0) === '.') {
    targetModule = resolve(dirname(context), targetModule)
      .replace(/^.*\/Build\/JavaScript\/([^\/]+?)/, (match, pkgname) => '@typo3/' + pkgname);
  }

  return targetModule + suffix;
};

const importExportTypes = [ 'ImportDeclaration', 'ImportExpression', 'ExportNamedDeclaration', 'ExportDefaultDeclaration', 'ExportAllDeclaration' ];
export const mapImports = {
  name: 'map imports',
  transform(source, file) {
    const ms = new MagicString(source);
    walk(parseAst(source), {
      enter(node) {
        if (importExportTypes.includes(node.type) && node.source?.type === 'Literal') {
          ms.update(node.source.start, node.source.end, JSON.stringify(mapImport(node.source.value, file)))
        }
      },
    });
    return { code: ms.toString(), map: ms.generateMap({ file, includeContent: true, hires: true }) }
  }
};

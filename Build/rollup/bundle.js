import terser from '@rollup/plugin-terser';
import { createRequire } from 'node:module';
import { readFileSync } from 'fs';
import { resolve, dirname } from 'path';

export function bundle(packageName, options = {}) {
  const { src, extension, imports, external } = {
    src: null,
    extension: 'core',
    imports: {},
    external: [],
    ...options
  }

  return {
    input: src ?? resolveEntryPoint(packageName),
    output: {
      file: `../typo3/sysext/${extension}/Resources/Public/JavaScript/Contrib/${packageName.replace(/\.js$/, 'js')}.js`,
      format: 'es',
    },
    external,
    plugins: [
      terser({ ecma: 8 }),
      mapImports(imports),
      resolveRelativeImports,
    ],
  }
}

export function resolveEntryPoint(packageName) {
  const packagePath = `node_modules/${packageName}/`
  const packageJson = JSON.parse(readFileSync(packagePath + 'package.json', 'utf8'))
  const entryPoint = resolvePackageExport(packageJson['exports']) ?? packageJson['module'] ?? packageJson['main'] ?? null
  if (entryPoint === null) {
    throw new Error(`Could not find entry point for ${packageName}`)
  }
  return packagePath + entryPoint
}

export function mapImports(imports) {
  return {
    name: 'map imports',
    resolveId: (source, importer) => {
      return imports[source] ?? null
    }
  }
}

export const resolveRelativeImports = {
  name: 'resolve relative imports',
  resolveId: (source, importer) => {
    if (source.startsWith('.') && importer) {
      return createRequire(import.meta.url).resolve(resolve(dirname(importer), source))
    }
    return null
  }
}

/* Poor mans https://nodejs.org/api/packages.html#main-entry-point-export parser */
export function resolvePackageExport(exports) {
  if (exports === null || exports === undefined) {
    return null
  }
  if (typeof exports === 'string') {
    return exports
  }
  if (typeof exports !== 'object') {
    throw new Error('Could not parse package exports')
  }
  if ('.' in exports) {
    return resolvePackageExport(exports['.'])
  }
  if ('import' in exports) {
    return resolvePackageExport(exports['import'])
  }
  if ('module-sync' in exports) {
    return resolvePackageExport(exports['module-sync'])
  }
  if ('default' in exports) {
    return resolvePackageExport(exports['default'])
  }
  throw new Error('Could not parse package exports')
}

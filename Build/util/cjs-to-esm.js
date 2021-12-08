const provideImports = (imports) => {
  const src = []
  imports.forEach(module => {
    const variableName = '__import_' + module.replace('/', '_').replace('@','').replace('-', '_')
    src.push('import ' + variableName + ' from "' + module + '"')
  })

  src.push('let require = (name) => {');
  src.push('  switch (name) {');
  imports.forEach(module => {
    const variableName = '__import_' + module.replace('/', '_').replace('@','').replace('-', '_')
    src.push('  case "' + module + '":');
    src.push('    return ' + variableName)
  })

  src.push('  }');
  src.push('  throw new Error("module " + name + " missing")')
  src.push('}');

  return src.join('\n');
}

const cjsToEsm = (source, imports, prefix, suffix) => {
  imports = imports || [];
  prefix = prefix || ''
  suffix = suffix || ''
  source = source.replace(/\/\/# sourceMappingURL=[^ ]+/, '');
  // Using a user-defined object type to provide a `this` context
  // to prevent static analysis tools (like rollup) from complaining
  // about `this` in top-level context. (`this` is often used by
  // UMD wrappers if module.exports is not defined, so it's fine to refer
  // to this dummy object).
  const code = [
    'export default (new function() {',
    '  const module = { exports: {} }, exports = module.exports, define = null;',
    source,
    '  this.__default_export = module.exports;' + suffix,
    '}).__default_export;'
  ];

  if (imports.length > 0) {
    code.unshift(provideImports(imports));
  }

  if (prefix) {
    code.unshift(prefix);
  }

  return code.join('\n');
}

exports.cjsToEsm = cjsToEsm;

const cjsToEsm = (source, prefix) => {
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
    '  this.__default_export = module.exports;',
    '}).__default_export;'
  ];

  if (prefix) {
    code.unshift(prefix);
  }

  return code.join('\n');
}

exports.cjsToEsm = cjsToEsm;

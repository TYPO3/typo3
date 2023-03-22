/* eslint-env node, commonjs */
/* eslint-disable @typescript-eslint/no-var-requires */
module.exports = function (config) {
  require('./karma.conf.js')(config);
  config.set({
    browsers: ['ChromeHeadlessCustom'],
    customLaunchers: {
      ChromeHeadlessCustom: {
        base: 'ChromeHeadless',
        flags: ['--no-sandbox']
      }
    },
  });
};

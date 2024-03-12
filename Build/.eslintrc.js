const path = require('path');

module.exports = {
  "root": true,
  "env": {
    "browser": true,
    "es6": true
  },
  "parser": "@typescript-eslint/parser",
  "parserOptions": {
    "project": [path.resolve(__dirname, "./tsconfig.eslint.json")]
  },
  "plugins": [
    "@typescript-eslint",
    "lit",
    "wc"
  ],
  "settings": {
    "wc": {
      "elementBaseClasses": [
        "LitElement"
      ]
    }
  },
  "extends": [
    "eslint:recommended",
    "plugin:@typescript-eslint/recommended",
    "plugin:wc/recommended",
    "plugin:lit/recommended"
  ],
  "rules": {
    "@typescript-eslint/indent": ["error", 2],
    "@typescript-eslint/no-inferrable-types": "off", // we want to keep explicit type casting
    "@typescript-eslint/ban-types": "error",
    "@typescript-eslint/no-explicit-any": "off", // too many warnings/errors for now, needs be fixed step by step
    "@typescript-eslint/no-this-alias": "error",
    "@typescript-eslint/no-unused-vars": "error",
    "@typescript-eslint/member-ordering": "error",
    "@typescript-eslint/prefer-readonly": "error",
    "@typescript-eslint/naming-convention": [
      "error",
      {
        "selector": "class",
        "format": ["PascalCase"]
      },
      {
        "selector": "typeLike",
        "format": ["PascalCase"]
      }
    ],
    "@typescript-eslint/no-array-delete": "error",
    "curly": "error",
    "default-case": "error",
    "dot-notation": "error",
    "eol-last": "error",
    "guard-for-in": "error",
    "lit/no-duplicate-template-bindings": "error",
    "lit/no-native-attributes": "error",
    "lit/no-invalid-escape-sequences": "error",
    "lit/no-legacy-imports": "error",
    "lit/no-useless-template-literals": "error",
    "lit/prefer-nothing": "error",
    "no-bitwise": "off",
    "no-caller": "error",
    "no-debugger": "error",
    "no-empty": "error",
    "no-empty-function": ["error", {
        "allow": ["constructors"]
    }],
    "no-eval": "error",
    "no-fallthrough": "error",
    "no-new-wrappers": "error",
    "no-unused-labels": "error",
    "no-multi-spaces": "error",
    "no-var": "error",
    "no-case-declarations": "off",
    "no-restricted-properties": [
      "error",
      {
        "object": "window",
        "property": "jQuery",
        "message": "Use `import jQuery from 'jquery'` instead."
      },
      {
        "object": "window",
        "property": "$",
        "message": "Use `import $ from 'jquery'` instead."
      }
    ],
    "no-restricted-globals": [
      "error",
      {
        "name": "jQuery",
        "message": "Use `import jQuery from 'jquery'` instead."
      },
      {
        "name": "$",
        "message": "Use `import $ from 'jquery'` instead."
      }
    ],
    "object-curly-spacing": [
      "error",
      "always"
    ],
    "quotes": [
      "error",
      "single"
    ],
    "radix": "error",
    "semi": "off",
    "space-infix-ops": "error",
    "wc/no-constructor-params": "error",
    "wc/no-typos": "error",
    "wc/require-listener-teardown": "error"
  }
}

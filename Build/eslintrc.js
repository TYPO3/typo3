module.exports = {
  "env": {
    "browser": true,
    "es6": true
  },
  "parser": "@typescript-eslint/parser",
  "parserOptions": {
    "project": "tsconfig.json",
    "sourceType": "module"
  },
  "plugins": [
    "@typescript-eslint"
  ],
  "rules": {
    "@typescript-eslint/class-name-casing": "error",
    "@typescript-eslint/indent": ["error", 2],
    "@typescript-eslint/interface-name-prefix": "off",
    "@typescript-eslint/member-ordering": ["error", {
      "default": [
        "public-field",
        "protected-field",
        "private-field",
        'public-static-method',
        'protected-static-method',
        'private-static-method',
        "constructor",
        "public-instance-method",
        "protected-instance-method",
        "private-instance-method"
      ]
    }],
    "@typescript-eslint/no-explicit-any": "off",
    "@typescript-eslint/no-require-imports": "off",
    "@typescript-eslint/no-unused-vars": "off",
    "@typescript-eslint/no-var-requires": "off",
    "@typescript-eslint/quotes": ["error", "single"],
    "@typescript-eslint/type-annotation-spacing": "error",
    "@typescript-eslint/typedef": ["error", {
        parameter: true,
        propertyDeclaration: true,
        memberVariableDeclaration: false
    }],
    "curly": "error",
    "default-case": "error",
    "dot-notation": "error",
    "eol-last": "error",
    "guard-for-in": "error",
    "no-bitwise": "off",
    "no-caller": "error",
    "no-debugger": "error",
    "no-empty": "error",
    "no-empty-function": "error",
    "no-eval": "error",
    "no-fallthrough": "error",
    "no-new-wrappers": "error",
    "no-unused-labels": "error",
    "no-unused-vars": "off",
    "no-var": "error",
    "quotes": "off",
    "radix": "error",
    "semi": "off"
  }
};

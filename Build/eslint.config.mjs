import stylistic from "@stylistic/eslint-plugin";
import typescriptEslint from "@typescript-eslint/eslint-plugin";
import lit from "eslint-plugin-lit";
import wc from "eslint-plugin-wc";
import tsParser from "@typescript-eslint/parser";
import path from "node:path";
import {fileURLToPath} from "node:url";
import js from "@eslint/js";
import {FlatCompat} from "@eslint/eslintrc";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const compat = new FlatCompat({
  baseDirectory: __dirname,
  recommendedConfig: js.configs.recommended,
  allConfig: js.configs.all
});

export default [...compat.extends(
  "eslint:recommended",
  "plugin:@typescript-eslint/recommended",
  "plugin:wc/recommended",
  "plugin:lit/recommended",
), {
  plugins: {
    "@stylistic": stylistic,
    "@typescript-eslint": typescriptEslint,
    lit,
    wc,
  },

  languageOptions: {
    parser: tsParser,
    ecmaVersion: 5,
    sourceType: "script",

    parserOptions: {
      project: [
        path.resolve(__dirname, "./tsconfig.eslint.json")
      ],
    },
  },

  settings: {
    wc: {
      elementBaseClasses: ["LitElement"],
    },
  },

  rules: {
    "@stylistic/indent": ["error", 2],
    "@typescript-eslint/no-inferrable-types": "off",
    "@typescript-eslint/no-restricted-types": "error",
    "@typescript-eslint/no-unsafe-function-type": "error",
    "@typescript-eslint/no-wrapper-object-types": "error",
    "@typescript-eslint/no-explicit-any": "off",
    "@typescript-eslint/no-this-alias": "error",
    "@typescript-eslint/no-unused-vars": "error",
    "@typescript-eslint/member-ordering": "error",
    "@typescript-eslint/prefer-readonly": "error",
    "@typescript-eslint/prefer-string-starts-ends-with": "error",

    "@typescript-eslint/naming-convention": ["error", {
      selector: "class",
      format: ["PascalCase"],
    }, {
      selector: "typeLike",
      format: ["PascalCase"],
    }],

    "@typescript-eslint/no-array-delete": "error",
    "@typescript-eslint/restrict-plus-operands": "error",
    curly: "error",
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
      allow: ["constructors"],
    }],

    "no-eval": "error",
    "no-fallthrough": "error",
    "no-new-wrappers": "error",
    "no-unused-labels": "error",
    "no-multi-spaces": "error",
    "no-var": "error",
    "no-case-declarations": "off",

    "no-restricted-properties": ["error", {
      object: "window",
      property: "jQuery",
      message: "Use `import jQuery from 'jquery'` instead.",
    }, {
      object: "window",
      property: "$",
      message: "Use `import $ from 'jquery'` instead.",
    }],

    "no-restricted-globals": ["error", {
      name: "jQuery",
      message: "Use `import jQuery from 'jquery'` instead.",
    }, {
      name: "$",
      message: "Use `import $ from 'jquery'` instead.",
    }],

    "object-curly-spacing": ["error", "always"],
    quotes: ["error", "single"],
    radix: "error",
    semi: "off",
    "space-infix-ops": "error",
    "wc/no-constructor-params": "error",
    "wc/no-typos": "error",
    "wc/require-listener-teardown": "error",
  },
}, {
  files: ["**/*-test.ts"],

  rules: {
    "no-unused-expressions": "off",
    "@typescript-eslint/no-unused-expressions": "off",
  },
}];

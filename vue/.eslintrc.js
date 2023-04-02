/* eslint-disable */
module.exports = {
    root: true,
    parser: 'vue-eslint-parser',
    parserOptions: {
        parser: '@typescript-eslint/parser',
        sourceType: 'module',
        ecmaVersion: 'latest',
        ecmaFeatures: {
            jsx: true,
        },
    },
    plugins: ['editorconfig', '@typescript-eslint'],
    extends: [
        'eslint:recommended',
        'plugin:editorconfig/all',
        'plugin:@typescript-eslint/eslint-recommended',
        'plugin:@typescript-eslint/recommended',
        'plugin:vue/vue3-essential',
        'prettier',
    ],
    globals: {
        M: true,
        Y: true,
    },
    rules: {
        'no-undef': 'off',
        'no-console': 1,
        'no-prototype-builtins': 'off',
        'vue/multi-word-component-names': 'off',
        'vue/script-indent': [
            'error',
            4,
            {
                baseIndent: 0,
                switchCase: 1,
            },
        ],
        'editorconfig/indent': 'off',
    },
};

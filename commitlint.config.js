export default {
    extends: ['@commitlint/config-conventional'],
    rules: {
        'type-enum': [
            2,
            'always',
            ['feat', 'fix', 'chore', 'test', 'docs', 'refactor', 'perf', 'style', 'build', 'ci'],
        ],
        'subject-max-length': [2, 'always', 100],
        'body-max-line-length': [1, 'always', 120],
    },
};

module.exports = {
    verbose: true,
    testMatch: [
        '**/pub_src/__test__/**/*.test.js',
    ],
    transform: {
        '^.+\\.[t|j]sx?$': 'babel-jest'
    },
};

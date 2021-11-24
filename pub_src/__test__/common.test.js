const {
    objCmp,
    numCmp,
    dateCmp,
} = require('../common');

test('objCmp("a", "b") == -1', () => {
    expect(objCmp('a', 'b')).toBe(-1);
});

test('objCmp("abc", "abc") == 0', () => {
    expect(objCmp('abc', 'abc')).toBe(0);
});

test('objCmp("10", "2") == -1', () => {
    expect(objCmp('10', '2')).toBe(-1);
});

test('numCmp("10", "2") == 1', () => {
    expect(numCmp('10', '2')).toBe(1);
});

test('dateCmp("December 17, 1995 03:24:00", "1995-12-17T03:24:00") == 0', () => {
    expect(dateCmp('December 17, 1995 03:24:00', '1995-12-17T03:24:00')).toBe(0);
});

test('"abc".capitalize() == "Abc"', () => {
    expect('abc'.capitalize()).toBe('Abc');
});

test('"<a>".html() == "&#60;a&#62;"', () => {
    expect('<a>'.html()).toBe('&#60;a&#62;');
});

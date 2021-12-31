import {
    objCmp,
    numCmp,
    dateCmp,
    capitalize,
    html,
    textBuilder,
} from '../ts/common';

test('objCmp', () => {
    expect(objCmp('a', 'b')).toBe(-1);
    expect(objCmp('abc', 'abc')).toBe(0);
    expect(objCmp('10', '2')).toBe(-1);
});

test('numCmp', () => {
    expect(numCmp('10', '2')).toBe(1);
});

test('dateCmp', () => {
    expect(dateCmp('December 17, 1995 03:24:00', '1995-12-17T03:24:00')).toBe(0);
});

test('capitalize', () => {
    expect(capitalize('abc')).toBe('Abc');
});

test('html', () => {
    expect(html('<a>')).toBe('&#60;a&#62;');
});

test('textBuilder', () => {
    const p = textBuilder();
    p('abc');
    p('def');
    p('ghi');
    expect(p.text()).toBe("abc\ndef\nghi\n");
});

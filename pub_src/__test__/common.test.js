import {
    objCmp,
    numCmp,
    dateCmp,
    capitalize,
    html,
    textBuilder,
    timeStr,
    zhCmp,
    zhToNum,
} from '../ts/common';

test('objCmp', () => {
    expect(objCmp('a', 'b')).toBe(-1);
    expect(objCmp('abc', 'abc')).toBe(0);
    expect(objCmp('10', '2')).toBe(-1);
});

test('numCmp', () => {
    expect(numCmp('10', '2')).toBe(1);
});

test('zhToNum', () => {
    expect(zhToNum('零')).toBe(0);
    expect(zhToNum('一')).toBe(1);
    expect(zhToNum('二')).toBe(2);
    expect(zhToNum('三')).toBe(3);
    expect(zhToNum('四')).toBe(4);
    expect(zhToNum('五')).toBe(5);
    expect(zhToNum('六')).toBe(6);
    expect(zhToNum('七')).toBe(7);
    expect(zhToNum('八')).toBe(8);
    expect(zhToNum('九')).toBe(9);
    expect(zhToNum('十')).toBe(10);
    expect(zhToNum('十一')).toBe(11);
    expect(zhToNum('二十')).toBe(20);
    expect(zhToNum('二十一')).toBe(21);
    expect(zhToNum('九十九')).toBe(99);
});

test('zhCmp', () => {
    expect(zhCmp('我', '你')).toBe(1);
    expect(zhCmp('2', '10')).toBe(-1);
    expect(zhCmp('一', '二')).toBe(-1);
    expect(zhCmp('甲十一', '甲二十')).toBe(-1);
});

test('dateCmp', () => {
    expect(dateCmp('December 17, 1995 03:24:00', '1995-12-17T03:24:00')).toBe(0);
});

test('capitalize', () => {
    expect(capitalize('abc')).toBe('Abc');
});

test('timeStr', () => {
    //expect(timeStr(0)).toBe('1970/01/01 08:00:00');
    //expect(timeStr('10')).toBe('1970/01/01 08:00:10');
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

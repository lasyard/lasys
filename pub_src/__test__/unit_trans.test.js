import {
    UnitTrans
} from '../ts/unit_trans';

test('sizeStr', () => {
    expect(UnitTrans.sizeStr(1024)).toBe('1 kiB');
    expect(UnitTrans.sizeStr(1024 * 1024 * 1.52)).toBe('1.52 MiB');
    expect(UnitTrans.sizeStr(1024 * 1024 * 1024 * 3.484)).toBe('3.48 GiB');
});

test('timeStr', () => {
    expect(UnitTrans.timeStr(60 + 15)).toBe('1 m 15 s');
    expect(UnitTrans.timeStr(60 * 60 + 15)).toBe('1 h 0 m 15 s');
    expect(UnitTrans.timeStr(24 * 60 * 60 + 60 * 60)).toBe('1 d 1 h');
});

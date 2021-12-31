export class UnitTrans {
    private static readonly timeSeries: [string, number][]
        = [['s', 60], ['m', 60], ['h', 24], ['d', 10000]];
    private static readonly sizeSeries: [string, number][]
        = [['', 1024], ['k', 1024], ['M', 1024], ['G', 1024], ['T', 1024], ['P', 10000]];

    private static transData(d: number, series: [string, number][]) {
        let str = '';
        for (const s of series) {
            const unit = s[0];
            const scale = s[1];
            if (d < scale) {
                str = d + ' ' + unit + ' ' + str;
                break;
            }
            const r = d % scale;
            if (str != '') {
                str = r + ' ' + unit + ' ' + str;
            } else if (r != 0) {
                str = r + ' ' + unit;
            }
            d = (d - r) / scale;
        }
        return str;
    }

    private static transData1(d: number, series: [string, number][]) {
        let str = '';
        for (const s of series) {
            const unit = s[0];
            const scale = s[1];
            if (d < scale) {
                str = Math.round(d * 100) / 100 + ' ' + unit;
                break;
            }
            d = d / scale;
        }
        return str;
    }

    private static getCoef(series: [string, number][]) {
        const seriesCoef: { [index: string]: number } = {};
        for (let i = 0; i < series.length; ++i) {
            if (i > 0) {
                seriesCoef[series[i][0]] = seriesCoef[series[i - 1][0]] * series[i - 1][1];
            } else {
                seriesCoef[series[i][0]] = 1;
            }
        }
        return seriesCoef;
    }

    public static timeStr(d: number) {
        return UnitTrans.transData(d, UnitTrans.timeSeries);
    }

    public static sizeStr(d: number) {
        return UnitTrans.transData1(d, UnitTrans.sizeSeries) + 'iB';
    }

    public static strTime(str: string) {
        const seriesCoef = UnitTrans.getCoef(UnitTrans.timeSeries);
        let d = 0;
        const reg = /(\d+)\s*([dhms])/g;
        let matches: RegExpExecArray;
        while ((matches = reg.exec(str)) != null) {
            d += parseInt(matches[1]) * seriesCoef[matches[2]];
        }
        return d;
    }

    public static strSize(str: string) {
        const seriesCoef = UnitTrans.getCoef(UnitTrans.sizeSeries);
        let d = 0;
        const reg = /(\d+(?:\.\d+)?)\s*([kMGTP]?)/;
        let matches: RegExpExecArray;
        if ((matches = reg.exec(str)) != null) {
            d += parseFloat(matches[1]) * seriesCoef[matches[2]];
        }
        return Math.round(d);
    }
}

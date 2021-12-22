import { Tag, onLoad, Ajax, TagList } from './index';

interface Record {
    [index: string]: any;
}

interface ColumnDefinition {
    th: string;
    td: string | ((data: Record) => string | Tag | TagList);
    width?: string;
}

interface DbTableConfig {
    cols: ColumnDefinition[];
    columns?: number;
    sort?: (data: Record) => -1 | 0 | 1;
}

export class DbTable {
    private recs: Record[];
    private conf: DbTableConfig;
    private divData: Tag = null;

    private constructor(recs: Record[]) {
        this.recs = recs;
    }

    public static of(recs: Record[]) {
        return new DbTable(recs);
    }

    public config(conf: DbTableConfig) {
        this.conf = conf;
        return this;
    }

    render(panelId: string) {
        const panel = Tag.byId(panelId);
        if (!panel) {
            return;
        }
        const dataAreaId = panelId + 'data-';
        let divData = Tag.byId(dataAreaId);
        if (!divData) {
            divData = Tag.of('div').id(dataAreaId).putInto(panel);
        }
        this.divData = divData;
        this.refresh();
        return this;
    }

    public refresh() {
        const divData = this.divData;
        if (!divData) {
            return;
        }
        const colgroup = Tag.of('colgroup');
        const headers = Tag.of('tr').cls('header');
        const columns = this.conf.columns ? this.conf.columns : 1;
        const cols = this.conf.cols;
        for (let i = 0; i < columns; ++i) {
            for (const col of cols) {
                Tag.of('col').style({ width: col.width }).putInto(colgroup);
                Tag.of('th').add(col.th).putInto(headers);
            }
        }
        const table = Tag.of('table').cls('stylized').add(colgroup, headers);
        let alt = false;
        let count = 0;
        let colCount = 0;
        let tr = null;
        let recs = this.recs;
        if (this.conf.sort) {
            recs.sort(this.conf.sort);
        }
        for (const rec of recs) {
            if (colCount == 0) {
                tr = Tag.of('tr').cls(alt ? 'alt' : 'def');
                alt = !alt;
            }
            for (const col of cols) {
                const td = Tag.of('td');
                if (typeof col.td === 'string') {
                    td.add(rec[col.td]);
                } else if (typeof col.td === 'function') {
                    const v = col.td(rec);
                    if (Array.isArray(v)) {
                        td.add(...v);
                    } else {
                        td.add(v);
                    }
                }
                tr.add(td);
            }
            colCount++;
            if (colCount == columns) {
                tr.putInto(table);
                tr = null;
                colCount = 0;
                count++;
            }
        }
        if (tr) {
            tr.putInto(table);
        }
        table.putInto(divData);
    }
}

declare const dbTableConfig: () => DbTableConfig;

onLoad(function () {
    Ajax.get((r) => {
        DbTable.of(JSON.parse(r)).config(dbTableConfig()).render('main');
    });
});

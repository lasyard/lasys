import { MimeType, Ajax } from './ajax';
import { Tag, TagList, onLoad } from './html';

type ColumnIndices = { [index: string]: number };

interface DataSet {
    canEdit?: boolean;
    canDelete?: boolean;
    columns: ColumnIndices;
    labels: string[];
    data: any[][];
}

type ValueCallback = (col: string) => any;
type ContentFun = (d: ValueCallback) => string | Tag | TagList;
type SortFun = (a: ValueCallback, b: ValueCallback) => -1 | 0 | 1;

interface ColumnDefinition {
    th: string | ContentFun;
    td: string | ContentFun;
    width?: string;
}

interface DbTableConfig {
    cols: ColumnDefinition[];
    columns?: number;
    sort?: SortFun;
}

declare const TABLE_KEY_COLUMNS: string[];
export class DbTable {
    private dataSet: DataSet;
    private conf: DbTableConfig;
    private divData: Tag = null;

    constructor(conf: DbTableConfig) {
        this.conf = conf;
    }

    data(dataSet: DataSet) {
        this.dataSet = dataSet;
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
        return this;
    }

    private addContent(t: Tag, data: any[], fun: string | ContentFun, ci: ColumnIndices) {
        if (typeof fun === 'string') {
            t.add(data[ci[fun]]);
        } else if (typeof fun === 'function') {
            const v = fun((col) => data[ci[col]]);
            if (Array.isArray(v)) {
                t.add(...v);
            } else {
                t.add(v);
            }
        }
    }

    refresh() {
        const divData = this.divData;
        if (!divData) {
            return;
        }
        divData.clear();
        const columns = this.conf.columns ? this.conf.columns : 1;
        const cols = this.conf.cols;
        const dataSet = this.dataSet;
        const ci = dataSet.columns;
        const labels = dataSet.labels;
        const colgroup = Tag.of('colgroup');
        const headers = Tag.of('tr').cls('header');
        for (let i = 0; i < columns; ++i) {
            for (const col of cols) {
                Tag.of('col').style({ width: col.width }).putInto(colgroup);
                const th = Tag.of('th');
                this.addContent(th, labels, col.th, ci);
                th.putInto(headers);
            }
            if (Array.isArray(TABLE_KEY_COLUMNS)) {
                if (dataSet.canEdit) {
                    Tag.of('col').style({ width: '2ex' }).putInto(colgroup);
                    Tag.of('th').putInto(headers);
                }
                if (dataSet.canDelete) {
                    Tag.of('col').style({ width: '2ex' }).putInto(colgroup);
                    Tag.of('th').putInto(headers);
                }
            }
        }
        const table = Tag.of('table').cls('stylized').add(colgroup, headers);
        let alt = false;
        let colCount = 0;
        let tr = null;
        let data = dataSet.data;
        if (this.conf.sort) {
            data.sort((a, b) => this.conf.sort((col) => a[ci[col]], (col) => b[ci[col]]));
        }
        for (const dt of data) {
            if (colCount == 0) {
                tr = Tag.of('tr').cls(alt ? 'alt' : 'def');
                alt = !alt;
            }
            for (const col of cols) {
                const td = Tag.of('td');
                this.addContent(td, dt, col.td, ci);
                tr.add(td);
            }
            if (Array.isArray(TABLE_KEY_COLUMNS)) {
                if (dataSet.canEdit) {
                    Tag.of('td').add(Tag.bi('pencil-square')).putInto(tr);
                }
                if (dataSet.canDelete) {
                    const self = this;
                    Tag.of('td').add(Tag.bi('x-square')).putInto(tr).event('click', () => {
                        const r = confirm('Are you sure to delete item [' + dt + ']?');
                        if (r) {
                            const url = new URL(window.location.href);
                            for (const keyColumn of TABLE_KEY_COLUMNS) {
                                url.searchParams.append(keyColumn, dt[ci[keyColumn]]);
                            }
                            Ajax.delete(function (res) {
                                Tag.byId('ajax-msg').html(res);
                                self.loadData();
                            }, url.href, MimeType.HTML);
                        }
                    });
                }
            }
            colCount++;
            if (colCount == columns) {
                tr.putInto(table);
                tr = null;
                colCount = 0;
            }
        }
        if (tr) {
            tr.putInto(table);
        }
        table.putInto(divData);
    }

    loadData() {
        Ajax.get((r) => {
            this.data(JSON.parse(r)).refresh();
        });
    }
}

declare const dbTableConfig: () => DbTableConfig;

onLoad(function () {
    new DbTable(dbTableConfig()).render('main').loadData();
});

import { Tag, TagContent } from './tag';
import { ToolTip } from './tool_tip';
import { onLoad } from './html';
import { MimeType, Ajax, HttpMethod } from './ajax';

type ColumnIndices = { [index: string]: number };

interface DataSet {
    canEdit?: boolean;
    canDelete?: boolean;
    columns: ColumnIndices;
    labels: string[];
    data: any[][];
}

type ValueCallback = (col: string) => any;
type ContentFun = (d: ValueCallback) => TagContent;
type SortFun = (a: ValueCallback, b: ValueCallback) => -1 | 0 | 1;

interface ColumnDefinition {
    th?: string | ContentFun;
    td: string | ContentFun;
    width?: string;
}

interface DbTableConfig {
    cols?: ColumnDefinition[];
    columns?: number;
    sort?: SortFun;
}

declare const TABLE_KEY_FIELDS: string[];

export class DbTable {
    private dataSet: DataSet;
    private conf: DbTableConfig;
    private divData: Tag<HTMLDivElement> = null;
    private updateForm: Tag<HTMLFormElement> = null;

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
        let divData = Tag.byId(dataAreaId) as Tag<HTMLDivElement>;
        if (!divData) {
            divData = Tag.of('div').id(dataAreaId).putInto(panel) as Tag<HTMLDivElement>;
        }
        this.divData = divData;
        const insertForm = Tag.form('-form-db-insert-');
        if (insertForm) {
            insertForm.ajaxfy(
                function (res) {
                    Tag.byId('ajax-msg').html(res);
                    self.loadData();
                },
                function (form) {
                    const data: { [index: string]: any } = {};
                    for (let i = 0; i < form.elements.length; ++i) {
                        const f = form.elements[i];
                        if (f instanceof HTMLInputElement) {
                            if (f.type === 'submit') {
                                continue;
                            }
                            data[f.name] = f.value;
                        }
                    }
                    return data;
                },
                HttpMethod.POST,
                MimeType.HTML,
            );
        }
        const updateForm = Tag.form('-form-db-update-');
        const self = this;
        if (updateForm) {
            updateForm.vanish().style({ display: 'block' }).ajaxfy(
                function (res) {
                    Tag.byId('ajax-msg').html(res);
                    ToolTip.get().hide();
                    self.loadData();
                },
                function (form) {
                    const keys: { [index: string]: any } = {};
                    const data: { [index: string]: any } = {};
                    for (let i = 0; i < form.elements.length; ++i) {
                        const f = form.elements[i];
                        if (f instanceof HTMLInputElement) {
                            if (f.type === 'submit') {
                                continue;
                            }
                            (TABLE_KEY_FIELDS.includes(f.name) ? keys : data)[f.name] = f.value;
                        }
                    }
                    return { keys: keys, data: data };
                },
                HttpMethod.PUT,
                MimeType.HTML,
            );
            this.updateForm = updateForm;
        }
        return this;
    }

    private static addContent(t: Tag<HTMLElement>, data: any[], fun: string | ContentFun, ci: ColumnIndices) {
        if (typeof fun === 'string') {
            t.add(data[ci[fun]]);
        } else if (typeof fun === 'function') {
            t.add(fun((col) => data[ci[col]]));
        }
    }

    private setFormData(data: any[]) {
        const ci = this.dataSet.columns;
        const form = this.updateForm;
        for (const col in ci) {
            const field = form.get().elements.namedItem(col);
            if (field instanceof HTMLInputElement) {
                (field as HTMLInputElement).value = data[ci[col]];
            } else if (field instanceof HTMLTextAreaElement) {
                (field as HTMLTextAreaElement).value = data[ci[col]];
            } else if (field instanceof HTMLSelectElement) {
                (field as HTMLSelectElement).value = data[ci[col]];
            }
        }
    }

    refresh() {
        const divData = this.divData;
        if (!divData) {
            return;
        }
        divData.clear();
        const self = this;
        const conf = this.conf;
        const columns = conf.columns ? conf.columns : 1;
        const dataSet = this.dataSet;
        const ci = dataSet.columns;
        const cols = conf.cols ? conf.cols : Object.keys(dataSet.columns).map(
            c => ({ th: c, td: c } as ColumnDefinition)
        );
        const labels = dataSet.labels;
        const colgroup = Tag.of('colgroup');
        const headers = Tag.of('tr').cls('header');
        for (let i = 0; i < columns; ++i) {
            for (const col of cols) {
                Tag.of('col').style({ width: col.width }).putInto(colgroup);
                const th = Tag.of('th');
                DbTable.addContent(th, labels, col.th, ci);
                th.putInto(headers);
            }
            if (Array.isArray(TABLE_KEY_FIELDS)) {
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
        const table = Tag.of('table').cls('stylized').addAll(colgroup, headers);
        let alt = false;
        let colCount = 0;
        let tr = null;
        let data = dataSet.data;
        if (conf.sort) {
            data.sort((a, b) => conf.sort((col) => a[ci[col]], (col) => b[ci[col]]));
        }
        for (const dt of data) {
            if (colCount == 0) {
                tr = Tag.of('tr').cls(alt ? 'alt' : 'def');
                alt = !alt;
            }
            for (const col of cols) {
                const td = Tag.of('td');
                DbTable.addContent(td, dt, col.td, ci);
                tr.add(td);
            }
            if (Array.isArray(TABLE_KEY_FIELDS)) {
                if (dataSet.canEdit) {
                    Tag.of('td').add(Tag.icon('pencil-square')).putInto(tr).toolTip(function () {
                        const data = dt;
                        self.setFormData(data);
                        return {
                            body: self.updateForm,
                            width: '70%',
                        }
                    });
                }
                if (dataSet.canDelete) {
                    Tag.of('td').add(Tag.icon('x-square')).putInto(tr).event('click', () => {
                        const r = confirm('Are you sure to delete item [' + dt + ']?');
                        if (r) {
                            const url = new URL(window.location.href);
                            for (const keyColumn of TABLE_KEY_FIELDS) {
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
    new DbTable((typeof dbTableConfig === 'function') ? dbTableConfig() : {}).render('main').loadData();
});

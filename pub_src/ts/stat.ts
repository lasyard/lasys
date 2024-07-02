import { TagContent } from "./tag";

export type ValueCallback = (col: string) => any;

type StatFun = (data: ValueCallback, context: any) => void;
type ResultFun = (context: any) => TagContent;

export interface StatObject {
    init: () => any;
    fun: StatFun,
    result: ResultFun,
}

export class Stat implements StatObject {
    private labels: string[];
    private resFun: ResultFun;

    public constructor(resFun: ResultFun, ...labels: string[]) {
        this.labels = labels;
        this.resFun = resFun;
    }

    init() {
        const ctx = new Map<string, number>();
        for (const label of this.labels) {
            ctx.set(label, 0);
        }
        return ctx;
    }

    fun(d: ValueCallback, ctx: any) {
        for (const e of ctx as Map<string, number>) {
            const k = e[0];
            ctx.set(k, ctx.get(k) + parseInt(d(k)));
        }
    }

    result(ctx: any) {
        return this.resFun(ctx);
    }
}

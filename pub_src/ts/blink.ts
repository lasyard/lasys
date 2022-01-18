type BlinkCallback = (nodes: HTMLElement[]) => void;

export class Blink {
    private time: number;
    private times: number;
    private color0: string[];
    private color: string;

    private nodes: HTMLElement[];
    private blinkTimes: number;
    private callback: BlinkCallback;
    private timer: number;

    public constructor(time = 200, times = 6, color = '#F00') {
        this.time = time;
        this.times = times;
        this.color = color;
    }

    public start(nodes: HTMLElement[], callback: BlinkCallback) {
        this.nodes = nodes;
        this.color0 = [];
        for (let i = 0; i < this.nodes.length; i++) {
            this.color0[i] = this.nodes[i].style.backgroundColor;
        }
        this.blinkTimes = this.times;
        this.callback = callback;
        const self = this;
        // `window` is needed to distinguish from node's own `setTimeout`.
        this.timer = window.setTimeout(function () {
            self.onTimer();
        }, this.time);
    }

    public isRunning() {
        return this.timer != null;
    }

    public kill() {
        if (this.timer) {
            window.clearTimeout(this.timer);
        }
        this.timer = null;
    }

    private onTimer() {
        if (this.blinkTimes == 0) {
            return;
        }
        this.blinkTimes--;
        for (let i = 0; i < this.nodes.length; i++) {
            if (this.blinkTimes % 2) {
                this.nodes[i].style.backgroundColor = this.color0[i];
            } else {
                this.nodes[i].style.backgroundColor = this.color;
            }
        }
        if (this.blinkTimes > 0) {
            const self = this;
            this.timer = window.setTimeout(function () {
                self.onTimer();
            }, this.time);
        } else {
            this.timer = null;
            this.callback(this.nodes);
        }
    }
}

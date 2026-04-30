import { INotyfNotificationOptions, DeepPartial, NotyfEvent } from './notyf.options';
export interface INotyfEventPayload {
    target: NotyfNotification;
    event?: Event;
}
export declare type NotyfEventCallback = (payload: INotyfEventPayload) => void;
export declare class NotyfNotification {
    options: DeepPartial<INotyfNotificationOptions>;
    private listeners;
    constructor(options: DeepPartial<INotyfNotificationOptions>);
    on(eventType: NotyfEvent, cb: NotyfEventCallback): void;
    private triggerEvent;
}
export interface IRenderedNotification {
    notification: NotyfNotification;
    node: HTMLElement;
}
export declare enum NotyfArrayEvent {
    Add = 0,
    Remove = 1
}
export declare type NotyfArrayEventFn<T> = (elem: T, event: NotyfArrayEvent, elems: T[]) => void;
export declare class NotyfArray<T> {
    private notifications;
    private updateFn;
    push(elem: T): void;
    splice(index: number, num: number): T;
    indexOf(elem: T): number;
    onUpdate(fn: NotyfArrayEventFn<T>): void;
}

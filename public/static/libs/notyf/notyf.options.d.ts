export declare type DeepPartial<T> = {
    [P in keyof T]?: DeepPartial<T[P]>;
};
export declare type NotyfHorizontalPosition = 'left' | 'center' | 'right';
export declare type NotyfVerticalPosition = 'top' | 'center' | 'bottom';
export interface INotyfPosition {
    x: NotyfHorizontalPosition;
    y: NotyfVerticalPosition;
}
export declare enum NotyfEvent {
    Dismiss = "dismiss",
    Click = "click"
}
export interface INotyfIcon {
    className: string;
    tagName: keyof ElementTagNameMap;
    text: string;
    color: string;
}
export interface INotyfNotificationOptions {
    type: string;
    className: string;
    duration: number;
    icon: string | INotyfIcon | false;
    /**
     * @deprecated Use `background` instead. Removal target: v4.0.0.
     */
    backgroundColor: string;
    background: string;
    message: string;
    ripple: boolean;
    position: INotyfPosition;
    dismissible: boolean;
}
export interface INotyfOptions {
    types: Array<DeepPartial<INotyfNotificationOptions>>;
    duration: number;
    ripple: boolean;
    position: INotyfPosition;
    dismissible: boolean;
}
export declare const DEFAULT_OPTIONS: INotyfOptions;

import { NotyfArray, NotyfNotification } from './notyf.models';
import { INotyfOptions, INotyfNotificationOptions, DeepPartial } from './notyf.options';
/**
 * Main controller class. Defines the main Notyf API.
 */
export default class Notyf {
    notifications: NotyfArray<NotyfNotification>;
    options: INotyfOptions;
    dismiss: (notification: NotyfNotification) => void;
    private view;
    constructor(opts?: Partial<INotyfOptions>);
    error(payload: string | Partial<INotyfNotificationOptions>): NotyfNotification;
    success(payload: string | Partial<INotyfNotificationOptions>): NotyfNotification;
    open(options: DeepPartial<INotyfNotificationOptions>): NotyfNotification;
    dismissAll(): void;
    /**
     * Assigns properties to a config object based on two rules:
     * 1. If the config object already sets that prop, leave it as so
     * 2. Otherwise, use the default prop from the global options
     *
     * It's intended to build the final config object to open a notification. e.g. if
     * 'dismissible' is not set, then use the value from the global config.
     *
     * @param props - properties to be assigned to the config object
     * @param config - object whose properties need to be set
     */
    private assignProps;
    private _pushNotification;
    private _removeNotification;
    private normalizeOptions;
    private registerTypes;
}

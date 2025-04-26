import { Ref } from "atomico";
interface SubmitEvent {
    formData: FormData;
}
interface FormDataEvent {
    submitter: HTMLElement;
}
type FormHandler<T extends FormKeyofEvents> = (ev: Omit<FormEvents[T], "currentTarget"> & {
    currentTarget: HTMLFormElement;
}) => any;
interface FormEvents {
    formdata: FormDataEvent & Event;
    submit: SubmitEvent & Event;
    reset: Event;
    change: Event;
    input: Event;
}
type FormKeyofEvents = keyof FormEvents;
export declare function useForm(): Ref<HTMLFormElement>;
/**
 * Allows you to listen to the native events of the form
 */
export declare function useFormListener<T extends FormKeyofEvents>(name: T, handler: FormHandler<T>, options?: boolean | AddEventListenerOptions): void;
export declare function useFormInputHidden(name: string, value: string): void;
export {};

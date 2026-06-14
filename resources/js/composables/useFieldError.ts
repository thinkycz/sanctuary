export interface FieldErrorProps {
    message: string | null;
    errorId: string | null;
    describedBy: string | undefined;
    invalid: boolean;
}

/**
 * Resolve a field's validation error from an Inertia `<Form>` errors map.
 *
 * Returns the props needed to wire a field input to its error message
 * for accessibility (`aria-describedby` + `aria-invalid`). Pure helper
 * (no state or lifecycle) — safe to call from templates.
 */
export function fieldError(
    errors: Record<string, unknown> | null | undefined,
    field: string,
    formId: string,
): FieldErrorProps {
    const raw = errors?.[field];
    const message = typeof raw === 'string' && raw !== '' ? raw : null;

    if (message === null) {
        return {
            message: null,
            errorId: null,
            describedBy: undefined,
            invalid: false,
        };
    }

    const errorId = `${formId}-${field}-error`;

    return {
        message,
        errorId,
        describedBy: errorId,
        invalid: true,
    };
}

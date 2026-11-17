/**
 * The compact notification shape shared with the header bell.
 *
 * The full notification row stays on the server; the bell only needs what it
 * renders in the dropdown.
 */
export type AppNotification = {
    id: string;
    title: string;
    message: string;
    url: string | null;
    read_at: string | null;
    created_at: string | null;
};

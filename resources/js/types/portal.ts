export type PortalOption = {
    key: string;
    label: string;
    url: string;
    inboxUrl: string | null;
    pendingCount: number | null;
    isCurrent: boolean;
};

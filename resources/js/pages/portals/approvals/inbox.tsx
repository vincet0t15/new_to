import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import approvals from '@/routes/approvals';

export default function ApprovalsInbox() {
    return (
        <>
            <Head title="Approvals Inbox" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Heading
                    title="Approvals Inbox"
                    description="Travel orders currently delivered to your approval step."
                />

                <div className="relative min-h-[60vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
        </>
    );
}

ApprovalsInbox.layout = {
    breadcrumbs: [
        {
            title: 'Approvals Inbox',
            href: approvals.inbox().url,
        },
    ],
};

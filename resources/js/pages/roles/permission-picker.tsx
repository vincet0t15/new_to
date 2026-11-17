import { Checkbox } from '@/components/ui/checkbox';

const GROUP_LABELS: Record<string, string> = {
    accounts: 'Accounts',
    administration: 'Administration',
    audit: 'Audit',
    designations: 'Designations',
    drafts: 'Drafts',
    employees: 'Employees',
    'employment-types': 'Employment Types',
    inbox: 'Inbox',
    offices: 'Offices',
    permissions: 'Permissions',
    positions: 'Positions',
    roles: 'Roles',
    sent: 'Sent',
    'travel-orders': 'Travel Orders',
    workflow: 'Workflow',
};

function humanize(value: string): string {
    return value
        .split(/[-_.]/)
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function groupLabel(permission: string): string {
    const [prefix] = permission.split('.');

    return GROUP_LABELS[prefix] ?? humanize(prefix);
}

function permissionLabel(permission: string): string {
    const [, ...rest] = permission.split('.');

    return rest.length > 0 ? humanize(rest.join('.')) : humanize(permission);
}

type PermissionPickerProps = {
    permissions: string[];
    selected: string[];
    onChange: (permissions: string[]) => void;
    disabled?: boolean;
};

/**
 * Grouped permission checkboxes.
 *
 * Permissions are named `area.action`, so the area becomes the group heading
 * and the action becomes the checkbox label. Each group has a shortcut so a
 * role can be set up in a few clicks instead of twenty.
 */
export function PermissionPicker({
    permissions,
    selected,
    onChange,
    disabled = false,
}: PermissionPickerProps) {
    const groups = new Map<string, string[]>();

    for (const permission of permissions) {
        const group = groupLabel(permission);
        const items = groups.get(group) ?? [];
        items.push(permission);
        groups.set(group, items);
    }

    const orderedGroups = [...groups.entries()].sort(([left], [right]) =>
        left.localeCompare(right),
    );

    const toggle = (permission: string) => {
        if (selected.includes(permission)) {
            onChange(selected.filter((item) => item !== permission));
        } else {
            onChange([...selected, permission]);
        }
    };

    const toggleGroup = (items: string[]) => {
        const allSelected = items.every((item) => selected.includes(item));

        if (allSelected) {
            onChange(selected.filter((item) => !items.includes(item)));
        } else {
            onChange([...new Set([...selected, ...items])]);
        }
    };

    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between text-xs text-muted-foreground">
                <span>
                    {selected.length} of {permissions.length} permissions
                    selected
                </span>
                {!disabled && (
                    <button
                        type="button"
                        className="font-medium text-primary hover:underline"
                        onClick={() => onChange([...permissions])}
                    >
                        Select all
                    </button>
                )}
            </div>

            <div className="max-h-72 space-y-4 overflow-y-auto rounded-md border p-3">
                {orderedGroups.map(([group, items]) => {
                    const allSelected = items.every((item) =>
                        selected.includes(item),
                    );

                    return (
                        <div key={group}>
                            <div className="mb-1 flex items-center justify-between gap-2">
                                <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    {group}
                                </p>
                                {!disabled && (
                                    <button
                                        type="button"
                                        className="text-xs text-primary hover:underline"
                                        onClick={() => toggleGroup(items)}
                                    >
                                        {allSelected ? 'Clear' : 'Select all'}
                                    </button>
                                )}
                            </div>

                            <div className="grid gap-1 sm:grid-cols-2">
                                {items.map((permission) => (
                                    <div
                                        key={permission}
                                        className="flex items-center gap-2"
                                    >
                                        <Checkbox
                                            id={`permission-${permission}`}
                                            checked={selected.includes(
                                                permission,
                                            )}
                                            onCheckedChange={() =>
                                                toggle(permission)
                                            }
                                            disabled={disabled}
                                        />
                                        <label
                                            htmlFor={`permission-${permission}`}
                                            className="cursor-pointer text-sm"
                                        >
                                            {permissionLabel(permission)}
                                        </label>
                                    </div>
                                ))}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

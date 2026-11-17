import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import type { RoleOption } from '@/types/access';

type RolePickerProps = {
    roles: RoleOption[];
    selected: number[];
    onChange: (roleIds: number[]) => void;
    canManageAdmins: boolean;
};

/**
 * Role checkboxes with a one-line explanation of what each role grants.
 *
 * Administrator roles can only be handed out by a Super Admin, so they are
 * shown disabled rather than hidden: the person setting up the account can see
 * that the role exists and who to ask.
 */
export function RolePicker({
    roles,
    selected,
    onChange,
    canManageAdmins,
}: RolePickerProps) {
    const toggle = (roleId: number) => {
        if (selected.includes(roleId)) {
            onChange(selected.filter((id) => id !== roleId));
        } else {
            onChange([...selected, roleId]);
        }
    };

    return (
        <div className="max-h-64 space-y-1 overflow-y-auto rounded-md border p-2">
            {roles.map((role) => {
                const locked =
                    role.requires_admin_permission && !canManageAdmins;

                return (
                    <div
                        key={role.id}
                        className="flex items-start gap-2 rounded px-1 py-1.5 hover:bg-muted/50"
                    >
                        <Checkbox
                            id={`role-${role.id}`}
                            className="mt-0.5"
                            checked={selected.includes(role.id)}
                            onCheckedChange={() => toggle(role.id)}
                            disabled={locked}
                        />
                        <div className="min-w-0 flex-1">
                            <label
                                htmlFor={`role-${role.id}`}
                                className="flex flex-wrap items-center gap-2 text-sm font-medium"
                                style={
                                    locked
                                        ? { cursor: 'not-allowed' }
                                        : { cursor: 'pointer' }
                                }
                            >
                                {role.name}
                                {role.is_system && (
                                    <Badge variant="secondary">Built-in</Badge>
                                )}
                                {locked && (
                                    <Badge variant="outline">
                                        Super Admin only
                                    </Badge>
                                )}
                            </label>
                            <p className="text-xs text-muted-foreground">
                                {role.permissions_count} permissions
                                {role.description
                                    ? ` · ${role.description}`
                                    : ''}
                            </p>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

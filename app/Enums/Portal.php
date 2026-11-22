<?php

namespace App\Enums;

/**
 * The top-level workspaces a signed-in account can enter.
 *
 * A portal is a UX boundary, not a separate application: each one has its own
 * URL prefix, landing page, and navigation, while the models, services, and
 * policies underneath stay shared. Access is enforced server-side by the
 * EnsurePortalAccess middleware.
 */
enum Portal: string
{
    case Employee = 'employee';
    case DepartmentHead = 'department-head';
    case Approvals = 'approvals';
    case Admin = 'admin';

    /**
     * The human-readable name shown in the portal switcher.
     */
    public function label(): string
    {
        return match ($this) {
            self::Employee => 'Employee Portal',
            self::DepartmentHead => 'Department Head Portal',
            self::Approvals => 'Approvals Portal',
            self::Admin => 'Admin Portal',
        };
    }

    /**
     * The URL prefix that identifies the portal.
     */
    public function prefix(): string
    {
        return $this->value;
    }

    /**
     * The route name of the portal's landing page.
     */
    public function dashboardRouteName(): string
    {
        return match ($this) {
            self::Employee => 'employee.dashboard',
            self::DepartmentHead => 'department-head.dashboard',
            self::Approvals => 'approvals.inbox',
            self::Admin => 'admin.dashboard',
        };
    }

    /**
     * The processing queue that belongs to this portal, if it has one.
     *
     * Used by the workspace switcher to link a person straight to the work
     * queue instead of the portal's landing page. The employee and admin
     * portals have no processing inbox of their own.
     */
    public function inboxRouteName(): ?string
    {
        return match ($this) {
            self::DepartmentHead => 'department-head.inbox',
            self::Approvals => 'approvals.inbox',
            self::Employee, self::Admin => null,
        };
    }

    /**
     * The roles that grant entry to the portal.
     *
     * The employee portal is the baseline workspace for every signed-in
     * account, so it has no granting role of its own.
     *
     * @return array<int, SystemRole>
     */
    public function grantingRoles(): array
    {
        return match ($this) {
            self::Employee => [],
            self::DepartmentHead => [SystemRole::DepartmentHead],
            self::Approvals => [
                SystemRole::BudgetOfficer,
                SystemRole::Mayor,
                SystemRole::Hrmo,
            ],
            self::Admin => [SystemRole::Admin, SystemRole::SuperAdmin],
        };
    }

    /**
     * Whether every signed-in account may enter this portal.
     */
    public function isBaseline(): bool
    {
        return $this === self::Employee;
    }

    /**
     * Sort weight for listings; the most privileged portal comes first.
     */
    public function priority(): int
    {
        return match ($this) {
            self::Admin => 1,
            self::Approvals => 2,
            self::DepartmentHead => 3,
            self::Employee => 4,
        };
    }
}

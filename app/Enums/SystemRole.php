<?php

namespace App\Enums;

/**
 * Slugs of the roles that ship with the system.
 *
 * Role records themselves live in the database so administrators can create
 * custom roles. These system slugs are used for seeding, portal routing, and
 * the few behaviours that depend on a stable role identity.
 */
enum SystemRole: string
{
    case Employee = 'employee';
    case DepartmentHead = 'department_head';
    case BudgetOfficer = 'budget_officer';
    case Mayor = 'mayor';
    case ViceMayor = 'vice_mayor';
    case Hrmo = 'hrmo';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::Employee => 'Employee',
            self::DepartmentHead => 'Department Head',
            self::BudgetOfficer => 'Budget Officer',
            self::Mayor => "Mayor's Office",
            self::ViceMayor => 'Vice Mayor',
            self::Hrmo => 'HRMO',
            self::Admin => 'Admin',
            self::SuperAdmin => 'Super Admin',
        };
    }

    /**
     * The workspace name shown at the top of the sidebar.
     */
    public function workspaceLabel(): string
    {
        return match ($this) {
            self::Employee => 'Employee Workspace',
            self::DepartmentHead => 'Department Head Workspace',
            self::BudgetOfficer => 'Budget Office Workspace',
            self::Mayor => "Mayor's Office Workspace",
            self::ViceMayor => 'Vice Mayor Workspace',
            self::Hrmo => 'HRMO Workspace',
            self::Admin => 'Administration',
            self::SuperAdmin => 'Super Administration',
        };
    }
}

<?php

namespace Backpack\PermissionManager\app\Http\Controllers;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\PermissionManager\app\Http\Requests\RoleStoreCrudRequest as StoreRequest;
use Backpack\PermissionManager\app\Http\Requests\RoleUpdateCrudRequest as UpdateRequest;
use Spatie\Permission\PermissionRegistrar;

// VALIDATION

class RoleCrudController extends CrudController
{
    protected string $role_model;
    protected string $permission_model;

    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        $this->role_model = $role_model = config('hncore.permissionmanager.models.role');
        $this->permission_model = $permission_model = config('hncore.permissionmanager.models.permission');

        $this->crud->setModel($role_model);
        $this->crud->setEntityNameStrings(trans('hncore::permissionmanager.role'), trans('hncore::permissionmanager.roles'));
        $this->crud->setRoute(hncore_url('role'));

        // deny access according to configuration file
        if (config('hncore.permissionmanager.allow_role_create') == false) {
            $this->crud->denyAccess('create');
        }
        if (config('hncore.permissionmanager.allow_role_update') == false) {
            $this->crud->denyAccess('update');
        }
        if (config('hncore.permissionmanager.allow_role_delete') == false) {
            $this->crud->denyAccess('delete');
        }
    }

    public function setupListOperation()
    {
        /**
         * Show a column for the name of the role.
         */
        $this->crud->addColumn([
            'name'  => 'name',
            'label' => trans('hncore::permissionmanager.name'),
            'type'  => 'text',
        ]);

        /**
         * Show a column with the number of users that have that particular role.
         *
         * Note: To account for the fact that there can be thousands or millions
         * of users for a role, we did not use the `relationship_count` column,
         * but instead opted to append a fake `user_count` column to
         * the result, using Laravel's `withCount()` method.
         * That way, no users are loaded.
         */
        $this->crud->query->withCount('users');
        $this->crud->addColumn([
            'label'     => trans('hncore::permissionmanager.users'),
            'type'      => 'text',
            'name'      => 'users_count',
            'wrapper'   => [
                'href' => function ($crud, $column, $entry, $related_key) {
                    return hncore_url('user?role='.$entry->getKey());
                },
            ],
            'suffix'    => ' '.strtolower(trans('hncore::permissionmanager.users')),
        ]);

        /**
         * In case multiple guards are used, show a column for the guard.
         */
        if (config('hncore.permissionmanager.multiple_guards')) {
            $this->crud->addColumn([
                'name'  => 'guard_name',
                'label' => trans('hncore::permissionmanager.guard_type'),
                'type'  => 'text',
            ]);
        }

        /**
         * Show the exact permissions that role has.
         */
        $this->crud->addColumn([
            // n-n relationship (with pivot table)
            'label'     => mb_ucfirst(trans('hncore::permissionmanager.permission_plural')),
            'type'      => 'select_multiple',
            'name'      => 'permissions', // the method that defines the relationship in your Model
            'entity'    => 'permissions', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => $this->permission_model, // foreign key model
            'pivot'     => true, // on create&update, do you need to add/delete pivot table entries?
        ]);
    }

    public function setupCreateOperation()
    {
        $this->addFields();
        $this->crud->setValidation(StoreRequest::class);

        //otherwise, changes won't have effect
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function setupUpdateOperation()
    {
        $this->addFields();
        $this->crud->setValidation(UpdateRequest::class);

        //otherwise, changes won't have effect
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function addFields()
    {
        $this->crud->addField([
            'name'  => 'name',
            'label' => trans('hncore::permissionmanager.name'),
            'type'  => 'text',
        ]);

        if (config('hncore.permissionmanager.multiple_guards')) {
            $this->crud->addField([
                'name'    => 'guard_name',
                'label'   => trans('hncore::permissionmanager.guard_type'),
                'type'    => 'select_from_array',
                'options' => $this->getGuardTypes(),
            ]);
        }

        $this->crud->addField([
            'label'     => mb_ucfirst(trans('hncore::permissionmanager.permission_plural')),
            'type'      => 'checklist',
            'name'      => 'permissions',
            'entity'    => 'permissions',
            'attribute' => 'name',
            'model'     => $this->permission_model,
            'pivot'     => true,
        ]);
    }

    /*
     * Get an array list of all available guard types
     * that have been defined in app/config/auth.php
     *
     * @return array
     **/
    private function getGuardTypes()
    {
        $guards = config('auth.guards');

        $returnable = [];
        foreach ($guards as $key => $details) {
            $returnable[$key] = $key;
        }

        return $returnable;
    }
}

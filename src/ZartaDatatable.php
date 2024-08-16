<?php

namespace  Syntech\SyntechTable\SyntechTable;

use App\Models\Customer\Customer;

class ZartaDatatable extends SyntechBase
{
    protected $model = Customer::class;
    protected $route = 'dashboard.customer';

    protected function getColumns()
    {
        return [
            $this->makeColumn('id', __('ID'), true, true, true, false),
            $this->makeColumn('name', __('Name'), true, true, true, true, 'htmlCellRenderer'),
            $this->makeColumn('email', __('Email'), true, true, true, true),
            $this->makeColumn('phone', __('Phone'), true, true, true, true),
            $this->makeColumn('created_at', __('Created At'), true, true, true, false),
            $this->makeColumn('actions', __('Actions'), false, false, false, false, 'htmlCellRenderer'),
        ];
    }

    public function columns($dataTable)
    {
        return $this->editColumns($dataTable, []);
    }
}

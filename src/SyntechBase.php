<?php

namespace Syntech\Syntechtable;

use Illuminate\Support\Facades\Route;

class SyntechBase
{
    protected $actionDisplay;

    public function __construct()
    {
        $this->actionDisplay = config('syntechtable.action_display', ['show', 'edit', 'delete']);
    }

    public function query()
    {
        return (new $this->model)->latest()->get();
    }

    public function dataTable($query)
    {
        $columns = $this->getColumns();

        return $query->map(function ($model) use ($columns) {
            $row = [];
            foreach ($columns as $column) {
                $field = $column['field'];
                if (method_exists($this, $field)) {
                    $row[$field] = $this->{$field}($model);
                } else {
                    $row[$field] = $model->{$field};
                }
            }
            $row['actions'] = $this->actionButtons($model->id);
            return $row;
        });
    }

    protected function editColumns($dataTable, $callbacks)
    {
        return $dataTable->map(function ($row) use ($callbacks) {
            foreach ($callbacks as $column => $callback) {
                if (isset($row[$column])) {
                    $row[$column] = $callback($row);
                }
            }
            return $row;
        });
    }

    protected function nameWithImage($name, $image)
    {
        return '<div><img src="' . $image . '" alt="' . $name . '" style="width: 30px; height: 30px; margin-right: 10px;">' . $name . '</div>';
    }

    public function render($view)
    {
        return view($view, [
            'scripts'   => $this->script(),
            'datatable' => $this->columns($this->dataTable($this->query())),
            'columns'   => $this->getColumns()
        ]);
    }

    protected function makeColumn($name, $title, $sortable = true, $filter = true, $floatingFilter = true, $editable = false, $cellRenderer = null, $suppressHeaderMenuButton = false)
    {
        $column = [
            'field' => $name,
            'headerName' => $this->translate($title),
            'sortable' => $sortable,
            'filter' => $filter,
            'floatingFilter' => $floatingFilter,
            'editable' => $editable,
            'suppressHeaderMenuButton' => $suppressHeaderMenuButton,
        ];

        if ($cellRenderer) {
            $column['cellRenderer'] = $cellRenderer;
        }

        return $column;
    }

    protected function rowData()
    {
        return $this->columns($this->dataTable($this->query()))->toJson();
    }

    public function actionButtons($id)
    {
        $buttons = '';
        $route = $this->route;

        foreach ($this->actionDisplay as $action) {
            $buttons .= $this->getButton($action, $id, $route);
        }

        return $buttons;
    }

    protected function getButton($action, $id, $route)
    {
        switch ($action) {
            case 'show':
                return '<a href="' . route($route . '.show', $id) . '" class="btn btn-sm btn-primary">' . __('Show') . '</a> ';
            case 'edit':
                return '<a href="' . route($route . '.edit', $id) . '" class="btn btn-sm btn-warning">' . __('Edit') . '</a> ';
            case 'delete':
                return '<form action="' . route($route . '.destroy', $id) . '" method="POST" style="display:inline-block;">
                    ' . csrf_field() . method_field('DELETE') . '
                    <button type="submit" class="btn btn-sm btn-danger">' . __('Delete') . '</button>
                </form>';
        }
    }

    protected function translate($text)
    {
        return __($text);
    }

    public function script()
    {
        $localization = config('syntechtable.localization', [
            'exportCsv' => __('Export as CSV'),
            'exportPdf' => __('Export as PDF'),
            'print' => __('Print'),
        ]);

        $main_script = '

        <link rel="stylesheet" href="https://www.cdn.jsdelivr.net/npm/ag-grid-community/dist/styles/ag-grid.css">
        <link rel="stylesheet" href="https://www.cdn.jsdelivr.net/npm/ag-grid-community/dist/styles/ag-theme-quartz.css">
        <script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.0/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.21/jspdf.plugin.autotable.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.21/jspdf.plugin.autotable.min.js"></script>';

      $table_script =  '
        <script>
        let gridApi;

        document.addEventListener("DOMContentLoaded", function() {
            class HtmlCellRenderer {
                init(params) {
                    this.eGui = document.createElement("div");
                    this.eGui.innerHTML = params.value;
                }

                getGui() {
                    return this.eGui;
                }

                refresh(params) {
                    return false;
                }
            }

            const gridOptions = {
                columnDefs: ' . json_encode($this->getColumns()) . ',
                rowData: ' . $this->rowData() . ',
                localeText: ' . json_encode($localization) . ',

                defaultColDef: {
                    flex: 1,
                    minWidth: 100,
                    resizable: true,
                    filter: true,
                    sortable: true,
                    floatingFilter: true,
                    editable: true,
                },
                suppressExcelExport: true,
                popupParent: document.body,

                sideBar: "filters",
                pagination: true,
                paginationPageSize: 10,
                paginationPageSizeSelector: [10, 50, 100, 500, 1000],
                domLayout: "autoHeight",
                components: {
                    htmlCellRenderer: HtmlCellRenderer,
                },
                onGridReady: function(params) {
                    gridApi = params.api;
                    params.api.sizeColumnsToFit();
                    params.api.setDomLayout("normal");
                },
                onCellValueChanged: function(event) {
                    const data = event.data;
                    fetch("/customers/update", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "' . csrf_token() . '"
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log("Success:", data);
                    })
                    .catch((error) => {
                        console.error("Error:", error);
                    });
                }
            };

            const gridDiv = document.querySelector("#myGrid");
            new agGrid.Grid(gridDiv, gridOptions);

            document.getElementById("exportCsv").addEventListener("click", function() {
                gridOptions.api.exportDataAsCsv();
            });

            document.getElementById("exportPdf").addEventListener("click", function() {
                const doc = new jsPDF();
                doc.autoTable({ html: "#myGrid table" });
                doc.save("table.pdf");
            });

            document.getElementById("print").addEventListener("click", function() {
                const gridHtml = gridDiv.innerHTML;
                const newWindow = window.open("", "", "width=800, height=600");
                newWindow.document.write(gridHtml);
                newWindow.print();
            });
        });
        </script>
        ';

        return $main_script . $table_script;
    }
}

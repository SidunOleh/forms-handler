<div class="wrap">
    <div id="forms-data"></div>
</div>

<link href="https://unpkg.com/tabulator-tables@6.2.0/dist/css/tabulator.min.css" rel="stylesheet">

<script type="text/javascript" src="https://unpkg.com/tabulator-tables/dist/js/tabulator.min.js"></script>
<script>
const table = new Tabulator('#forms-data', {
    pagination: true,
    paginationMode: 'remote',
    ajaxURL: '/wp-admin/admin-ajax.php',
    ajaxParams: {
        action: 'get_forms_data',
    },
    paginationSize: 15,
    layout: 'fitColumns',
    columns:[
        {
            title: 'Name', 
            field: 'name',
        },
        {
            title: 'Phone', 
            field: 'phone',
        },
        {
            title: 'E-mail', 
            field: 'email',
        },
        {
            title: 'Message', 
            field: 'message',
            formatter: 'textarea',
            width: 500,
        },
        {
            title: 'Form', 
            field: 'form',
            width: 150,
        },
        {
            title: 'Status', 
            field: 'status',
            formatter: 'tickCross',
            width: 80,
            hozAlign: 'center',
        },
        {
            title: 'Created at', 
            field: 'created_at',
            width: 150,
        },
        {
            formatter: () => {
                return '<span title="Delete">🗑️</span>'
            }, 
            hozAlign: 'center',
            width: 30,
            cellClick(e, cell) {
                if (! confirm('Are you sure to delete?')) {
                    return
                }

                deleteItem(cell._cell.row.data.id)
            },
        },
    ],
})

function deleteItem(id) {
    const data = new FormData()
    data.append('action', 'delete_forms_data')
    data.append('id', id)
    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: data,
    }).then(async res => {
        const data = await res.json()
        if (data.success) {
            table.setData()
        } else {
            throw new Error('Error')
        }
    }).catch(err => alert(err))
}
</script>

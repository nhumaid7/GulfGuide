$(document).ready(function () {
    $('.datatable-gulfguide').each(function () {
        $(this).DataTable({
            autoWidth: false,
            destroy: true,
            orderMulti: true,
            responsive: true,
            rowReorder: {
                selector: 'td:nth-child(2)'
            },
            numeric: true,
            caseFirst: false,
            ignorePunctuation: true,
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50, 100],
            order: [[0, 'asc']],
            columnDefs: [
                {targets: 0, type: "num", },
                { orderable: false, targets: -1 }
            ],
            language: {
                search: 'Search:',
                lengthMenu: 'Show _MENU_ records',
                info: 'Showing _START_ to _END_ of _TOTAL_ records | Hold SHIFT to sort multiple columns',
                emptyTable: 'No record found',
                zeroRecords: 'No matching record found'
            }
        });
    }
    );
});
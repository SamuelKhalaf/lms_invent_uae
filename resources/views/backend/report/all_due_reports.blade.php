@extends('backend.layout.main') @section('content')

<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h4 class="text-center">{{trans('file.All Due Reports')}}</h4>
            </div>
            {!! Form::open(['route' => 'report.allDueReports.post', 'method' => 'post']) !!}
            <div class="col-md-6 offset-md-3 mt-4 mb-3">
                <div class="form-group row">
                    <label class="d-tc mt-2 col-md-3"><strong>{{trans('file.Customer')}}</strong> &nbsp;</label>
                    <div class="d-tc col-md-6">
                        <select name="customer_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select customer...">
                            <option value="">{{trans('file.All Customers')}}</option>
                            @foreach($lims_customer_list as $customer)
                                <option value="{{$customer->id}}" @if($customer_id == $customer->id) selected @endif>{{$customer->name . ' (' . $customer->phone_number . ')'}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-tc col-md-3">
                        <button class="btn btn-primary" type="submit">{{trans('file.submit')}}</button>
                    </div>
                </div>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
    <div class="table-responsive mb-4">
        <table id="report-table" class="table table-hover">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('file.Date')}}</th>
                    <th>{{trans('file.reference')}}</th>
                    <th>{{trans('file.Customer Details')}}</th>
                    <th>{{trans('file.grand total')}}</th>
                    <th>{{trans('file.Returned Amount')}}</th>
                    <th>{{trans('file.Paid')}}</th>
                    <th>{{trans('file.Due')}}</th>
                    <th class="not-exported">{{trans('file.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_sale_data as $key => $sale_data)
                <?php
                    $customer = DB::table('customers')->find($sale_data->customer_id);
                    $returned_amount = DB::table('returns')->where('sale_id', $sale_data->id)->sum('grand_total');
                ?>
                <tr>
                    <td>{{$key}}</td>
                    <td>{{date($general_setting->date_format, strtotime($sale_data->created_at->toDateString())) . ' '. $sale_data->created_at->toTimeString()}}</td>
                    <td>{{$sale_data->reference_no}}</td>
                    <td>{{$customer->name .' (' .$customer->phone_number . ')'}}</td>
                    <td>{{number_format((float)$sale_data->grand_total, 2, '.', '')}}</td>
                    <td>{{number_format((float)$returned_amount, 2, '.', '')}}</td>
                    @if($sale_data->paid_amount)
                    <td>{{number_format((float)$sale_data->paid_amount, 2, '.', '')}}</td>
                    @else
                    <td>0.00</td>
                    @endif
                    <td>{{number_format((float)($sale_data->grand_total - $returned_amount - $sale_data->paid_amount), 2, '.', '')}}</td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{trans('file.action')}}<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" class="btn btn-link get-payment" data-id="{{$sale_data->id}}"><i class="fa fa-money"></i> {{trans('file.View Payment')}}</button>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="tfoot active">
                <th></th>
                <th>{{trans('file.Total')}}:</th>
                <th></th>
                <th></th>
                <th>0.00</th>
                <th>0.00</th>
                <th>0.00</th>
                <th>0.00</th>
                <th></th>
            </tfoot>
        </table>
    </div>
</section>

<!-- Payment Viewing Modal -->
<div id="view-payment" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{trans('file.All')}} {{trans('file.Payment')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                <table class="table table-hover payment-list">
                    <thead>
                        <tr>
                            <th>{{trans('file.date')}}</th>
                            <th>{{trans('file.reference')}}</th>
                            <th>{{trans('file.Account')}}</th>
                            <th>{{trans('file.Amount')}}</th>
                            <th>{{trans('file.Paid By')}}</th>
                            <th>{{trans('file.action')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div id="edit-payment" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{trans('file.Edit Payment')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                {!! Form::open(['route' => 'sale.update-payment', 'method' => 'post', 'files' => true, 'class' => 'payment-form' ]) !!}
                    <div class="row">
                        <input type="hidden" name="payment_id">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{trans('file.Paid By')}}</label>
                                <select name="edit_paid_by_id" class="form-control">
                                    <option value="1">Cash</option>
                                    <option value="2">Gift Card</option>
                                    <option value="3">Credit Card</option>
                                    <option value="4">Cheque</option>
                                    <option value="5">Paypal</option>
                                    <option value="6">Deposit</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{trans('file.Amount')}} *</label>
                                <input type="number" name="edit_amount" class="form-control" step="any" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{trans('file.Paying Amount')}} *</label>
                                <input type="number" name="edit_paying_amount" class="form-control" step="any" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{trans('file.Account')}}</label>
                                <select name="account_id" class="form-control">
                                    @foreach($lims_account_list as $account)
                                        <option value="{{$account->id}}">{{$account->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{trans('file.Payment Note')}}</label>
                                <textarea rows="3" name="edit_payment_note" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">{{trans('file.submit')}}</button>
                {{ Form::close() }}
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">

    $("ul#report").siblings('a').attr('aria-expanded','true');
    $("ul#report").addClass("show");
    $("ul#report #all-due-report-menu").addClass("active");

    // Payment viewing variables
    var payment_date = [];
    var payment_reference = [];
    var paid_amount = [];
    var paying_method = [];
    var payment_id = [];
    var payment_note = [];
    var account = [];
    var account_name = [];
    var account_id = [];
    var paying_amount = [];

    // AJAX setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Payment viewing functionality
    $(document).on("click", "table tbody .get-payment", function(event) {
        var id = $(this).data('id').toString();
        $.get('../sales/getpayment/' + id, function(data) {
            $(".payment-list tbody").remove();
            var newBody = $("<tbody>");
            payment_date = data[0];
            payment_reference = data[1];
            paid_amount = data[2];
            paying_method = data[3];
            payment_id = data[4];
            payment_note = data[5];
            paying_amount = data[9];
            account_name = data[10];
            account_id = data[11];

            $.each(payment_date, function(index) {
                var newRow = $("<tr>");
                var cols = '';

                cols += '<td>' + payment_date[index] + '</td>';
                cols += '<td>' + payment_reference[index] + '</td>';
                cols += '<td>' + account_name[index] + '</td>';
                cols += '<td>' + paid_amount[index] + '</td>';
                cols += '<td>' + paying_method[index] + '</td>';
                cols += '<td><div class="btn-group"><button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{trans("file.action")}}<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button><ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">';
                if(paying_method[index] != 'Paypal')
                    cols += '<li><button type="button" class="btn btn-link edit-btn" data-id="' + payment_id[index] +'" data-clicked=false data-toggle="modal" data-target="#edit-payment"><i class="dripicons-document-edit"></i> {{trans("file.edit")}}</button></li> ';
                cols += '{{ Form::open(['route' => 'sale.delete-payment', 'method' => 'post'] ) }}<li><input type="hidden" name="id" value="' + payment_id[index] + '" /> <button type="submit" class="btn btn-link" onclick="return confirmPaymentDelete()"><i class="dripicons-trash"></i> {{trans("file.delete")}}</button></li>{{ Form::close() }}';
                cols += '</ul></div></td>';
                newRow.append(cols);
                newBody.append(newRow);
                $("table.payment-list").append(newBody);
            });
            $('#view-payment').modal('show');
        });
    });

    // Edit payment functionality
    $("table.payment-list").on("click", ".edit-btn", function(event) {
        $(".edit-btn").attr('data-clicked', true);
        var id = $(this).data('id').toString();
        
        // Payment method mapping
        var paymentMethodMap = {
            'Cash': 1,
            'Gift Card': 2,
            'Credit Card': 3,
            'Cheque': 4,
            'Paypal': 5,
            'Deposit': 6,
            'Points': 7
        };
        
        $.each(payment_id, function(index) {
            if(payment_id[index] == id) {
                $('input[name="payment_id"]').val(payment_id[index]);
                $('select[name="edit_paid_by_id"]').val(paymentMethodMap[paying_method[index]] || 1);
                $('input[name="edit_amount"]').val(paid_amount[index]);
                $('input[name="edit_paying_amount"]').val(paying_amount[index]);
                $('textarea[name="edit_payment_note"]').val(payment_note[index]);
                return false;
            }
        });
        $('#view-payment').modal('hide');
    });

    $('#report-table').DataTable( {
        "order": [],
        'language': {
            'lengthMenu': '_MENU_ {{trans("file.records per page")}}',
             "info":      '<small>{{trans("file.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":  '{{trans("file.Search")}}',
            'paginate': {
                    'previous': '<i class="dripicons-chevron-left"></i>',
                    'next': '<i class="dripicons-chevron-right"></i>'
            }
        },
        'columnDefs': [
            {
                "orderable": false,
                'targets': [0, 8]
            },
            {
                'render': function(data, type, row, meta){
                    if(type === 'display'){
                        data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    }

                   return data;
                },
                'checkboxes': {
                   'selectRow': true,
                   'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                },
                'targets': [0]
            }
        ],
        'select': { style: 'multi',  selector: 'td:first-child'},
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="dripicons-document-new"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="fa fa-print"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="fa fa-eye"></i>',
                columns: ':gt(0)'
            }
        ],
        drawCallback: function () {
            var api = this.api();
            datatable_sum(api, false);
        }
    } );

    function datatable_sum(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();

            $( dt_selector.column( 4 ).footer() ).html(dt_selector.cells( rows, 4, { page: 'current' } ).data().sum().toFixed(2));
            $( dt_selector.column( 5 ).footer() ).html(dt_selector.cells( rows, 5, { page: 'current' } ).data().sum().toFixed(2));
            $( dt_selector.column( 6 ).footer() ).html(dt_selector.cells( rows, 6, { page: 'current' } ).data().sum().toFixed(2));
            $( dt_selector.column( 7 ).footer() ).html(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum().toFixed(2));
        }
        else {
            $( dt_selector.column( 4 ).footer() ).html(dt_selector.column( 4, {page:'current'} ).data().sum().toFixed(2));
            $( dt_selector.column( 5 ).footer() ).html(dt_selector.column( 5, {page:'current'} ).data().sum().toFixed(2));
            $( dt_selector.column( 6 ).footer() ).html(dt_selector.column( 6, {page:'current'} ).data().sum().toFixed(2));
            $( dt_selector.column( 7 ).footer() ).html(dt_selector.column( 7, {page:'current'} ).data().sum().toFixed(2));
        }
    }


</script>
@endpush

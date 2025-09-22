@extends('backend.layout.main') @section('content')

<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h4 class="text-center">{{trans('file.Tax Report')}}</h4>
            </div>
            {!! Form::open(['route' => 'report.taxReport.post', 'method' => 'post']) !!}
            <div class="row ml-1 mt-2">
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{trans('file.Date')}}</strong></label>
                        <input type="text" class="daterangepicker-field form-control" value="{{$start_date}} To {{$end_date}}" required />
                        <input type="hidden" name="start_date" value="{{$start_date}}" />
                        <input type="hidden" name="end_date" value="{{$end_date}}" />
                    </div>
                </div>
                <div class="col-md-3 @if(\Auth::user()->role_id > 2){{'d-none'}}@endif">
                    <div class="form-group">
                        <label><strong>{{trans('file.Warehouse')}}</strong></label>
                        <select name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins">
                            <option value="0">{{trans('file.All Warehouse')}}</option>
                            @foreach($lims_warehouse_list as $warehouse)
                                <option value="{{$warehouse->id}}" @if($warehouse_id == $warehouse->id) selected @endif>{{$warehouse->name}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{trans('file.Tax Type')}}</strong></label>
                        <select name="tax_type" class="form-control">
                            <option value="all" @if($tax_type == 'all') selected @endif>{{trans('file.All')}}</option>
                            <option value="sales" @if($tax_type == 'sales') selected @endif>{{trans('file.Sales Tax')}}</option>
                            <option value="purchases" @if($tax_type == 'purchases') selected @endif>{{trans('file.Purchase Tax')}}</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 mt-3">
                    <div class="form-group">
                        <button class="btn btn-primary" type="submit">{{trans('file.submit')}}</button>
                    </div>
                </div>
            </div>
            {!! Form::close() !!}
        </div>
    </div>



    <!-- Detailed Tax Transactions -->
    @if($tax_type == 'all' || $tax_type == 'sales')
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{trans('file.Sales Tax Transactions')}}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="sales-tax-table">
                            <thead>
                                <tr>
                                    <th>{{trans('file.Date')}}</th>
                                    <th>{{trans('file.reference')}}</th>
                                    <th>{{trans('file.Customer')}}</th>
                                    <th>{{trans('file.Product Tax')}}</th>
                                    <th>{{trans('file.Order Tax')}}</th>
                                    <th>{{trans('file.Total Tax')}}</th>
                                    <th>{{trans('file.grand total')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sales_tax_data as $sale)
                                    @php
                                        $customer = DB::table('customers')->find($sale->customer_id);
                                    @endphp
                                    <tr>
                                        <td>{{date(config('date_format'), strtotime($sale->created_at))}}</td>
                                        <td>{{$sale->reference_no}}</td>
                                        <td>{{$customer->name ?? 'N/A'}}</td>
                                        <td>{{number_format($sale->total_tax, 2)}}</td>
                                        <td>{{number_format($sale->order_tax, 2)}}</td>
                                        <td><strong>{{number_format($sale->total_tax + $sale->order_tax, 2)}}</strong></td>
                                        <td>{{number_format($sale->grand_total, 2)}}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($tax_type == 'all' || $tax_type == 'purchases')
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{trans('file.Purchase Tax Transactions')}}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="purchase-tax-table">
                            <thead>
                                <tr>
                                    <th>{{trans('file.Date')}}</th>
                                    <th>{{trans('file.reference')}}</th>
                                    <th>{{trans('file.Supplier')}}</th>
                                    <th>{{trans('file.Product Tax')}}</th>
                                    <th>{{trans('file.Order Tax')}}</th>
                                    <th>{{trans('file.Total Tax')}}</th>
                                    <th>{{trans('file.grand total')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchase_tax_data as $purchase)
                                    @php
                                        $supplier = DB::table('suppliers')->find($purchase->supplier_id);
                                    @endphp
                                    <tr>
                                        <td>{{date(config('date_format'), strtotime($purchase->created_at))}}</td>
                                        <td>{{$purchase->reference_no}}</td>
                                        <td>{{$supplier->name ?? 'N/A'}}</td>
                                        <td>{{number_format($purchase->total_tax, 2)}}</td>
                                        <td>{{number_format($purchase->order_tax, 2)}}</td>
                                        <td><strong>{{number_format($purchase->total_tax + $purchase->order_tax, 2)}}</strong></td>
                                        <td>{{number_format($purchase->grand_total, 2)}}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</section>

@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#report").siblings('a').attr('aria-expanded','true');
    $("ul#report").addClass("show");
    $("ul#report #tax-report-menu").addClass("active");

    $(".daterangepicker-field").daterangepicker({
      callback: function(startDate, endDate, period){
        var starting_date = startDate.format('YYYY-MM-DD');
        var ending_date = endDate.format('YYYY-MM-DD');
        var title = starting_date + ' To ' + ending_date;
        $(this).val(title);
        $('input[name="start_date"]').val(starting_date);
        $('input[name="end_date"]').val(ending_date);
      }
    });

    $('.selectpicker').selectpicker('refresh');

    // Initialize DataTables for tax transaction tables
    if ($.fn.DataTable) {
        $('#sales-tax-table').DataTable({
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
            'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
            dom: '<"row"lfB>rtip',
            buttons: [
                {
                    extend: 'pdf',
                    text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'excel',
                    text: '<i title="export to excel" class="dripicons-document-new"></i>',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'csv',
                    text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'print',
                    text: '<i title="print" class="fa fa-print"></i>',
                    exportOptions: {
                        columns: ':visible'
                    }
                }
            ]
        });

        $('#purchase-tax-table').DataTable({
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
            'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
            dom: '<"row"lfB>rtip',
            buttons: [
                {
                    extend: 'pdf',
                    text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'excel',
                    text: '<i title="export to excel" class="dripicons-document-new"></i>',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'csv',
                    text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'print',
                    text: '<i title="print" class="fa fa-print"></i>',
                    exportOptions: {
                        columns: ':visible'
                    }
                }
            ]
        });
    }
</script>
@endpush

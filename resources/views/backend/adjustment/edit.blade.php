@extends('backend.layout.main')
@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{trans('file.Update Adjustment')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => ['qty_adjustment.update', $lims_adjustment_data->id], 'method' => 'put', 'files' => true, 'id' => 'adjustment-form']) !!}
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.reference')}}</label>
                                            <p><strong>{{$lims_adjustment_data->reference_no}}</strong></p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Warehouse')}} *</label>
                                            <select required id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select warehouse...">
                                                @foreach($lims_warehouse_list as $warehouse)
                                                <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="warehouse_id_hidden" value="{{$lims_adjustment_data->warehouse_id}}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Attach Document')}}</label>
                                            <input type="file" name="document" class="form-control" >
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <label>{{trans('file.Select Product')}}</label>
                                        <div class="search-box input-group">
                                            <button type="button" class="btn btn-secondary btn-lg"><i class="fa fa-barcode"></i></button>
                                            <input type="text" name="product_code_name" id="lims_productcodeSearch" placeholder="Please type product code and select..." class="form-control" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-5">
                                    <div class="col-md-12">
                                        <h5>{{trans('file.Order Table')}} *</h5>
                                        <div class="table-responsive mt-3">
                                            <table id="myTable" class="table table-hover order-list">
                                                <thead>
                                                    <tr>
                                                        <th>{{trans('file.name')}}</th>
                                                        <th>{{trans('file.Code')}}</th>
                                                        <th>{{trans('file.Quantity')}}</th>
                                                        <th>Batch Number</th>
                                                        <th>Expire Date</th>
                                                        <th>{{trans('file.action')}}</th>
                                                        <th><i class="dripicons-trash"></i></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                	@foreach($lims_product_adjustment_data as $product_adjustment_data)
                                                	<tr>
                                                	<?php
                                                	   $product = DB::table('products')->find($product_adjustment_data->product_id);
                                                       if($product_adjustment_data->variant_id) {
                                                            $product_variant = \App\ProductVariant::select('id', 'item_code')->FindExactProduct($product_adjustment_data->product_id, $product_adjustment_data->variant_id)->first();
                                                            $product->code = $product_variant->item_code;
                                                            $product_variant_id = $product_variant->id;
                                                       }
                                                       else
                                                            $product_variant_id = null;
                                                       
                                                       // Get batch information if exists
                                                       $batch_no = '';
                                                       $expired_date = '';
                                                       $product_batch_id = '';
                                                       if($product_adjustment_data->product_batch_id) {
                                                           $batch_data = \App\ProductBatch::find($product_adjustment_data->product_batch_id);
                                                           if($batch_data) {
                                                               $batch_no = $batch_data->batch_no;
                                                               $expired_date = $batch_data->expired_date ? date('Y-m-d', strtotime($batch_data->expired_date)) : '';
                                                               $product_batch_id = $batch_data->id;
                                                           }
                                                       }
                                                       
                                                       // Get current stock quantity from warehouse
                                                       $current_stock = 0;
                                                       if($product_adjustment_data->variant_id) {
                                                           $warehouse_stock = \App\Product_Warehouse::where([
                                                               ['product_id', $product_adjustment_data->product_id],
                                                               ['variant_id', $product_adjustment_data->variant_id],
                                                               ['warehouse_id', $lims_adjustment_data->warehouse_id]
                                                           ])->first();
                                                       } else {
                                                           // For batched products, find the specific warehouse record with the correct batch_id
                                                           if($product_adjustment_data->product_batch_id) {
                                                               $warehouse_stock = \App\Product_Warehouse::where([
                                                                   ['product_id', $product_adjustment_data->product_id],
                                                                   ['warehouse_id', $lims_adjustment_data->warehouse_id],
                                                                   ['variant_id', null],
                                                                   ['product_batch_id', $product_adjustment_data->product_batch_id]
                                                               ])->first();
                                                           } else {
                                                               $warehouse_stock = \App\Product_Warehouse::where([
                                                                   ['product_id', $product_adjustment_data->product_id],
                                                                   ['warehouse_id', $lims_adjustment_data->warehouse_id],
                                                                   ['variant_id', null],
                                                                   ['product_batch_id', null]
                                                               ])->first();
                                                           }
                                                       }
                                                       if($warehouse_stock) {
                                                           $current_stock = $warehouse_stock->qty;
                                                       } else {
                                                           $current_stock = 0;
                                                       }
                                                	?>
                                                	<td>
                                                	    @if($product->is_batch && $product_batch_id)
                                                	        {{$product->name}} - Batch: {{$batch_no}}@if($expired_date) (Exp: {{$expired_date}})@endif
                                                	    @else
                                                	        {{$product->name}}
                                                	    @endif
                                                	    <small class="text-muted d-block">Stock: {{$current_stock}}</small>
                                                	</td>
                                                	<td>{{$product->code}}</td>
                                                	<td><input type="number" class="form-control qty" name="qty[]" value="{{$product_adjustment_data->qty}}" data-original-value="{{$product_adjustment_data->qty}}" min="0" required step="any" /></td>
                                                	<td>
                                                	    @if($product->is_batch)
                                                	        <input type="text" class="form-control batch-no" name="batch_no[]" value="{{$batch_no}}" data-product-id="{{$product->id}}" @if($product_batch_id) disabled @endif />
                                                	    @else
                                                	        <input type="text" class="form-control batch-no" name="batch_no[]" value="" disabled />
                                                	    @endif
                                                	</td>
                                                	<td>
                                                	    @if($product->is_batch)
                                                	        <input type="date" class="form-control expire-date" name="expire_date[]" value="{{$expired_date}}" @if($product_batch_id) disabled @endif />
                                                	    @else
                                                	        <input type="date" class="form-control expire-date" name="expire_date[]" value="" disabled />
                                                	    @endif
                                                	</td>
                                                	<td class="action">
                                                		<select name="action[]" class="form-control act-val">
                                                			@if($product_adjustment_data->action == '+')
                                                			<option value="+" selected>{{trans("file.Addition")}}</option>
                                                			<option value="-">{{trans("file.Subtraction")}}</option>
                                                			@else
                                                			<option value="-" selected>{{trans("file.Subtraction")}}</option>
                                                			<option value="+">{{trans("file.Addition")}}</option>
                                                			@endif
                                                		</select>
                                                	</td>
                                                	<td><button type="button" class="ibtnDel btn btn-md btn-danger">{{trans("file.delete")}}</button>
                                                	<input type="hidden" name="product_code[]" class="product-code" value="{{$product->code}}" />
                                                	<input type="hidden" class="product-id" name="product_id[]" value="{{$product->id}}" />
                                                    <input type="hidden" name="product_variant_id[]" value="{{$product_variant_id}}" />
                                                    <input type="hidden" name="product_batch_id[]" value="{{$product_batch_id}}" />
                                                    <input type="hidden" class="array-index" value="{{$loop->index}}" />
                                                    <input type="hidden" class="product-has-record" value="1" />
                                                	</td>
                                                	@endforeach
                                                	</tr>
                                                </tbody>
                                                <tfoot class="tfoot active">
                                                    <th colspan="2">{{trans('file.Total')}}</th>
                                                    <th id="total-qty" colspan="4">0</th>
                                                    <th><i class="dripicons-trash"></i></th>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_qty" />
                                            <input type="hidden" name="item" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>{{trans('file.Note')}}</label>
                                            <textarea rows="5" class="form-control" name="note">{{$lims_adjustment_data->note}}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <input type="submit" value="{{trans('file.submit')}}" class="btn btn-primary" id="submit-button">
                                </div>
                            </div>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#product").siblings('a').attr('aria-expanded','true');
    $("ul#product").addClass("show");
    $("ul#product #adjustment-create-menu").addClass("active");
// array data depend on warehouse
var lims_product_array = [];
var product_code = [];
var product_name = [];
var product_qty = [];
    var product_has_record = [];
    var batch_no = [];
    var product_batch_id = [];
    var expired_date = [];
    var is_batch = [];

var exist_code = [];
var exist_qty = [];

@foreach($lims_product_adjustment_data as $product_adjustment_data)
    @php
        $product = \App\Product::find($product_adjustment_data->product_id);
    @endphp
    exist_code.push('{{$product->code}}');
    exist_qty.push({{$product_adjustment_data->qty}});
@endforeach

var rownumber = $('table.order-list tbody tr:last').index();

for(rowindex  =0; rowindex <= rownumber; rowindex++){
    exist_code.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text());
    var quantity = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val());
    exist_qty.push(quantity);
}

	$('.selectpicker').selectpicker({
	    style: 'btn-link',
	});
	//assigning value
	$('select[name="warehouse_id"]').val($('input[name="warehouse_id_hidden"]').val());
	$('.selectpicker').selectpicker('refresh');
	calculateTotal();

	$('#lims_productcodeSearch').on('input', function(){
	    var warehouse_id = $('#warehouse_id').val();
	    temp_data = $('#lims_productcodeSearch').val();

	    if(!warehouse_id){
	        $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
	        alert('Please select Warehouse!');
	    }
	});

	var id = $('#warehouse_id').val();
    $.get('../getproduct/' + id, function(data) {
        lims_product_array = [];
        product_code = data[0];
        product_name = data[1];
        product_qty = data[2];
        product_has_record = data[3] || [];
        batch_no = data[4] || [];
        product_batch_id = data[5] || [];
        expired_date = data[6] || [];
        is_batch = data[7] || [];
        
        $.each(product_code, function(index) {
            if(exist_code.includes(product_code[index])) {
                pos = exist_code.indexOf(product_code[index]);
                // For existing products, we need to use the CURRENT warehouse stock for validation
                // The product_qty[index] from getProduct is the current warehouse stock
                // No need to modify it - it's already correct for validation
            }
            var labelName = product_name[index];
            if (is_batch[index] === 1 && product_batch_id[index]) {
                labelName += ' - Batch: ' + batch_no[index] + (expired_date[index] ? ' (Exp: ' + expired_date[index] + ')' : '');
                // Build autocomplete entry that carries batch_id and array index
                lims_product_array.push(product_code[index] + ' (' + labelName + ')|' + product_batch_id[index] + '|' + index);
            } else {
                var base = product_code[index] + ' (' + labelName + ')';
                if(product_has_record[index] === 0) base += ' [No stock record]';
                lims_product_array.push(base + '||' + index);
            }
        });
    });

	var lims_productcodeSearch = $('#lims_productcodeSearch');

	lims_productcodeSearch.autocomplete({
	    source: function(request, response) {
	        var matcher = new RegExp(".?" + $.ui.autocomplete.escapeRegex(request.term), "i");
	        response($.grep(lims_product_array, function(item) {
	            return matcher.test(item);
	        }));
	    },
        response: function(event, ui) {
            if (ui.content.length == 1) {
                var data = ui.content[0].value;
                $(this).autocomplete( "close" );
                productSearch(data);
            };
        },
	    select: function(event, ui) {
	        var data = ui.item.value;
            productSearch(data);
	    }
	});

	$('select[name="warehouse_id"]').on('change', function() {
	    var id = $('#warehouse_id').val();
	    $.get('../getproduct/' + id, function(data) {
	        lims_product_array = [];
	        product_code = data[0];
	        product_name = data[1];
	        product_qty = data[2];
	        product_has_record = data[3] || [];
	        batch_no = data[4] || [];
	        product_batch_id = data[5] || [];
	        expired_date = data[6] || [];
	        is_batch = data[7] || [];
	        $.each(product_code, function(index) {
	            var labelName = product_name[index];
	            if (is_batch[index] === 1 && product_batch_id[index]) {
	                labelName += ' - Batch: ' + batch_no[index] + (expired_date[index] ? ' (Exp: ' + expired_date[index] + ')' : '');
	                // Build autocomplete entry that carries batch_id and array index
	                lims_product_array.push(product_code[index] + ' (' + labelName + ')|' + product_batch_id[index] + '|' + index);
	            } else {
	                var base = product_code[index] + ' (' + labelName + ')';
	                if(product_has_record[index] === 0) base += ' [No stock record]';
	                lims_product_array.push(base + '||' + index);
	            }
	        });
	    });
	});

	$("#myTable").on('input', '.qty', function() {
	    rowindex = $(this).closest('tr').index();
	    checkQuantity($(this).val(), true);
	});

    // Keep stock hint (under name) updated if needed
    $("#myTable").on('change', '.act-val', function() {
        rowindex = $(this).closest('tr').index();
        var pos = parseInt($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.array-index').val());
        if(pos !== undefined && product_qty[pos] !== undefined) {
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.stock-hint').text('Stock: ' + (parseFloat(product_qty[pos]) || 0));
        }
	});

	$("table.order-list tbody").on("click", ".ibtnDel", function(event) {
	    rowindex = $(this).closest('tr').index();
	    $(this).closest("tr").remove();
	    calculateTotal();
	});

    $(window).keydown(function(e){
        if (e.which == 13) {
            var $targ = $(e.target);
            if (!$targ.is("textarea") && !$targ.is(":button,:submit")) {
                var focusNext = false;
                $(this).find(":input:visible:not([disabled],[readonly]), a").each(function(){
                    if (this === e.target) {
                        focusNext = true;
                    }
                    else if (focusNext){
                        $(this).focus();
                        return false;
                    }
                });
                return false;
            }
        }
    });

	// Batch number validation
	$(document).on('blur', '.batch-no', function() {
		var batchInput = $(this);
		var batchNo = batchInput.val().trim();
		var productId = batchInput.data('product-id');
		var currentBatchId = batchInput.closest('tr').find('.product-batch-id').val();
		
		// Skip validation if already validated or if input is disabled
		if (batchNo && productId && !batchInput.prop('disabled') && !batchInput.data('validated')) {
			// Mark as being validated to prevent multiple requests
			batchInput.data('validated', true);
			
			$.ajax({
				url: '{{ route("adjustment.check_batch_number") }}',
				type: 'POST',
				data: {
					product_id: productId,
					batch_no: batchNo,
					exclude_batch_id: currentBatchId || null,
					_token: $('meta[name="csrf-token"]').attr('content')
				},
				success: function(response) {
					if (response.exists) {
						alert('Batch number "' + batchNo + '" already exists for this product!');
						// Clear the input and reset validation flag
						batchInput.val('').data('validated', false);
						batchInput.focus();
					} else {
						// Reset validation flag on successful validation
						batchInput.data('validated', false);
					}
				},
				error: function() {
					console.log('Error checking batch number');
					// Reset validation flag on error
					batchInput.data('validated', false);
				}
			});
		}
	});
	
	// Reset validation flag when user starts typing
	$(document).on('input', '.batch-no', function() {
		$(this).data('validated', false);
    });

    $('#adjustment-form').on('submit',function(e){
        var rownumber = $('table.order-list tbody tr:last').index();
        if (rownumber < 0) {
            alert("Please insert product to order table!")
            e.preventDefault();
	    }
        else {
            // Re-enable disabled inputs so they get submitted
            $('.batch-no, .expire-date').prop('disabled', false);
            var product_ids = [];
            var product_codes = [];
            var product_batch_ids = [];
            var batch_nos = [];
            var expire_dates = [];
            var quantities = [];
            var actions = [];
            $('table.order-list tbody tr').each(function(index){
                var pid = $(this).find('.product-id').val();
                var pcode = $(this).find('.product-code').val();
                var pbatch = $(this).find('.product-batch-id').val() || '';
                var batchNo = $(this).find('.batch-no').val() || '';
                var expireDate = $(this).find('.expire-date').val() || '';
                var qty = $(this).find('.qty').val();
                var action = $(this).find('.act-val').val();
                product_ids.push(pid);
                product_codes.push(pcode);
                product_batch_ids.push(pbatch);
                batch_nos.push(batchNo);
                expire_dates.push(expireDate);
                quantities.push(qty);
                actions.push(action);
            });
        }
    });

    function productSearch(data){
        $.ajax({
            type: 'GET',
            url: '../lims_product_search',
            data: {
                data: data
            },
            success: function(data) {
                var flag = 1;
                var parts = $("#lims_productcodeSearch").val().split('|');
                var selected_code_and_name = parts[0];
                var selected_batch_id = parts.length > 1 && parts[1] !== '' ? parts[1] : '';
                var selected_index = parts.length > 2 ? parseInt(parts[2]) : product_code.indexOf(data[1]);

                $("table.order-list tbody tr").each(function(i) {
                    var existing_code = $(this).find('.product-code').val();
                    var existing_batch = $(this).find('.product-batch-id').val() || '';
                    if (existing_code == product_code[selected_index] && existing_batch == (selected_batch_id || '')) {
                        rowindex = i;
                        var qty = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val()) + 1;
                        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(qty);
                        checkQuantity(qty);
                        flag = 0;
                    }
                });
                $("input[name='product_code_name']").val('');
                if(flag){
                    var newRow = $("<tr>");
                    var cols = '';
                    var displayName = data[0];
                    if (is_batch[selected_index] === 1 && product_batch_id[selected_index]) {
                        displayName += ' - Batch: ' + batch_no[selected_index] + (expired_date[selected_index] ? ' (Exp: ' + expired_date[selected_index] + ')' : '');
                    }
                    cols += '<td>' + displayName + '<small class="text-muted d-block stock-hint">Stock: ' + (parseFloat(product_qty[selected_index]) || 0) + '</small></td>';
                    cols += '<td>' + product_code[selected_index] + '</td>';
                    var pos = selected_index;
                    cols += '<td>'+
                        '<input type="number" class="form-control qty" name="qty[]" value="1" min="0" required step="any" />'+
                    '</td>';
                    
                    // Batch Number and Expire Date columns
                    var isBatched = is_batch[pos] === 1;
                    var hasRecord = product_has_record[pos] === 1;
                    var hasBatchData = isBatched && product_batch_id[pos];
                    
                    // Batch Number column
                    if (isBatched) {
                        if (hasBatchData) {
                            // Auto-fill and disable for existing batch
                            cols += '<td><input type="text" class="form-control batch-no" name="batch_no[]" value="' + (batch_no[pos] || '') + '" disabled /></td>';
                        } else if (hasRecord) {
                            // Empty, editable, not required for existing product without batch
                            cols += '<td><input type="text" class="form-control batch-no" name="batch_no[]" value="" data-product-id="' + data[2] + '" /></td>';
                        } else {
                            // Required for new product
                            cols += '<td><input type="text" class="form-control batch-no" name="batch_no[]" value="" required data-product-id="' + data[2] + '" /></td>';
                        }
                    } else {
                        // Disabled for non-batched products
                        cols += '<td><input type="text" class="form-control batch-no" name="batch_no[]" value="" disabled /></td>';
                    }
                    
                    // Expire Date column
                    if (isBatched) {
                        if (hasBatchData) {
                            // Auto-fill and disable for existing batch
                            cols += '<td><input type="date" class="form-control expire-date" name="expire_date[]" value="' + (expired_date[pos] || '') + '" disabled /></td>';
                        } else if (hasRecord) {
                            // Empty, editable, not required for existing product without batch
                            cols += '<td><input type="date" class="form-control expire-date" name="expire_date[]" value="" /></td>';
                        } else {
                            // Required for new product
                            cols += '<td><input type="date" class="form-control expire-date" name="expire_date[]" value="" required /></td>';
                        }
                    } else {
                        // Disabled for non-batched products
                        cols += '<td><input type="date" class="form-control expire-date" name="expire_date[]" value="" disabled /></td>';
                    }
                    
                    var hasRecord = product_has_record[pos] === 1;
                    var actionSelect = '<select name="action[]" class="form-control act-val">';
                    if(hasRecord) {
                        actionSelect += '<option value="-">{{trans("file.Subtraction")}}</option>';
                    }
                    actionSelect += '<option value="+">{{trans("file.Addition")}}</option>';
                    actionSelect += '</select>';
                    cols += '<td class="action">' + actionSelect + (hasRecord ? '' : ' <span class="badge badge-warning">No warehouse record</span>') + '</td>';
                    cols += '<td><button type="button" class="ibtnDel btn btn-md btn-danger">{{trans("file.delete")}}</button></td>';
                    // Store only the raw code for backend processing
                    cols += '<input type="hidden" class="product-code" name="product_code[]" value="' + product_code[selected_index] + '"/>';
                    cols += '<input type="hidden" class="product-id" name="product_id[]" value="' + data[2] + '"/>';
                    cols += '<input type="hidden" class="product-batch-id" name="product_batch_id[]" value="' + (selected_batch_id || '') + '"/>';
                    cols += '<input type="hidden" class="array-index" value="' + selected_index + '"/>';

                    newRow.append(cols);
                    $("table.order-list tbody").append(newRow);
                    rowindex = newRow.index();
                    calculateTotal();
                }
            }
        });
    }

	function checkQuantity(qty) {
	    var row_product_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text();
        var action = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.act-val option:selected').val();
        var pos = parseInt($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.array-index').val());


        // Prevent subtraction for products without warehouse record
        var hasRecord = product_has_record[pos] === 1;
        
        // For existing products, also check the hidden input
        var hiddenHasRecord = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-has-record').val();
        if(hiddenHasRecord !== undefined && hiddenHasRecord !== '') {
            hasRecord = hiddenHasRecord === '1';
        }

        if (!hasRecord && action == '-') {
            alert('Cannot subtract: no stock record for this product in the selected warehouse.');
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.act-val').val('+');
        }
        else if (action == '-') {
            // Get current warehouse stock directly from the stock display under product name
            var stockText = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(1) small').text();
            var current_stock = 0;
            if (stockText && stockText.includes('Stock:')) {
                current_stock = parseFloat(stockText.replace('Stock:', '').trim());
            }
            
            // For edit mode: calculate available stock for subtraction
            // Available stock = current stock + original adjustment quantity
            // This gives us the stock that was available before the original adjustment
            var original_qty = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').attr('data-original-value') || $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val());
            var available_stock = current_stock + original_qty;
            
            if (qty > available_stock) {
                alert('Quantity exceeds stock quantity! Available: ' + available_stock);
            var row_qty = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val();
            row_qty = row_qty.substring(0, row_qty.length - 1);
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(row_qty);
            } else {
                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(qty);
            }
	    }
	    else {
	        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(qty);
	    }
        calculateTotal();
	}

	function calculateTotal() {
	    var total_qty = 0;
	    $(".qty").each(function() {

	        if ($(this).val() == '') {
	            total_qty += 0;
	        } else {
	            total_qty += parseFloat($(this).val());
	        }
	    });
	    $("#total-qty").text(total_qty);
	    $('input[name="total_qty"]').val(total_qty);
	    $('input[name="item"]').val($('table.order-list tbody tr:last').index() + 1);
	}
</script>
@endpush

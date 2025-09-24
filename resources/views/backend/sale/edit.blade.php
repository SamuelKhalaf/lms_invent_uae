@extends('backend.layout.main') @section('content')
@if(session()->has('not_permitted'))
<div class="alert alert-danger alert-dismissible text-center">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    {{ session()->get('not_permitted') }}
</div>
@endif
@if(session()->has('error'))
<div class="alert alert-danger alert-dismissible text-center">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    {{ session()->get('error') }}
</div>
@endif
@if(session()->has('message'))
<div class="alert alert-success alert-dismissible text-center">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    {!! session()->get('message') !!}
</div>
@endif
@if($errors->any())
<div class="alert alert-danger alert-dismissible text-center">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    <ul class="list-unstyled mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{trans('file.Update Sale')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => ['sales.update', $lims_sale_data->id], 'method' => 'put', 'files' => true, 'id' => 'payment-form']) !!}
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Date')}}</label>
                                            <input type="text" name="created_at" class="form-control date" value="{{date($general_setting->date_format, strtotime($lims_sale_data->created_at->toDateString()))}}" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.reference')}}</label>
                                            <p><strong>{{ $lims_sale_data->reference_no }}</strong></p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.customer')}} *</label>
                                            <input type="hidden" name="customer_id_hidden" value="{{ $lims_sale_data->customer_id }}" />
                                            <select required name="customer_id" class="selectpicker form-control" data-live-search="true" id="customer_id" title="Select customer...">
                                                @foreach($lims_customer_list as $customer)
                                                <option value="{{$customer->id}}">{{$customer->name . ' (' . $customer->phone_number . ')'}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Warehouse')}} *</label>
                                            <input type="hidden" name="warehouse_id_hidden" value="{{$lims_sale_data->warehouse_id}}" />
                                            <select required id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select warehouse...">
                                                @foreach($lims_warehouse_list as $warehouse)
                                                <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Biller')}} *</label>
                                            <input type="hidden" name="biller_id_hidden" value="{{$lims_sale_data->biller_id}}" />
                                            <select required name="biller_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Biller...">
                                                @foreach($lims_biller_list as $biller)
                                                <option value="{{$biller->id}}">{{$biller->name . ' (' . $biller->company_name . ')'}}</option>
                                                @endforeach
                                            </select>
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
                                                        <th>{{trans('file.Batch No')}}</th>
                                                        <th>{{trans('file.Expired Date')}}</th>
                                                        <th>{{trans('file.Net Unit Price')}}</th>
                                                        <th>{{trans('file.Discount')}}</th>
                                                        <th>{{trans('file.Tax')}}</th>
                                                        <th>{{trans('file.Subtotal')}}</th>
                                                        <th><i class="dripicons-trash"></i></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $temp_unit_name = [];
                                                    $temp_unit_operator = [];
                                                    $temp_unit_operation_value = [];
                                                    ?>
                                                    @foreach($lims_product_sale_data as $product_sale)
                                                    <tr>
                                                    <?php
                                                        $product_data = DB::table('products')->find($product_sale->product_id);
                                                        if($product_sale->variant_id){
                                                            $product_variant_data = \App\ProductVariant::select('id', 'item_code')->FindExactProduct($product_data->id, $product_sale->variant_id)->first();
                                                            $product_variant_id = $product_variant_data->id;
                                                            $product_data->code = $product_variant_data->item_code;
                                                        }
                                                        else
                                                            $product_variant_id = null;
                                                        if($product_data->tax_method == 1){
                                                            $product_price = $product_sale->net_unit_price + ($product_sale->discount / $product_sale->qty);
                                                        }
                                                        elseif ($product_data->tax_method == 2) {
                                                            $product_price =($product_sale->total / $product_sale->qty) + ($product_sale->discount / $product_sale->qty);
                                                        }

                                                        $tax = DB::table('taxes')->where('rate',$product_sale->tax_rate)->first();
                                                        $unit_name = array();
                                                        $unit_operator = array();
                                                        $unit_operation_value = array();
                                                        if($product_data->type == 'standard'){
                                                            $units = DB::table('units')->where('base_unit', $product_data->unit_id)->orWhere('id', $product_data->unit_id)->get();

                                                            foreach($units as $unit) {
                                                                if($product_sale->sale_unit_id == $unit->id) {
                                                                    array_unshift($unit_name, $unit->unit_name);
                                                                    array_unshift($unit_operator, $unit->operator);
                                                                    array_unshift($unit_operation_value, $unit->operation_value);
                                                                }
                                                                else {
                                                                    $unit_name[]  = $unit->unit_name;
                                                                    $unit_operator[] = $unit->operator;
                                                                    $unit_operation_value[] = $unit->operation_value;
                                                                }
                                                            }
                                                            if($unit_operator[0] == '*'){
                                                                $product_price = $product_price / $unit_operation_value[0];
                                                            }
                                                            elseif($unit_operator[0] == '/'){
                                                                $product_price = $product_price * $unit_operation_value[0];
                                                            }
                                                        }
                                                        else {
                                                            $unit_name[] = 'n/a'. ',';
                                                            $unit_operator[] = 'n/a'. ',';
                                                            $unit_operation_value[] = 'n/a'. ',';
                                                        }
                                                        $temp_unit_name = $unit_name = implode(",",$unit_name) . ',';

                                                        $temp_unit_operator = $unit_operator = implode(",",$unit_operator) .',';

                                                        $temp_unit_operation_value = $unit_operation_value =  implode(",",$unit_operation_value) . ',';

                                                        $product_batch_data = \App\ProductBatch::select('batch_no', 'expired_date')->find($product_sale->product_batch_id);
                                                    ?>
                                                        <td>{{$product_data->name}} <button type="button" class="edit-product btn btn-link" data-toggle="modal" data-target="#editModal"> <i class="dripicons-document-edit"></i></button> <input type="hidden" class="product-type" value="{{$product_data->type}}" /></td>
                                                        <td>{{$product_data->code}}</td>
                                                        <td><input type="number" class="form-control qty" name="qty[]" value="{{$product_sale->qty}}" step="any" required/></td>
                                                        @if($product_batch_data)
                                                        <td>
                                                            <input type="hidden" class="product-batch-id" name="product_batch_id[]" value="{{$product_sale->product_batch_id}}">
                                                            <input type="hidden" class="batch-id" name="batch_id[]" value="{{$product_sale->product_batch_id}}">
                                                            <input type="hidden" class="is-batch" name="is_batch[]" value="1">
                                                            <input type="text" class="form-control batch-no" name="batch_no[]" value="{{$product_batch_data->batch_no}}" disabled/>
                                                        </td>
                                                        <td>{{date($general_setting->date_format, strtotime($product_batch_data->expired_date))}}</td>
                                                        @else
                                                        <td>
                                                            <input type="hidden" class="product-batch-id" name="product_batch_id[]" value="">
                                                            <input type="hidden" class="batch-id" name="batch_id[]" value="">
                                                            <input type="hidden" class="is-batch" name="is_batch[]" value="0">
                                                            <input type="text" class="form-control batch-no" name="batch_no[]" value="N/A" disabled />
                                                        </td>
                                                        <td>N/A</td>
                                                        @endif
                                                        <td class="net_unit_price">{{ number_format((float)$product_sale->net_unit_price, 2, '.', '') }} </td>
                                                        <td class="discount">{{ number_format((float)$product_sale->discount, 2, '.', '') }}</td>
                                                        <td class="tax">{{ number_format((float)$product_sale->tax, 2, '.', '') }}</td>
                                                        <td class="sub-total">{{ number_format((float)$product_sale->total, 2, '.', '') }}</td>
                                                        <td><button type="button" class="ibtnDel btn btn-md btn-danger">{{trans("file.delete")}}</button></td>
                                                        <input type="hidden" class="product-code" name="product_code[]" value="{{$product_data->code}}"/>
                                                        <input type="hidden" class="product-id" name="product_id[]" value="{{$product_data->id}}"/>
                                                        <input type="hidden" name="product_variant_id[]" value="{{$product_variant_id}}"/>
                                                        <input type="hidden" class="product-price" name="product_price[]" value="{{$product_price}}"/>
                                                        <input type="hidden" class="sale-unit" name="sale_unit[]" value="{{$unit_name}}"/>
                                                        <input type="hidden" class="sale-unit-operator" value="{{$unit_operator}}"/>
                                                        <input type="hidden" class="sale-unit-operation-value" value="{{$unit_operation_value}}"/>
                                                        <input type="hidden" class="net_unit_price" name="net_unit_price[]" value="{{$product_sale->net_unit_price}}" />
                                                        <input type="hidden" class="discount-value" name="discount[]" value="{{$product_sale->discount}}" />
                                                        <input type="hidden" class="tax-rate" name="tax_rate[]" value="{{$product_sale->tax_rate}}"/>
                                                        @if($tax)
                                                        <input type="hidden" class="tax-name" value="{{$tax->name}}" />
                                                        @else
                                                        <input type="hidden" class="tax-name" value="No Tax" />
                                                        @endif
                                                        <input type="hidden" class="tax-method" value="{{$product_data->tax_method}}"/>
                                                        <input type="hidden" class="tax-value" name="tax[]" value="{{$product_sale->tax}}" />
                                                        <input type="hidden" class="subtotal-value" name="subtotal[]" value="{{$product_sale->total}}" />
                                                        <input type="hidden" class="imei-number" name="imei_number[]"  value="{{$product_sale->imei_number}}" />
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="tfoot active">
                                                    <th colspan="2">{{trans('file.Total')}}</th>
                                                    <th id="total-qty">{{$lims_sale_data->total_qty}}</th>
                                                    <th></th>
                                                    <th></th>
                                                    <th></th>
                                                    <th id="total-discount">{{ number_format((float)$lims_sale_data->total_discount, 2, '.', '') }}</th>
                                                    <th id="total-tax">{{ number_format((float)$lims_sale_data->total_tax, 2, '.', '')}}</th>
                                                    <th id="total">{{ number_format((float)$lims_sale_data->total_price, 2, '.', '') }}</th>
                                                    <th><i class="dripicons-trash"></i></th>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_qty" value="{{$lims_sale_data->total_qty}}" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_discount" value="{{$lims_sale_data->total_discount}}" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_tax" value="{{$lims_sale_data->total_tax}}" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_price" value="{{$lims_sale_data->total_price}}" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="item" value="{{$lims_sale_data->item}}" />
                                            <input type="hidden" name="order_tax" value="{{$lims_sale_data->order_tax}}"/>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        @if($lims_sale_data->coupon_id)
                                            @php
                                                $coupon_data = DB::table('coupons')->find($lims_sale_data->coupon_id);
                                            @endphp
                                            <input type="hidden" name="coupon_active" value="1" />
                                            <input type="hidden" name="coupon_type" value="{{$coupon_data->type}}" />
                                            <input type="hidden" name="coupon_amount" value="{{$coupon_data->amount}}" />
                                            <input type="hidden" name="coupon_minimum_amount" value="{{$coupon_data->minimum_amount}}" />
                                            <input type="hidden" name="coupon_discount" value="{{$lims_sale_data->coupon_discount}}">

                                        @else
                                            <input type="hidden" name="coupon_active" value="0" />
                                        @endif
                                        <div class="form-group">
                                            <input type="hidden" name="grand_total" value="{{$lims_sale_data->grand_total}}" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <input type="hidden" name="order_tax_rate_hidden" value="{{$lims_sale_data->order_tax_rate}}">
                                            <label>{{trans('file.Order Tax')}}</label>
                                            <select class="form-control" name="order_tax_rate">
                                                <option value="0">No Tax</option>
                                                @foreach($lims_tax_list as $tax)
                                                <option value="{{$tax->rate}}">{{$tax->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Order Discount Type')}}</label>
                                            <select class="form-control" name="order_discount_type">
                                                @if($lims_sale_data->order_discount_type == 'Percentage')
                                                <option value="Percentage">Percentage</option>
                                                <option value="Flat">Flat</option>
                                                @else
                                                <option value="Flat">Flat</option>
                                                <option value="Percentage">Percentage</option>
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>
                                                {{trans('file.Order Discount Value')}}
                                            </label>
                                            <input type="number" name="order_discount_value" class="form-control" value="@if($lims_sale_data->order_discount_value){{$lims_sale_data->order_discount_value}}@else{{$lims_sale_data->order_discount}}@endif" step="any" />
                                            <input type="hidden" name="order_discount" value="{{$lims_sale_data->order_discount}}" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>
                                                {{trans('file.Shipping Cost')}}
                                            </label>
                                            <input type="number" name="shipping_cost" class="form-control" value="{{$lims_sale_data->shipping_cost}}" step="any" />
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Attach Document')}}</label> <i class="dripicons-question" data-toggle="tooltip" title="Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported"></i>
                                            <input type="file" name="document" class="form-control" />
                                            @if($errors->has('extension'))
                                                <span>
                                                   <strong>{{ $errors->first('extension') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Sale Status')}} *</label>
                                            <input type="hidden" name="sale_status_hidden" value="{{$lims_sale_data->sale_status}}" />
                                            <select name="sale_status" class="form-control">
                                                <option value="1">{{trans('file.Completed')}}</option>
                                                <option value="2">{{trans('file.Pending')}}</option>
                                            </select>
                                        </div>
                                    </div>
                                    @if($lims_sale_data->coupon_id)
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>
                                                <strong>{{trans('file.Coupon Discount')}}</strong>
                                            </label>
                                            <p class="mt-2 pl-2"><strong id="coupon-text">{{ number_format((float)$lims_sale_data->coupon_discount, 2, '.', '') }}</strong></p>
                                        </div>
                                    </div>
                                    @endif
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{trans('file.Sale Note')}}</label>
                                            <textarea rows="5" class="form-control" name="sale_note" >{{ $lims_sale_data->sale_note }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{trans('file.Staff Note')}}</label>
                                            <textarea rows="5" class="form-control" name="staff_note">{{ $lims_sale_data->staff_note }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <input type="hidden" name="payment_status" value="{{$lims_sale_data->payment_status}}" />
                                            <input type="hidden" name="paid_amount" value="{{$lims_sale_data->paid_amount}}" />
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
    <div class="container-fluid">
        <table class="table table-bordered table-condensed totals">
            <td><strong>{{trans('file.Items')}}</strong>
                <span class="pull-right" id="item">0.00</span>
            </td>
            <td><strong>{{trans('file.Total')}}</strong>
                <span class="pull-right" id="subtotal">0.00</span>
            </td>
            <td><strong>{{trans('file.Order Tax')}}</strong>
                <span class="pull-right" id="order_tax">0.00</span>
            </td>
            <td><strong>{{trans('file.Order Discount')}}</strong>
                <span class="pull-right" id="order_discount">0.00</span>
            </td>
            <td><strong>{{trans('file.Shipping Cost')}}</strong>
                <span class="pull-right" id="shipping_cost">0.00</span>
            </td>
            <td><strong>{{trans('file.grand total')}}</strong>
                <span class="pull-right" id="grand_total">0.00</span>
            </td>
        </table>
    </div>
    <div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="modal_header" class="modal-title"></h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row modal-element">
                            <div class="col-md-4 form-group">
                                <label>{{trans('file.Quantity')}}</label>
                                <input type="number" step="any" name="edit_qty" class="form-control">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{trans('file.Unit Discount')}}</label>
                                <input type="number" name="edit_discount" class="form-control">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{trans('file.Unit Price')}}</label>
                                <input type="number" name="edit_unit_price" class="form-control" step="any">
                            </div>
                            <?php
                                $tax_name_all[] = 'No Tax';
                                $tax_rate_all[] = 0;
                                foreach($lims_tax_list as $tax) {
                                    $tax_name_all[] = $tax->name;
                                    $tax_rate_all[] = $tax->rate;
                                }
                            ?>
                            <div class="col-md-4 form-group">
                                <label>{{trans('file.Tax Rate')}}</label>
                                <select name="edit_tax_rate" class="form-control selectpicker">
                                    @foreach($tax_name_all as $key => $name)
                                    <option value="{{$key}}">{{$name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="edit_unit" class="col-md-4 form-group">
                                <label>{{trans('file.Product Unit')}}</label>
                                <select name="edit_unit" class="form-control selectpicker">
                                </select>
                            </div>
                        </div>
                        <button type="button" name="update_btn" class="btn btn-primary">{{trans('file.update')}}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- add cash register modal -->
    <div id="cash-register-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
          <div class="modal-content">
            {!! Form::open(['route' => 'cashRegister.store', 'method' => 'post']) !!}
            <div class="modal-header">
              <h5 id="exampleModalLabel" class="modal-title">{{trans('file.Add Cash Register')}}</h5>
              <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
              <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                <div class="row">
                  <div class="col-md-6 form-group warehouse-section">
                      <label>{{trans('file.Warehouse')}} *</strong> </label>
                      <select required name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select warehouse...">
                          @foreach($lims_warehouse_list as $warehouse)
                          <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                          @endforeach
                      </select>
                  </div>
                  <div class="col-md-6 form-group">
                      <label>{{trans('file.Cash in Hand')}} *</strong> </label>
                      <input type="number" name="cash_in_hand" required class="form-control">
                  </div>
                  <div class="col-md-12 form-group">
                      <button type="submit" class="btn btn-primary">{{trans('file.submit')}}</button>
                  </div>
                </div>
            </div>
            {{ Form::close() }}
          </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script type="text/javascript">
    // Prevent automatic alert dismissal for this page only
    $(document).ready(function() {
        // Override the global alert auto-dismiss behavior
        $("div.alert").off('slideUp');
        $("div.alert").removeClass('fade');
        
        // Remove any existing timeouts that might be set by the global script
        $("div.alert").stop(true, true);
    });
</script>
@endpush

@push('scripts')
<script type="text/javascript">

    $("#card-element").hide();
    $("#cheque").hide();

    // array data depend on warehouse
    var lims_product_array = [];
    var product_code = [];
    var product_name = [];
    var product_qty = [];
    var product_type = [];
    var product_id = [];
    var product_list = [];
    var qty_list = [];

    // array data with selection
    var product_price = [];
    var product_discount = [];
    var tax_rate = [];
    var tax_name = [];
    var tax_method = [];
    var unit_name = [];
    var unit_operator = [];
    var unit_operation_value = [];
    var is_imei = [];

    // temporary array
    var temp_unit_name = [];
    var temp_unit_operator = [];
    var temp_unit_operation_value = [];

    var exist_type = [];
    var exist_code = [];
    var exist_qty = [];
    var rowindex;
    var customer_group_rate;
    var row_product_price;
    var currency = <?php echo json_encode($currency) ?>;
    var role_id = <?php echo json_encode(Auth::user()->role_id)?>;

    var rownumber = $('table.order-list tbody tr:last').index();

    for(rowindex  =0; rowindex <= rownumber; rowindex++){
        product_price.push(parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-price').val()));
        exist_code.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text());
        exist_type.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-type').val());
        var total_discount = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.discount').text());
        var quantity = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val());
        exist_qty.push(quantity);
        product_discount.push((total_discount / quantity).toFixed(2));
        tax_rate.push(parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-rate').val()));
        tax_name.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-name').val());
        tax_method.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-method').val());
        temp_unit_name = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit').val().split(',');
        unit_name.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit').val());
        unit_operator.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit-operator').val());
        unit_operation_value.push($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit-operation-value').val());
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit').val(temp_unit_name[0]);
    }

    $('.selectpicker').selectpicker({
        style: 'btn-link',
    });

    $('[data-toggle="tooltip"]').tooltip();

    // Add real-time price validation with debounce
    var priceValidationTimeout;
    $('#editModal').on('input', 'input[name="edit_unit_price"]', function() {
        var product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-id').val();
        var unit_price = $(this).val();
        
        // Clear previous timeout
        clearTimeout(priceValidationTimeout);
        
        if (product_id && unit_price && !isNaN(unit_price) && unit_price > 0) {
            // Debounce the validation call by 500ms
            priceValidationTimeout = setTimeout(function() {
                validatePriceRealTime(product_id, unit_price);
            }, 500);
        } else {
            // Clear validation messages if input is invalid
            $('#editModal .price-validation-message').remove();
            $('input[name="edit_unit_price"]').removeClass('is-invalid is-valid');
        }
    });

    //assigning value
    $('select[name="customer_id"]').val($('input[name="customer_id_hidden"]').val());
    $('select[name="warehouse_id"]').val($('input[name="warehouse_id_hidden"]').val());
    $('select[name="biller_id"]').val($('input[name="biller_id_hidden"]').val());
    $('select[name="sale_status"]').val($('input[name="sale_status_hidden"]').val());
    $('select[name="order_tax_rate"]').val($('input[name="order_tax_rate_hidden"]').val());
    $('.selectpicker').selectpicker('refresh');

    $('#item').text($('input[name="item"]').val() + '(' + $('input[name="total_qty"]').val() + ')');
    $('#subtotal').text(parseFloat($('input[name="total_price"]').val()).toFixed(2));
    $('#order_tax').text(parseFloat($('input[name="order_tax"]').val()).toFixed(2));
    if(!$('input[name="order_discount"]').val())
        $('input[name="order_discount"]').val('0.00');
    $('#order_discount').text(parseFloat($('input[name="order_discount"]').val()).toFixed(2));
    if(!$('input[name="shipping_cost"]').val())
        $('input[name="shipping_cost"]').val('0.00');
    $('#shipping_cost').text(parseFloat($('input[name="shipping_cost"]').val()).toFixed(2));
    $('#grand_total').text(parseFloat($('input[name="grand_total"]').val()).toFixed(2));

    var id = $('select[name="customer_id"]').val();
    $.get('../getcustomergroup/' + id, function(data) {
        customer_group_rate = (data / 100);
    });

    var id = $('select[name="warehouse_id"]').val();
    $.get('../getproduct/' + id, function(data) {
        lims_product_array = [];
        product_code = data[0];
        product_name = data[1];
        product_qty = data[2];
        product_type = data[3];
        product_id = data[4];
        product_list = data[5];
        qty_list = data[6];
        product_warehouse_price = data[7];
        batch_no = data[8];
        product_batch_id = data[9];
        expired_date = data[10];
        is_embeded = data[11];
        $.each(product_code, function(index) {
            if(exist_code.includes(product_code[index])) {
                pos = exist_code.indexOf(product_code[index]);
                product_qty[index] = product_qty[index] + exist_qty[pos];
                exist_code.splice(pos, 1);
                exist_qty.splice(pos, 1);
            }
            
            var display_name = product_name[index];
            var data_string = '';
            
            // Add batch information to display name
            if(batch_no[index] && batch_no[index] !== null) {
                display_name += ' - Batch: ' + batch_no[index];
                if(expired_date[index]) {
                    display_name += ' (Exp: ' + expired_date[index] + ')';
                }
            } else {
                display_name += ' - No Batch';
            }
            
            // Create data string with batch info
            if(is_embeded[index]) {
                data_string = product_code[index] + ' (' + display_name + ')|' + is_embeded[index] + '|' + (product_batch_id[index] || '') + '|' + index;
            } else {
                data_string = product_code[index] + ' (' + display_name + ')|' + (product_batch_id[index] || '') + '|' + index;
            }
            
            lims_product_array.push(data_string);
        });
        $.each(exist_code, function(index) {
            product_type.push(exist_type[index]);
            product_code.push(exist_code[index]);
            product_qty.push(exist_qty[index]);
        });
    });

    isCashRegisterAvailable(id);

    $('select[name="customer_id"]').on('change', function() {
        var id = $(this).val();
        $.get('../getcustomergroup/' + id, function(data) {
            customer_group_rate = (data / 100);
        });
    });

    $('select[name="warehouse_id"]').on('change', function() {
        var id = $(this).val();
        $.get('../getproduct/' + id, function(data) {
            lims_product_array = [];
            product_code = data[0];
            product_name = data[1];
            product_qty = data[2];
            product_type = data[3];
            product_id = data[4];
            product_list = data[5];
            qty_list = data[6];
            product_warehouse_price = data[7];
            batch_no = data[8];
            product_batch_id = data[9];
            expired_date = data[10];
            is_embeded = data[11];
            $.each(product_code, function(index) {
                var display_name = product_name[index];
                var data_string = '';
                
                // Add batch information to display name
                if(batch_no[index] && batch_no[index] !== null) {
                    display_name += ' - Batch: ' + batch_no[index];
                    if(expired_date[index]) {
                        display_name += ' (Exp: ' + expired_date[index] + ')';
                    }
                } else {
                    display_name += ' - No Batch';
                }
                
                // Create data string with batch info
                if(is_embeded[index]) {
                    data_string = product_code[index] + ' (' + display_name + ')|' + is_embeded[index] + '|' + (product_batch_id[index] || '') + '|' + index;
                } else {
                    data_string = product_code[index] + ' (' + display_name + ')|' + (product_batch_id[index] || '') + '|' + index;
                }
                
                lims_product_array.push(data_string);
            });
        });
        isCashRegisterAvailable(id);
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
            }
            else if(ui.content.length == 0 && $('#lims_productcodeSearch').val().length == 13) {
              productSearch($('#lims_productcodeSearch').val()+'|'+1);
            }
        },
        select: function(event, ui) {
            var data = ui.item.value;
            productSearch(data);
        }
    });

    //Change quantity
    $("#myTable").on('input', '.qty', function() {
        rowindex = $(this).closest('tr').index();
        if($(this).val() < 1 && $(this).val() != '') {
          $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(1);
          alert("Quantity can't be less than 1");
        }
        checkDiscount($(this).val(), true);
        //checkQuantity($(this).val(), true);
    });


    //Delete product
    $("table.order-list tbody").on("click", ".ibtnDel", function(event) {
        rowindex = $(this).closest('tr').index();
        product_price.splice(rowindex, 1);
        product_discount.splice(rowindex, 1);
        tax_rate.splice(rowindex, 1);
        tax_name.splice(rowindex, 1);
        tax_method.splice(rowindex, 1);
        unit_name.splice(rowindex, 1);
        unit_operator.splice(rowindex, 1);
        unit_operation_value.splice(rowindex, 1);
        $(this).closest("tr").remove();
        calculateTotal();
    });

    //Edit product
    $("table.order-list").on("click", ".edit-product", function() {
        rowindex = $(this).closest('tr').index();
        edit();
    });

    //Update product
    $('button[name="update_btn"]').on("click", function() {
        if(is_imei[rowindex]) {
            var imeiNumbers = $("#editModal input[name=imei_numbers]").val();
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val(imeiNumbers);
        }

        var edit_discount = $('input[name="edit_discount"]').val();
        var edit_qty = $('input[name="edit_qty"]').val();
        var edit_unit_price = $('input[name="edit_unit_price"]').val();

        if (parseFloat(edit_discount) > parseFloat(edit_unit_price)) {
            alert('Invalid Discount Input!');
            return;
        }

        if(edit_qty < 1) {
            $('input[name="edit_qty"]').val(1);
            edit_qty = 1;
            alert("Quantity can't be less than 1");
        }

        // Validate lowest price before updating
        var product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-id').val();
        validatePriceBeforeUpdate(product_id, edit_unit_price, function(isValid) {
            if (!isValid) {
                return; // Stop execution if validation fails
            }
            proceedWithUpdate();
        });
    });

    function validatePriceBeforeUpdate(product_id, unit_price, callback) {
        $.ajax({
            url: '{{ route("sale.validate-single-price") }}',
            type: 'POST',
            data: {
                product_id: product_id,
                net_unit_price: unit_price,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    callback(true);
                } else {
                    callback(false);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errorMessage = xhr.responseJSON.message;
                    alert('Price Validation Error: ' + errorMessage);
                    callback(false);
                } else {
                    alert('Error validating price. Please try again.');
                    callback(false);
                }
            }
        });
    }

    function validatePriceRealTime(product_id, unit_price) {
        $.ajax({
            url: '{{ route("sale.validate-single-price") }}',
            type: 'POST',
            data: {
                product_id: product_id,
                net_unit_price: unit_price,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                // Remove any existing validation messages
                $('#editModal .price-validation-message').remove();
                if (response.success) {
                    // Add success indicator (optional)
                    $('input[name="edit_unit_price"]').removeClass('is-invalid').addClass('is-valid');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errorMessage = xhr.responseJSON.message;
                    // Remove any existing validation messages
                    $('#editModal .price-validation-message').remove();
                    // Add error styling and message
                    $('input[name="edit_unit_price"]').removeClass('is-valid').addClass('is-invalid');
                    $('input[name="edit_unit_price"]').after('<div class="price-validation-message text-danger small mt-1">' + errorMessage + '</div>');
                }
            }
        });
    }

    function proceedWithUpdate() {
        var edit_discount = $('input[name="edit_discount"]').val();
        var edit_qty = $('input[name="edit_qty"]').val();
        var edit_unit_price = $('input[name="edit_unit_price"]').val();

        var tax_rate_all = <?php echo json_encode($tax_rate_all) ?>;
        tax_rate[rowindex] = parseFloat(tax_rate_all[$('select[name="edit_tax_rate"]').val()]);
        tax_name[rowindex] = $('select[name="edit_tax_rate"] option:selected').text();
        if(product_type[pos] == 'standard'){
            var row_unit_operator = unit_operator[rowindex].slice(0, unit_operator[rowindex].indexOf(","));
            var row_unit_operation_value = unit_operation_value[rowindex].slice(0, unit_operation_value[rowindex].indexOf(","));
            if (row_unit_operator == '*') {
                product_price[rowindex] = $('input[name="edit_unit_price"]').val() / row_unit_operation_value;
            } else {
                product_price[rowindex] = $('input[name="edit_unit_price"]').val() * row_unit_operation_value;
            }
            var position = $('select[name="edit_unit"]').val();
            var temp_operator = temp_unit_operator[position];
            var temp_operation_value = temp_unit_operation_value[position];
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit').val(temp_unit_name[position]);
            temp_unit_name.splice(position, 1);
            temp_unit_operator.splice(position, 1);
            temp_unit_operation_value.splice(position, 1);

            temp_unit_name.unshift($('select[name="edit_unit"] option:selected').text());
            temp_unit_operator.unshift(temp_operator);
            temp_unit_operation_value.unshift(temp_operation_value);

            unit_name[rowindex] = temp_unit_name.toString() + ',';
            unit_operator[rowindex] = temp_unit_operator.toString() + ',';
            unit_operation_value[rowindex] = temp_unit_operation_value.toString() + ',';
        }
        else {
            product_price[rowindex] = $('input[name="edit_unit_price"]').val();
        }
        product_discount[rowindex] = $('input[name="edit_discount"]').val();
        
        // Update the hidden net_unit_price field with the exact price entered by user
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_price').val(edit_unit_price);
        
        checkDiscount(edit_qty, false);
        //checkQuantity(edit_qty, false);
    }

    $("#myTable").on("change", ".batch-no", function () {
        rowindex = $(this).closest('tr').index();
        var product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-id').val();
        var warehouse_id = $('select[name="warehouse_id"]').val();
        $.get('../../check-batch-availability/' + product_id + '/' + $(this).val() + '/' + warehouse_id, function(data) {
            if(data['message'] != 'ok') {
                alert(data['message']);
                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.batch-no').val('');
            }
            else {
                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-batch-id').val(data['product_batch_id']);
                code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-code').val();
                console.log(code);
                pos = product_code.indexOf(code);
                product_qty[pos] = data['qty'];
            }
        });
    });

    function isCashRegisterAvailable(warehouse_id) {
        $.ajax({
            url: '../../cash-register/check-availability/'+warehouse_id,
            type: "GET",
            success:function(data) {
                if(data == 'false') {
                    $('#cash-register-modal select[name=warehouse_id]').val(warehouse_id);
                    $('.selectpicker').selectpicker('refresh');
                    if(role_id <= 2){
                        $("#cash-register-modal .warehouse-section").removeClass('d-none');
                    }
                    else {
                        $("#cash-register-modal .warehouse-section").addClass('d-none');
                    }
                    $("#cash-register-modal").modal('show');
                }
            }
        });
    }

    function productSearch(data) {
        var product_info = data.split(" ");
        var code = product_info[0];
        var pre_qty = 0;
        var batch_id = '';
        var array_index = '';
        
        // Extract batch information from the selected data
        if(data.includes('|')) {
            var parts = data.split('|');
            
            if(parts.length >= 3) {
                batch_id = parts[parts.length - 2]; // batch_id
                array_index = parts[parts.length - 1]; // array index
            }
        }
        
        // Check if this exact product with same batch already exists in cart
        var found_existing = false;
        $(".product-code").each(function(i) {
            if ($(this).val() == code) {
                var existing_batch_id = $(this).closest('tr').find('.product-batch-id').val() || '';
                
                // Check if it's the same batch (or both are non-batched)
                if(batch_id == existing_batch_id) {
                    rowindex = i;
                    pre_qty = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val();
                    found_existing = true;
                    return false; // break the loop
                }
            }
        });
        
        // Create clean search string - only send product code and basic info
        var search_data = code + '?' + $('#customer_id').val() + '?' + (parseFloat(pre_qty) + 1) + '?' + array_index;
        var ajax_url = '{{ route("product_sale.search") }}';
        $.ajax({
            type: 'GET',
            url: ajax_url,
            data: {
                data: search_data
            },
            success: function(data) {
                var flag = 1;
                if (pre_qty > 0) {
                    var qty = data[15];
                    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(qty);
                    pos = product_code.indexOf(data[1]);
                    if(!data[11] && product_warehouse_price[pos]) {
                        product_price[rowindex] = parseFloat(product_warehouse_price[pos] * currency['exchange_rate']) + parseFloat(product_warehouse_price[pos] * currency['exchange_rate'] * customer_group_rate);
                    }
                    else{
                        product_price[rowindex] = parseFloat(data[2] * currency['exchange_rate']) + parseFloat(data[2] * currency['exchange_rate'] * customer_group_rate);
                    }
                    flag = 0;
                    checkQuantity(String(qty), true);
                    flag = 0;
                }
                $("input[name='product_code_name']").val('');
                if(flag){
                    var newRow = $("<tr>");
                    var cols = '';
                    var arrayIndex = data[17];
                    pos = arrayIndex ? parseInt(arrayIndex) : product_code.indexOf(data[1]);
                    temp_unit_name = (data[6]).split(',');
                    cols += '<td>' + data[0] + '<button type="button" class="edit-product btn btn-link" data-toggle="modal" data-target="#editModal"> <i class="dripicons-document-edit"></i></button></td>';
                    cols += '<td>' + data[1] + '</td>';
                    cols += '<td><input type="number" class="form-control qty" name="qty[]" value="'+data[15]+'" step="any" required/></td>';
                    if(data[12]) {
                        // Batched product: Auto-fill with actual batch number and disable input
                        var batchNo = (pos >= 0 && batch_no[pos]) ? batch_no[pos] : '';
                        var batchId = (pos >= 0 && product_batch_id[pos]) ? product_batch_id[pos] : '';
                        var expiredDate = (pos >= 0 && expired_date[pos]) ? expired_date[pos] : 'N/A';
                        
                        cols += '<td><input type="text" class="form-control batch-no" value="'+batchNo+'" disabled/> <input type="hidden" class="product-batch-id" name="product_batch_id[]" value="'+batchId+'"/> </td>';
                        cols += '<td class="expired-date">'+expiredDate+'</td>';
                    }
                    else {
                        // Non-batched product: Auto-fill with "N/A" and disable input
                        cols += '<td><input type="text" class="form-control batch-no" value="N/A" disabled/> <input type="hidden" class="product-batch-id" name="product_batch_id[]"/> </td>';
                        cols += '<td class="expired-date">N/A</td>';
                    }

                    cols += '<td class="net_unit_price"></td>';
                    cols += '<td class="discount">0.00</td>';
                    cols += '<td class="tax"></td>';
                    cols += '<td class="sub-total"></td>';
                    cols += '<td><button type="button" class="ibtnDel btn btn-md btn-danger">{{trans("file.delete")}}</button></td>';
                    cols += '<input type="hidden" class="product-code" name="product_code[]" value="' + data[1] + '"/>';
                    cols += '<input type="hidden" class="product-id" name="product_id[]" value="' + data[9] + '"/>';
                    cols += '<input type="hidden" class="sale-unit" name="sale_unit[]" value="' + temp_unit_name[0] + '"/>';
                    cols += '<input type="hidden" class="net_unit_price" name="net_unit_price[]" />';
                    cols += '<input type="hidden" class="discount-value" name="discount[]" />';
                    cols += '<input type="hidden" class="tax-rate" name="tax_rate[]" value="' + data[3] + '"/>';
                    cols += '<input type="hidden" class="tax-value" name="tax[]" />';
                    cols += '<input type="hidden" class="subtotal-value" name="subtotal[]" />';
                    cols += '<input type="hidden" class="imei-number" name="imei_number[]" />';
                    
                    // Add batch-related hidden inputs
                    if(data[12]) {
                        // Batched product: Store actual batch ID and is_batch flag
                        var batchId = (pos >= 0 && product_batch_id[pos]) ? product_batch_id[pos] : '';
                        cols += '<input type="hidden" class="batch-id" name="batch_id[]" value="' + batchId + '"/>';
                        cols += '<input type="hidden" class="is-batch" name="is_batch[]" value="1"/>';
                    } else {
                        // Non-batched product: Store empty batch ID and is_batch = 0
                        cols += '<input type="hidden" class="batch-id" name="batch_id[]" value=""/>';
                        cols += '<input type="hidden" class="is-batch" name="is_batch[]" value="0"/>';
                    }

                    newRow.append(cols);
                    $("table.order-list tbody").prepend(newRow);
                    rowindex = newRow.index();

                    if(!data[11] && product_warehouse_price[pos]) {
                        product_price.splice(rowindex, 0, parseFloat(product_warehouse_price[pos] * currency['exchange_rate']) + parseFloat(product_warehouse_price[pos] * currency['exchange_rate'] * customer_group_rate));
                    }
                    else {
                        product_price.splice(rowindex, 0, parseFloat(data[2] * currency['exchange_rate']) + parseFloat(data[2] * currency['exchange_rate'] * customer_group_rate));
                    }
                    product_discount.splice(rowindex, 0, '0.00');
                    tax_rate.splice(rowindex, 0, parseFloat(data[3]));
                    tax_name.splice(rowindex, 0, data[4]);
                    tax_method.splice(rowindex, 0, data[5]);
                    unit_name.splice(rowindex, 0, data[6]);
                    unit_operator.splice(rowindex, 0, data[7]);
                    unit_operation_value.splice(rowindex, 0, data[8]);
                    is_imei.splice(rowindex, 0, data[13]);
                    checkQuantity(data[15], true);
                    if(data[13]) {
                        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.edit-product').click();
                    }
                }
            },
            error: function(xhr, status, error) {
                if(xhr.status === 404) {
                    alert('Product not found. Please check the product code and try again.');
                } else {
                    alert('Error searching for product: ' + error);
                }
                $("input[name='product_code_name']").val('');
            }
        });
    }

    function edit()
    {
        $(".imei-section").remove();
        if(is_imei[rowindex]) {
            var imeiNumbers = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val();

            htmlText = '<div class="col-md-12 form-group imei-section"><label>IMEI or Serial Numbers</label><input type="text" name="imei_numbers" value="'+imeiNumbers+'" class="form-control imei_number" placeholder="Type imei or serial numbers and separate them by comma. Example:1001,2001" step="any"></div>';
            $("#editModal .modal-element").append(htmlText);
        }

        var row_product_name = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(1)').text();
        var row_product_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text();
        $('#modal_header').text(row_product_name + '(' + row_product_code + ')');

        var qty = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val();
        $('input[name="edit_qty"]').val(qty);

        $('input[name="edit_discount"]').val(parseFloat(product_discount[rowindex]).toFixed(2));

        var tax_name_all = <?php echo json_encode($tax_name_all) ?>;
        pos = tax_name_all.indexOf(tax_name[rowindex]);
        $('select[name="edit_tax_rate"]').val(pos);

        pos = product_code.indexOf(row_product_code);
        if(product_type[pos] == 'standard'){
            unitConversion();
            temp_unit_name = (unit_name[rowindex]).split(',');
            temp_unit_name.pop();
            temp_unit_operator = (unit_operator[rowindex]).split(',');
            temp_unit_operator.pop();
            temp_unit_operation_value = (unit_operation_value[rowindex]).split(',');
            temp_unit_operation_value.pop();
            $('select[name="edit_unit"]').empty();
            $.each(temp_unit_name, function(key, value) {
                $('select[name="edit_unit"]').append('<option value="' + key + '">' + value + '</option>');
            });
            $("#edit_unit").show();
        }
        else{
            row_product_price = product_price[rowindex];
            $("#edit_unit").hide();
        }
        $('input[name="edit_unit_price"]').val(row_product_price.toFixed(2));
        $('.selectpicker').selectpicker('refresh');
    }

    function checkDiscount(qty, flag) {
        var customer_id = $('#customer_id').val();
        var product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();
        if(flag) {
            $.ajax({
                type: 'GET',
                async: false,
                url: '../check-discount?qty='+qty+'&customer_id='+customer_id+'&product_id='+product_id,
                success: function(data) {
                    // console.log(data);
                    pos = product_code.indexOf($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product-code').val());
                    
                    // Check if this is an existing product (has net_unit_price)
                    // Target specifically the hidden input element, not the display td
                    var existing_net_unit_price = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('input.net_unit_price').val();
                    var is_existing_product = existing_net_unit_price && existing_net_unit_price !== '';
                    
                    if (is_existing_product) {
                        // For existing products, preserve the original sale price
                        product_price[rowindex] = parseFloat(existing_net_unit_price);
                    } else {
                        // For new products, apply currency conversion
                        product_price[rowindex] = parseFloat(data[0] * currency['exchange_rate']) + parseFloat(data[0] * currency['exchange_rate'] * customer_group_rate);
                    }
                }
            });
        }
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(qty);
        checkQuantity(String(qty), flag);
        localStorage.setItem("tbody-id", $("table.order-list tbody").html());
    }

    function checkQuantity(sale_qty, flag) {
        var row_product_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text();
        var row_batch_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.batch-id').val();
        
        // For batched products, find the specific batch position
        // For non-batched products, use the first occurrence
        pos = product_code.indexOf(row_product_code);
        if(row_batch_id && row_batch_id !== '') {
            // Find the correct position for this specific batch
            for(var i = 0; i < product_code.length; i++) {
                if(product_code[i] === row_product_code && product_batch_id[i] == row_batch_id) {
                    pos = i;
                    break;
                }
            }
        }
        if(product_type[pos] == 'standard'){
            var operator = unit_operator[rowindex].split(',');
            var operation_value = unit_operation_value[rowindex].split(',');
            if(operator[0] == '*')
                total_qty = sale_qty * operation_value[0];
            else if(operator[0] == '/')
                total_qty = sale_qty / operation_value[0];
            if (total_qty > parseFloat(product_qty[pos])) {
                alert('Quantity exceeds stock quantity!');
                if (flag) {
                    sale_qty = sale_qty.substring(0, sale_qty.length - 1);
                    $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(sale_qty);
                }
                else {
                    edit();
                    return;
                }
            }
        }
        else if(product_type[pos] == 'combo'){
            child_id = product_list[pos].split(',');
            child_qty = qty_list[pos].split(',');
            $(child_id).each(function(index) {
                var position = product_id.indexOf(parseInt(child_id[index]));
                if( parseFloat(sale_qty * child_qty[index]) > product_qty[position] ) {
                    alert('Quantity exceeds stock quantity!');
                    if (flag) {
                        sale_qty = sale_qty.substring(0, sale_qty.length - 1);
                        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(sale_qty);
                    }
                    else {
                        edit();
                        flag = true;
                        return false;
                    }
                }
            });
        }

        if(!flag){
            $('#editModal').modal('hide');
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(sale_qty);
        }

        calculateRowProductData(sale_qty);
    }

    function calculateRowProductData(quantity) {
        if(product_type[pos] == 'standard')
            unitConversion();
        else
            row_product_price = product_price[rowindex];

        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.discount').text((product_discount[rowindex] * quantity).toFixed(2));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.discount-value').val((product_discount[rowindex] * quantity).toFixed(2));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-rate').val(tax_rate[rowindex].toFixed(2));

        if (tax_method[rowindex] == 1) {
            var net_unit_price = row_product_price - product_discount[rowindex];
            var tax = net_unit_price * quantity * (tax_rate[rowindex] / 100);
            var sub_total = (net_unit_price * quantity) + tax;

            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_price').text(net_unit_price.toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_price').val(net_unit_price.toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax').text(tax.toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-value').val(tax.toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sub-total').text(sub_total.toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.subtotal-value').val(sub_total.toFixed(2));
        } else {
            var sub_total_unit = row_product_price - product_discount[rowindex];
            var net_unit_price = (100 / (100 + tax_rate[rowindex])) * sub_total_unit;
            var tax = (sub_total_unit - net_unit_price) * quantity;
            var sub_total = sub_total_unit * quantity;

            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_price').text(net_unit_price.toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_price').val(net_unit_price.toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax').text(tax.toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-value').val(tax.toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sub-total').text(sub_total.toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.subtotal-value').val(sub_total.toFixed(2));
        }

        calculateTotal();
    }

    function unitConversion() {
        var row_unit_operator = unit_operator[rowindex].slice(0, unit_operator[rowindex].indexOf(","));
        var row_unit_operation_value = unit_operation_value[rowindex].slice(0, unit_operation_value[rowindex].indexOf(","));

        if (row_unit_operator == '*') {
            row_product_price = product_price[rowindex] * row_unit_operation_value;
        } else {
            row_product_price = product_price[rowindex] / row_unit_operation_value;
        }
    }

    function calculateTotal() {
        //Sum of quantity
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

        //Sum of discount
        var total_discount = 0;
        $(".discount").each(function() {
            total_discount += parseFloat($(this).text());
        });
        $("#total-discount").text(total_discount.toFixed(2));
        $('input[name="total_discount"]').val(total_discount.toFixed(2));

        //Sum of tax
        var total_tax = 0;
        $(".tax").each(function() {
            total_tax += parseFloat($(this).text());
        });
        $("#total-tax").text(total_tax.toFixed(2));
        $('input[name="total_tax"]').val(total_tax.toFixed(2));

        //Sum of subtotal
        var total = 0;
        $(".sub-total").each(function() {
            total += parseFloat($(this).text());
        });
        $("#total").text(total.toFixed(2));
        $('input[name="total_price"]').val(total.toFixed(2));

        calculateGrandTotal();
    }

    function calculateGrandTotal() {

        var item = $('table.order-list tbody tr:last').index();

        var total_qty = parseFloat($('#total-qty').text());
        var subtotal = parseFloat($('#total').text());
        var order_tax = parseFloat($('select[name="order_tax_rate"]').val());
        var shipping_cost = parseFloat($('input[name="shipping_cost"]').val());
        var order_discount_type = $('select[name="order_discount_type"]').val();
        var order_discount_value = parseFloat($('input[name="order_discount_value"]').val());
    
        if (!order_discount_value)
            order_discount_value = 0.00;

        if(order_discount_type == 'Flat')
            var order_discount = parseFloat(order_discount_value);
        else
            var order_discount = parseFloat(subtotal * (order_discount_value / 100));

        $('input[name="order_discount"]').val(order_discount);

        if (!shipping_cost)
            shipping_cost = 0.00;

        item = ++item + '(' + total_qty + ')';
        order_tax = (subtotal - order_discount) * (order_tax / 100);
        var grand_total = (subtotal + order_tax + shipping_cost) - order_discount;
        $('input[name="grand_total"]').val(grand_total.toFixed(2));
        if($('input[name="coupon_active"]').val()) {
            couponDiscount();
            var coupon_discount = parseFloat($('input[name="coupon_discount"]').val());
            if (!coupon_discount)
                coupon_discount = 0.00;
            grand_total -= coupon_discount;
        }

        $('#item').text(item);
        $('input[name="item"]').val($('table.order-list tbody tr:last').index() + 1);
        $('#subtotal').text(subtotal.toFixed(2));
        $('#order_tax').text(order_tax.toFixed(2));
        $('input[name="order_tax"]').val(order_tax.toFixed(2));
        $('#order_discount').text(order_discount.toFixed(2));
        $('#shipping_cost').text(shipping_cost.toFixed(2));
        $('#grand_total').text(grand_total.toFixed(2));
        $('input[name="grand_total"]').val(grand_total.toFixed(2));
    }

    function couponDiscount() {
        var rownumber = $('table.order-list tbody tr:last').index();
        if (rownumber < 0) {
            alert("Please insert product to order table!")
        }
        else{
            if($('input[name="coupon_type"]').val() == 'fixed'){
                if( $('input[name="grand_total"]').val() >= $('input[name="coupon_minimum_amount"]').val() ) {
                    $('input[name="grand_total"]').val( $('input[name="grand_total"]').val() - $('input[name="coupon_amount"]').val() );
                }
                else
                    alert('Grand Total is not sufficient for discount! Required '+ currency['code'] + ' ' +$('input[name="coupon_minimum_amount"]').val());
            }
            else{
                var grand_total = $('input[name="grand_total"]').val();
                var coupon_discount = grand_total * (parseFloat($('input[name="coupon_amount"]').val()) / 100);
                grand_total = grand_total - coupon_discount;
                $('input[name="grand_total"]').val(grand_total);
                $('input[name="coupon_discount"]').val(coupon_discount);
                $('#coupon-text').text(parseFloat(coupon_discount).toFixed(2));
            }
        }
    }

    $('select[name="order_discount_type"]').on("change", function() {
        calculateGrandTotal();
    });

    $('input[name="order_discount_value"]').on("input", function() {
        calculateGrandTotal();
    });

    $('input[name="shipping_cost"]').on("input", function() {
        calculateGrandTotal();
    });

    $('select[name="order_tax_rate"]').on("change", function() {
        calculateGrandTotal();
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

    $('#payment-form').on('submit',function(e){
        var rownumber = $('table.order-list tbody tr:last').index();
        if (rownumber < 0) {
            alert("Please insert product to order table!")
            e.preventDefault();
        }
        else {
            
            $("#submit-button").prop('disabled', true);
            // Keep batch inputs disabled - they should always be auto-filled and not editable
        }
    });
    </script>
<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
@endpush

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Warehouse;
use App\Product_Warehouse;
use App\Product;
use App\Adjustment;
use App\ProductAdjustment;
use DB;
use App\StockCount;
use App\ProductVariant;
use Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdjustmentController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if( $role->hasPermissionTo('adjustment') ) {
            /*if(Auth::user()->role_id > 2 && config('staff_access') == 'own')
                $lims_adjustment_all = Adjustment::orderBy('id', 'desc')->where('user_id', Auth::id())->get();
            else*/
                $lims_adjustment_all = Adjustment::orderBy('id', 'desc')->get();
            return view('backend.adjustment.index', compact('lims_adjustment_all'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function getProduct($id)
    {
        // Load ALL active products (and variants) with LEFT JOIN to product_warehouse for the selected warehouse.
        // This returns items even if there is no warehouse record; we'll indicate that via has_record flag.

        $nonVariant = DB::table('products')
            ->leftJoin('product_warehouse', function($join) use ($id) {
                $join->on('products.id', '=', 'product_warehouse.product_id')
                     ->where('product_warehouse.warehouse_id', '=', $id)
                     ->whereNull('product_warehouse.variant_id');
            })
            ->whereNull('products.is_variant')
            ->where('products.is_active', true)
            ->where(function($q){
                // Include:
                // - Non-batch products (is_batch = 0 OR NULL)
                // - Batched products when NO pw record exists (so product still selectable)
                // - Batched products when pw exists but product_batch_id IS NULL (unbatched stock present in warehouse)
                $q->whereNull('products.is_batch')
                  ->orWhere('products.is_batch', 0)
                  ->orWhereNull('product_warehouse.id')
                  ->orWhere(function($q2){
                      $q2->where('products.is_batch', 1)
                         ->whereNotNull('product_warehouse.id')
                         ->whereNull('product_warehouse.product_batch_id');
                  });
            })
            ->select(
                'products.name as name',
                'products.code as code',
                DB::raw('COALESCE(product_warehouse.qty, 0) as qty'),
                DB::raw('CASE WHEN product_warehouse.id IS NULL THEN 0 ELSE 1 END as has_record'),
                DB::raw('NULL as batch_no'),
                DB::raw('NULL as product_batch_id'),
                DB::raw('NULL as expired_date'),
                DB::raw('products.is_batch as is_batch')
            );

        $variant = DB::table('products')
            ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('product_warehouse', function($join) use ($id) {
                $join->on('product_variants.product_id', '=', 'product_warehouse.product_id')
                     ->on('product_variants.variant_id', '=', 'product_warehouse.variant_id')
                     ->where('product_warehouse.warehouse_id', '=', $id);
            })
            ->whereNotNull('products.is_variant')
            ->where('products.is_active', true)
            ->select(
                'products.name as name',
                'product_variants.item_code as code',
                DB::raw('COALESCE(product_warehouse.qty, 0) as qty'),
                DB::raw('CASE WHEN product_warehouse.id IS NULL THEN 0 ELSE 1 END as has_record'),
                DB::raw('NULL as batch_no'),
                DB::raw('NULL as product_batch_id'),
                DB::raw('NULL as expired_date'),
                DB::raw('0 as is_batch')
            );

        // Batched products: one row per batch in selected warehouse
        $batched = DB::table('products')
            ->join('product_warehouse', function($join) use ($id) {
                $join->on('products.id', '=', 'product_warehouse.product_id')
                     ->where('product_warehouse.warehouse_id', '=', $id)
                     ->whereNull('product_warehouse.variant_id')
                     ->whereNotNull('product_warehouse.product_batch_id');
            })
            ->join('product_batches', 'product_batches.id', '=', 'product_warehouse.product_batch_id')
            ->where('products.is_active', true)
            ->where('products.is_batch', 1)
            ->select(
                'products.name as name',
                'products.code as code',
                DB::raw('COALESCE(product_warehouse.qty, 0) as qty'),
                DB::raw('1 as has_record'),
                'product_batches.batch_no as batch_no',
                'product_batches.id as product_batch_id',
                'product_batches.expired_date as expired_date',
                DB::raw('1 as is_batch')
            );

        $rows = $nonVariant->unionAll($variant)->unionAll($batched)->get();

        $product_code = [];
        $product_name = [];
        $product_qty = [];
        $product_has_record = [];
        $batch_no = [];
        $product_batch_id = [];
        $expired_date = [];
        $is_batch = [];
        $product_data = [];

        foreach ($rows as $row) {
            $product_code[] = $row->code;
            $product_name[] = $row->name;
            $product_qty[] = (float)$row->qty;
            $product_has_record[] = (int)$row->has_record;
            $batch_no[] = $row->batch_no;
            $product_batch_id[] = $row->product_batch_id;
            $expired_date[] = $row->expired_date ? date('Y-m-d', strtotime($row->expired_date)) : null;
            $is_batch[] = (int)$row->is_batch;
        }

        $product_data[] = $product_code;
        $product_data[] = $product_name;
        $product_data[] = $product_qty;
        $product_data[] = $product_has_record;
        $product_data[] = $batch_no;
        $product_data[] = $product_batch_id;
        $product_data[] = $expired_date;
        $product_data[] = $is_batch;
        return $product_data;
    }

    public function limsProductSearch(Request $request)
    {
        $product_code = explode("(", $request['data']);
        $product_code[0] = rtrim($product_code[0], " ");
        $lims_product_data = Product::where([
            ['code', $product_code[0]],
            ['is_active', true]
        ])->first();
        if(!$lims_product_data) {
            $lims_product_data = Product::join('product_variants', 'products.id', 'product_variants.product_id')
                ->select('products.id', 'products.name', 'products.is_variant', 'product_variants.id as product_variant_id', 'product_variants.item_code')
                ->where([
                    ['product_variants.item_code', $product_code[0]],
                    ['products.is_active', true]
                ])->first();
        }

        $product[] = $lims_product_data->name;
        $product_variant_id = null;
        if($lims_product_data->is_variant) {
            $product[] = $lims_product_data->item_code;
            $product_variant_id = $lims_product_data->product_variant_id;
        }
        else
            $product[] = $lims_product_data->code;

        $product[] = $lims_product_data->id;
        $product[] = $product_variant_id;
        return $product;
    }

    public function create()
    {
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        return view('backend.adjustment.create', compact('lims_warehouse_list'));
    }

    public function store(Request $request)
    {
        $data = $request->except('document');
        //return $data;
        if( isset($data['stock_count_id']) ){
            $lims_stock_count_data = StockCount::find($data['stock_count_id']);
            $lims_stock_count_data->is_adjusted = true;
            $lims_stock_count_data->save();
        }
        $data['reference_no'] = 'adr-' . date("Ymd") . '-'. date("his");
        $document = $request->document;
        if ($document) {
            $documentName = $document->getClientOriginalName();
            $document->move('public/documents/adjustment', $documentName);
            $data['document'] = $documentName;
        }
        $lims_adjustment_data = Adjustment::create($data);

        $product_id = $data['product_id'];
        $product_code = $data['product_code'];
        $qty = $data['qty'];
        $product_batch_id = $data['product_batch_id'] ?? [];
        $batch_no = $data['batch_no'] ?? [];
        $expire_date = $data['expire_date'] ?? [];
        $action = $data['action'];

        foreach ($product_id as $key => $pro_id) {
            $lims_product_data = Product::find($pro_id);
            if($lims_product_data->is_variant) {
                $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($pro_id, $product_code[$key])->first();
                $lims_product_warehouse_data = Product_Warehouse::where([
                    ['product_id', $pro_id],
                    ['variant_id', $lims_product_variant_data->variant_id ],
                    ['warehouse_id', $data['warehouse_id'] ],
                ])->first();
                if(!$lims_product_warehouse_data) {
                    if($action[$key] == '+') {
                        $lims_product_warehouse_data = Product_Warehouse::create([
                            'product_id' => $pro_id,
                            'variant_id' => $lims_product_variant_data->variant_id,
                            'warehouse_id' => $data['warehouse_id'],
                            'qty' => 0,
                        ]);
                    } else {
                        return back()->with('not_permitted', 'Cannot subtract: no stock record for this product in the selected warehouse.');
                    }
                }

                if($action[$key] == '-'){
                    $lims_product_variant_data->qty -= $qty[$key];
                }
                elseif($action[$key] == '+'){
                    $lims_product_variant_data->qty += $qty[$key];
                }
                $lims_product_variant_data->save();
                $variant_id = $lims_product_variant_data->variant_id;
            }
            else {
                // For batched products, handle different scenarios
                if($lims_product_data->is_batch) {
                    // Scenario 1: Existing batch selected (product_batch_id provided)
                    if(isset($product_batch_id[$key]) && $product_batch_id[$key]) {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $pro_id],
                            ['warehouse_id', $data['warehouse_id']],
                            ['product_batch_id', $product_batch_id[$key]],
                        ])->first();
                    }
                    // Scenario 2: New batch data provided (batch_no and expire_date)
                    elseif(!empty($batch_no[$key]) && !empty($expire_date[$key])) {
                        // First, try to find unbatched stock (product_batch_id IS NULL)
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $pro_id],
                            ['warehouse_id', $data['warehouse_id']],
                            ['variant_id', null],
                            ['product_batch_id', null]
                        ])->first();
                        
                        // If no unbatched stock found, look for any warehouse record
                        if(!$lims_product_warehouse_data) {
                            $lims_product_warehouse_data = Product_Warehouse::where([
                                ['product_id', $pro_id],
                                ['warehouse_id', $data['warehouse_id']],
                                ['variant_id', null]
                            ])->first();
                        }
                    }
                    // Scenario 3: No batch data provided, find any warehouse record
                    else {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $pro_id],
                            ['warehouse_id', $data['warehouse_id']],
                            ['variant_id', null]
                        ])->first();
                    }
                } else {
                    // Non-batched product
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $pro_id],
                        ['warehouse_id', $data['warehouse_id']],
                        ['variant_id', null],
                        ['product_batch_id', null]
                    ])->first();
                }
                
                if(!$lims_product_warehouse_data) {
                    if($action[$key] == '+') {
                        $lims_product_warehouse_data = Product_Warehouse::create([
                            'product_id' => $pro_id,
                            'warehouse_id' => $data['warehouse_id'],
                            'product_batch_id' => ($lims_product_data->is_batch && !empty($product_batch_id[$key])) ? (int)$product_batch_id[$key] : null,
                            'qty' => 0,
                        ]);
                    } else {
                        return back()->with('not_permitted', 'Cannot subtract: no stock record for this product in the selected warehouse.');
                    }
                }
                $variant_id = null;
            }

            if($action[$key] == '-') {
                $lims_product_data->qty -= $qty[$key];
                $lims_product_warehouse_data->qty -= $qty[$key];
            }
            elseif($action[$key] == '+') {
                $lims_product_data->qty += $qty[$key];
                $lims_product_warehouse_data->qty += $qty[$key];
            }
            $lims_product_data->save();
            $lims_product_warehouse_data->save();

            // Handle batch creation for new batch data
            $final_batch_id = null;
            if($lims_product_data->is_batch && !empty($batch_no[$key]) && !empty($expire_date[$key])) {
                // Check if this batch already exists
                $existing_batch = \App\ProductBatch::where([
                    ['product_id', $pro_id],
                    ['batch_no', $batch_no[$key]]
                ])->first();
                
                if($existing_batch) {
                    $final_batch_id = $existing_batch->id;
                    // Update existing batch quantity
                    if($action[$key] == '+') {
                        $existing_batch->qty += $qty[$key];
                    } elseif($action[$key] == '-') {
                        $existing_batch->qty -= $qty[$key];
                    }
                    $existing_batch->save();
                } else {
                    // Create new batch - set qty to actual warehouse qty after adjustment
                    $warehouse_qty_after_adjustment = $lims_product_warehouse_data->qty;
                    $new_batch = \App\ProductBatch::create([
                        'product_id' => $pro_id,
                        'batch_no' => $batch_no[$key],
                        'expired_date' => $expire_date[$key],
                        'qty' => $warehouse_qty_after_adjustment
                    ]);
                    $final_batch_id = $new_batch->id;
                }
                
                // Update product_warehouse to use the correct batch_id
                if($lims_product_warehouse_data->product_batch_id != $final_batch_id) {
                    $lims_product_warehouse_data->product_batch_id = $final_batch_id;
                    $lims_product_warehouse_data->save();
                }
            } elseif($lims_product_data->is_batch && isset($product_batch_id[$key]) && $product_batch_id[$key]) {
                // Use existing batch_id from selection
                $final_batch_id = (int)$product_batch_id[$key];
            }

            $product_adjustment['product_id'] = $pro_id;
            $product_adjustment['variant_id'] = $variant_id;
            $product_adjustment['adjustment_id'] = $lims_adjustment_data->id;
            $product_adjustment['qty'] = $qty[$key];
            $product_adjustment['action'] = $action[$key];
            $product_adjustment['product_batch_id'] = $final_batch_id;
            ProductAdjustment::create($product_adjustment);
        }
        return redirect('qty_adjustment')->with('message', 'Data inserted successfully');
    }

    public function edit($id)
    {
        $lims_adjustment_data = Adjustment::find($id);
        $lims_product_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();
        
        // Get batch information for each product adjustment
        foreach($lims_product_adjustment_data as $index => $adjustment) {
            // Now we can use the stored product_batch_id to get the correct batch information
            if($adjustment->product_batch_id) {
                $batch_data = \App\ProductBatch::find($adjustment->product_batch_id);
                if($batch_data) {
                    $adjustment->batch_no = $batch_data->batch_no;
                    $adjustment->expired_date = $batch_data->expired_date;
            } else {
                    $adjustment->batch_no = null;
                    $adjustment->expired_date = null;
                }
            } else {
                $adjustment->batch_no = null;
                $adjustment->expired_date = null;
            }
        }
        
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        return view('backend.adjustment.edit', compact('lims_adjustment_data', 'lims_warehouse_list', 'lims_product_adjustment_data'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->except('document');
        
        $document = $request->document;
        if ($document) {
            $documentName = $document->getClientOriginalName();
            $document->move('public/documents/adjustment', $documentName);
            $data['document'] = $documentName;
        }

        $lims_adjustment_data = Adjustment::find($id);
        $lims_product_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();
        $product_id = $data['product_id'];
        $product_variant_id = $data['product_variant_id'] ?? [];
        $product_code = $data['product_code'];
        $qty = $data['qty'];
        $product_batch_id = $data['product_batch_id'] ?? [];
        $batch_no = $data['batch_no'] ?? [];
        $expire_date = $data['expire_date'] ?? [];
        $action = $data['action'];
        
        // Create a map of existing adjustments for comparison
        $existing_adjustments = [];
        foreach ($lims_product_adjustment_data as $existing) {
            $key = $existing->product_id . '_' . ($existing->variant_id ?? 'null');
            $existing_adjustments[$key] = [
                'id' => $existing->id,
                'product_id' => $existing->product_id,
                'variant_id' => $existing->variant_id,
                'qty' => $existing->qty,
                'action' => $existing->action,
                'product_batch_id' => $existing->product_batch_id ?? null
            ];
        }
        
        // Process each submitted product
        foreach ($product_id as $key => $pro_id) {
            
            $variant_id = $product_variant_id[$key] ?? null;
            $key_for_existing = $pro_id . '_' . ($variant_id ?? 'null');
            
            // Check if this is an existing adjustment or a new one
            if (isset($existing_adjustments[$key_for_existing])) {
                $existing = $existing_adjustments[$key_for_existing];
                
                // Calculate the difference
                $old_qty = $existing['qty'];
                $old_action = $existing['action'];
                $new_qty = $qty[$key];
                $new_action = $action[$key];
                
                
                // Only apply changes if there's a difference
                if ($old_qty != $new_qty || $old_action != $new_action) {
                    $lims_product_data = Product::find($pro_id);
                    
                    // Calculate the net effect of the change
                    $net_change = 0;
                    if ($old_action == '+') {
                        $net_change -= $old_qty; // Reverse old addition
                    } else {
                        $net_change += $old_qty; // Reverse old subtraction
                    }
                    
                    if ($new_action == '+') {
                        $net_change += $new_qty; // Apply new addition
                    } else {
                        $net_change -= $new_qty; // Apply new subtraction
                    }
                    
                    
                    if ($net_change != 0) {
                        // Apply the net change to product quantities
                        $lims_product_data->qty += $net_change;
                        $lims_product_data->save();
                        
                        // Handle variant products
                        if ($lims_product_data->is_variant && $variant_id) {
                            $lims_product_variant_data = ProductVariant::where([
                                ['product_id', $pro_id],
                                ['variant_id', $variant_id]
                            ])->first();
                            if ($lims_product_variant_data) {
                                $lims_product_variant_data->qty += $net_change;
                                $lims_product_variant_data->save();
                            }
                        }
                        
                        // Handle warehouse quantities
                        $lims_product_warehouse_data = null;
                        if ($lims_product_data->is_variant && $variant_id) {
                            $lims_product_warehouse_data = Product_Warehouse::where([
                                ['product_id', $pro_id],
                                ['variant_id', $variant_id],
                                ['warehouse_id', $data['warehouse_id']]
                            ])->first();
                        } else {
                            // Handle batch-specific warehouse data
                            if ($lims_product_data->is_batch && isset($product_batch_id[$key]) && $product_batch_id[$key]) {
                                $lims_product_warehouse_data = Product_Warehouse::where([
                                    ['product_id', $pro_id],
                                    ['warehouse_id', $data['warehouse_id']],
                                    ['product_batch_id', $product_batch_id[$key]]
                                ])->first();
                            } else {
                                $lims_product_warehouse_data = Product_Warehouse::where([
                                    ['product_id', $pro_id],
                                    ['warehouse_id', $data['warehouse_id']],
                                    ['variant_id', null],
                                    ['product_batch_id', null]
                                ])->first();
                            }
                        }
                        
                        if ($lims_product_warehouse_data) {
                            $lims_product_warehouse_data->qty += $net_change;
                            $lims_product_warehouse_data->save();
                        }
                        
                        // Handle batch quantities
                        if ($lims_product_data->is_batch && isset($product_batch_id[$key]) && $product_batch_id[$key]) {
                            $existing_batch = \App\ProductBatch::find($product_batch_id[$key]);
                            if ($existing_batch) {
                                $existing_batch->qty += $net_change;
                                $existing_batch->save();
                            }
                        }
                    }
                    
                    // Determine the final batch_id for this update
                    $final_batch_id = null;
                    if($lims_product_data->is_batch && isset($product_batch_id[$key]) && $product_batch_id[$key]) {
                        $final_batch_id = (int)$product_batch_id[$key];
                    } elseif($lims_product_data->is_batch && !empty($batch_no[$key]) && !empty($expire_date[$key])) {
                        // Check if this batch already exists
                        $existing_batch = \App\ProductBatch::where([
                            ['product_id', $pro_id],
                            ['batch_no', $batch_no[$key]]
                        ])->first();
                        
                        if($existing_batch) {
                            $final_batch_id = $existing_batch->id;
                        } else {
                            // Create new batch
                            $new_batch = \App\ProductBatch::create([
                                'product_id' => $pro_id,
                                'batch_no' => $batch_no[$key],
                                'expired_date' => $expire_date[$key],
                                'qty' => 0
                            ]);
                            $final_batch_id = $new_batch->id;
                        }
                    }
                    
                    // Update the adjustment record
                    ProductAdjustment::where('id', $existing['id'])->update([
                        'qty' => $new_qty,
                        'action' => $new_action,
                        'product_batch_id' => $final_batch_id
                    ]);
                }
            } else {
                // This is a new product - apply the adjustment normally
                
                $lims_product_data = Product::find($pro_id);
                if($lims_product_data->is_variant) {
                    $lims_product_variant_data = ProductVariant::select('id', 'variant_id', 'qty')->FindExactProductWithCode($pro_id, $product_code[$key])->first();
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $pro_id],
                        ['variant_id', $lims_product_variant_data->variant_id ],
                        ['warehouse_id', $data['warehouse_id'] ],
                    ])->first();
                    if(!$lims_product_warehouse_data) {
                        if($action[$key] == '+') {
                            $lims_product_warehouse_data = Product_Warehouse::create([
                                'product_id' => $pro_id,
                                'variant_id' => $lims_product_variant_data->variant_id,
                                'warehouse_id' => $data['warehouse_id'],
                                'qty' => 0,
                            ]);
                        } else {
                            return back()->with('not_permitted', 'Cannot subtract: no stock record for this product in the selected warehouse.');
                        }
                    }

                    if($action[$key] == '-'){
                        $lims_product_variant_data->qty -= $qty[$key];
                    }
                    elseif($action[$key] == '+'){
                        $lims_product_variant_data->qty += $qty[$key];
                    }
                    $lims_product_variant_data->save();
                    $variant_id = $lims_product_variant_data->variant_id;
                }
                else {
                    // For batched products, handle different scenarios
                    if($lims_product_data->is_batch) {
                        // Scenario 1: Existing batch selected (product_batch_id provided)
                        if(isset($product_batch_id[$key]) && $product_batch_id[$key]) {
                            $lims_product_warehouse_data = Product_Warehouse::where([
                                ['product_id', $pro_id],
                                ['warehouse_id', $data['warehouse_id']],
                                ['product_batch_id', $product_batch_id[$key]],
                            ])->first();
                        }
                        // Scenario 2: New batch data provided (batch_no and expire_date)
                        elseif(!empty($batch_no[$key]) && !empty($expire_date[$key])) {
                            // First, try to find unbatched stock (product_batch_id IS NULL)
                            $lims_product_warehouse_data = Product_Warehouse::where([
                                ['product_id', $pro_id],
                                ['warehouse_id', $data['warehouse_id']],
                                ['variant_id', null],
                                ['product_batch_id', null]
                            ])->first();
                            
                            // If no unbatched stock found, look for any warehouse record
                            if(!$lims_product_warehouse_data) {
                                $lims_product_warehouse_data = Product_Warehouse::where([
                                    ['product_id', $pro_id],
                                    ['warehouse_id', $data['warehouse_id']],
                                    ['variant_id', null]
                                ])->first();
                            }
                        }
                        // Scenario 3: No batch data provided, find any warehouse record
                        else {
                            $lims_product_warehouse_data = Product_Warehouse::where([
                                ['product_id', $pro_id],
                                ['warehouse_id', $data['warehouse_id']],
                                ['variant_id', null]
                            ])->first();
                        }
                    } else {
                        // Non-batched product
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $pro_id],
                            ['warehouse_id', $data['warehouse_id']],
                            ['variant_id', null],
                            ['product_batch_id', null]
                        ])->first();
                    }
                    
                    if(!$lims_product_warehouse_data) {
                        if($action[$key] == '+') {
                            $lims_product_warehouse_data = Product_Warehouse::create([
                                'product_id' => $pro_id,
                                'warehouse_id' => $data['warehouse_id'],
                                'product_batch_id' => ($lims_product_data->is_batch && !empty($product_batch_id[$key])) ? (int)$product_batch_id[$key] : null,
                                'qty' => 0,
                            ]);
                        } else {
                            return back()->with('not_permitted', 'Cannot subtract: no stock record for this product in the selected warehouse.');
                        }
                    }
                    $variant_id = null;
                }

                if($action[$key] == '-') {
                    $lims_product_data->qty -= $qty[$key];
                    $lims_product_warehouse_data->qty -= $qty[$key];
                }
                elseif($action[$key] == '+') {
                    $lims_product_data->qty += $qty[$key];
                    $lims_product_warehouse_data->qty += $qty[$key];
                }
                $lims_product_data->save();
                $lims_product_warehouse_data->save();

                // Handle batch creation for new batch data
                $final_batch_id = null;
                if($lims_product_data->is_batch && !empty($batch_no[$key]) && !empty($expire_date[$key])) {
                    // Check if this batch already exists
                    $existing_batch = \App\ProductBatch::where([
                        ['product_id', $pro_id],
                        ['batch_no', $batch_no[$key]]
                    ])->first();
                    
                    if($existing_batch) {
                        $final_batch_id = $existing_batch->id;
                        // Update existing batch quantity
                        if($action[$key] == '+') {
                            $existing_batch->qty += $qty[$key];
                        } elseif($action[$key] == '-') {
                            $existing_batch->qty -= $qty[$key];
                        }
                        $existing_batch->save();
                    } else {
                        // Create new batch - set qty to actual warehouse qty after adjustment
                        $warehouse_qty_after_adjustment = $lims_product_warehouse_data->qty;
                        $new_batch = \App\ProductBatch::create([
                            'product_id' => $pro_id,
                            'batch_no' => $batch_no[$key],
                            'expired_date' => $expire_date[$key],
                            'qty' => $warehouse_qty_after_adjustment
                        ]);
                        $final_batch_id = $new_batch->id;
                    }
                    
                    // Update product_warehouse to use the correct batch_id
                    if($lims_product_warehouse_data->product_batch_id != $final_batch_id) {
                        $lims_product_warehouse_data->product_batch_id = $final_batch_id;
                        $lims_product_warehouse_data->save();
                    }
                } elseif($lims_product_data->is_batch && isset($product_batch_id[$key]) && $product_batch_id[$key]) {
                    // Use existing batch_id from selection
                    $final_batch_id = (int)$product_batch_id[$key];
                    // Update existing batch quantity
                    $existing_batch = \App\ProductBatch::find($final_batch_id);
                    if($existing_batch) {
                        if($action[$key] == '+') {
                            $existing_batch->qty += $qty[$key];
                        } elseif($action[$key] == '-') {
                            $existing_batch->qty -= $qty[$key];
                        }
                        $existing_batch->save();
                    }
                }

                // Create new adjustment record
                ProductAdjustment::create([
                    'product_id' => $pro_id,
                    'variant_id' => $variant_id,
                    'adjustment_id' => $id,
                    'qty' => $qty[$key],
                    'action' => $action[$key],
                    'product_batch_id' => $final_batch_id
                ]);
            }
        }
        
        // Remove adjustments that are no longer in the submitted data
        $submitted_keys = [];
        foreach ($product_id as $key => $pro_id) {
            $variant_id = $product_variant_id[$key] ?? null;
            $submitted_keys[] = $pro_id . '_' . ($variant_id ?? 'null');
        }
        
        foreach ($existing_adjustments as $key => $existing) {
            if (!in_array($key, $submitted_keys)) {
                // This adjustment was removed - reverse it
                
                $lims_product_data = Product::find($existing['product_id']);
                $net_change = 0;
                if ($existing['action'] == '+') {
                    $net_change -= $existing['qty']; // Reverse addition
                } else {
                    $net_change += $existing['qty']; // Reverse subtraction
                }
                
                if ($net_change != 0) {
                    $lims_product_data->qty += $net_change;
                    $lims_product_data->save();
                    
                    // Handle variant products
                    if ($lims_product_data->is_variant && $existing['variant_id']) {
                        $lims_product_variant_data = ProductVariant::where([
                            ['product_id', $existing['product_id']],
                            ['variant_id', $existing['variant_id']]
                        ])->first();
                        if ($lims_product_variant_data) {
                            $lims_product_variant_data->qty += $net_change;
                            $lims_product_variant_data->save();
                        }
                    }
                    
                    // Handle warehouse quantities
                    $lims_product_warehouse_data = null;
                    if ($lims_product_data->is_variant && $existing['variant_id']) {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $existing['product_id']],
                            ['variant_id', $existing['variant_id']],
                            ['warehouse_id', $data['warehouse_id']]
                        ])->first();
                    } else {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $existing['product_id']],
                            ['warehouse_id', $data['warehouse_id']],
                            ['variant_id', null],
                            ['product_batch_id', $existing['product_batch_id']]
                        ])->first();
                    }
                    
                    if ($lims_product_warehouse_data) {
                        $lims_product_warehouse_data->qty += $net_change;
                        $lims_product_warehouse_data->save();
                    }
                    
                    // Handle batch quantities
                    if ($existing['product_batch_id']) {
                        $existing_batch = \App\ProductBatch::find($existing['product_batch_id']);
                        if ($existing_batch) {
                            $existing_batch->qty += $net_change;
                            $existing_batch->save();
                        }
                    }
                }
                
                // Delete the adjustment record
                ProductAdjustment::where('id', $existing['id'])->delete();
            }
        }
        
        $lims_adjustment_data->update($data);
        return redirect('qty_adjustment')->with('message', 'Data updated successfully');
    }

    public function deleteBySelection(Request $request)
    {
        $adjustment_id = $request['adjustmentIdArray'];
        
        // Check if any adjustments are selected
        if(empty($adjustment_id) || !is_array($adjustment_id)) {
            return response()->json([
                'error' => true,
                'message' => 'No adjustments selected for deletion'
            ], 400);
        }
        
        // First, validate all adjustments before deleting any
        foreach ($adjustment_id as $index => $id) {
            $lims_adjustment_data = Adjustment::find($id);
            if (!$lims_adjustment_data) {
                return response()->json([
                    'error' => true,
                    'message' => 'Adjustment not found'
                ], 404);
            }
            
            $lims_product_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();
            
            foreach ($lims_product_adjustment_data as $pa_index => $product_adjustment_data) {
                // Only check for addition actions (we're reversing them, so we need stock available)
                if($product_adjustment_data->action == '+') {
                    // For addition actions, we need to check if we have enough stock to subtract
                    $lims_product_warehouse_data = null;
                    
                    if($product_adjustment_data->variant_id) {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $product_adjustment_data->product_id],
                            ['variant_id', $product_adjustment_data->variant_id],
                            ['warehouse_id', $lims_adjustment_data->warehouse_id]
                        ])->first();
                    } else {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $product_adjustment_data->product_id],
                            ['warehouse_id', $lims_adjustment_data->warehouse_id]
                        ])->first();
                    }
                    
                    if(!$lims_product_warehouse_data || $lims_product_warehouse_data->qty < $product_adjustment_data->qty) {
                        $product_name = Product::find($product_adjustment_data->product_id)->name;
                        $required_qty = $product_adjustment_data->qty;
                        $available_qty = $lims_product_warehouse_data ? $lims_product_warehouse_data->qty : 0;
                        
                        $error_message = "Cannot delete adjustment: Insufficient quantity for product '{$product_name}'. Required: {$required_qty}, Available: {$available_qty}";
                        
                        return response()->json([
                            'error' => true,
                            'message' => $error_message
                        ], 400);
                    }
                }
            }
        }
        
        // If validation passes, proceed with deletion
        foreach ($adjustment_id as $index => $id) {
            $lims_adjustment_data = Adjustment::find($id);
            $lims_product_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();
            
            foreach ($lims_product_adjustment_data as $key => $product_adjustment_data) {
                $lims_product_data = Product::find($product_adjustment_data->product_id);
                
                if($product_adjustment_data->variant_id) {
                    $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($product_adjustment_data->product_id, $product_adjustment_data->variant_id)->first();
                    
                    if (!$lims_product_variant_data) {
                        continue;
                    }
                    
                    $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $product_adjustment_data->product_id],
                            ['variant_id', $product_adjustment_data->variant_id],
                            ['warehouse_id', $lims_adjustment_data->warehouse_id]
                        ])->first();
                    
                    if($product_adjustment_data->action == '-'){
                        $old_qty = $lims_product_variant_data->qty;
                        $lims_product_variant_data->qty += $product_adjustment_data->qty;
                    }
                    elseif($product_adjustment_data->action == '+'){
                        $old_qty = $lims_product_variant_data->qty;
                        $lims_product_variant_data->qty -= $product_adjustment_data->qty;
                    }
                    $lims_product_variant_data->save();
                }
                else {
                    // For batched products, find the specific warehouse record with the correct batch_id
                    if($product_adjustment_data->product_batch_id) {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $product_adjustment_data->product_id],
                            ['warehouse_id', $lims_adjustment_data->warehouse_id],
                            ['product_batch_id', $product_adjustment_data->product_batch_id]
                        ])->first();
                    } else {
                        $lims_product_warehouse_data = Product_Warehouse::where([
                            ['product_id', $product_adjustment_data->product_id],
                            ['warehouse_id', $lims_adjustment_data->warehouse_id],
                            ['variant_id', null],
                            ['product_batch_id', null]
                        ])->first();
                    }
                }
                
                if (!$lims_product_warehouse_data) {
                    continue;
                }
                
                if($product_adjustment_data->action == '-'){
                    $old_product_qty = $lims_product_data->qty;
                    $old_warehouse_qty = $lims_product_warehouse_data->qty;
                    
                    $lims_product_data->qty += $product_adjustment_data->qty;
                    $lims_product_warehouse_data->qty += $product_adjustment_data->qty;
                }
                elseif($product_adjustment_data->action == '+'){
                    $old_product_qty = $lims_product_data->qty;
                    $old_warehouse_qty = $lims_product_warehouse_data->qty;
                    
                    $lims_product_data->qty -= $product_adjustment_data->qty;
                    $lims_product_warehouse_data->qty -= $product_adjustment_data->qty;
                }
                
                // Handle batch quantities if this adjustment has a batch
                if($product_adjustment_data->product_batch_id) {
                    $batch_data = \App\ProductBatch::find($product_adjustment_data->product_batch_id);
                    if($batch_data) {
                        if($product_adjustment_data->action == '-') {
                            $old_batch_qty = $batch_data->qty;
                            $batch_data->qty += $product_adjustment_data->qty;
                        } elseif($product_adjustment_data->action == '+') {
                            $old_batch_qty = $batch_data->qty;
                            $batch_data->qty -= $product_adjustment_data->qty;
                        }
                        $batch_data->save();
                    }
                }

                $lims_product_data->save();
                $lims_product_warehouse_data->save();
                $product_adjustment_data->delete();
            }
            
            $lims_adjustment_data->delete();
        }
        
        $deleted_count = count($adjustment_id);
        
        if($deleted_count == 1) {
            return response()->json(['message' => 'Adjustment deleted successfully']);
        } else {
            return response()->json(['message' => $deleted_count . ' adjustments deleted successfully']);
        }
    }

    public function destroy($id)
    {
        $lims_adjustment_data = Adjustment::find($id);
        
        if (!$lims_adjustment_data) {
            return redirect('qty_adjustment')->with('not_permitted', 'Adjustment not found');
        }
        
        $lims_product_adjustment_data = ProductAdjustment::where('adjustment_id', $id)->get();
        
        // First, validate the adjustment before deleting
        foreach ($lims_product_adjustment_data as $index => $product_adjustment_data) {
            // Only check for addition actions (we're reversing them, so we need stock available)
            if($product_adjustment_data->action == '+') {
                
                // For addition actions, we need to check if we have enough stock to subtract
                $lims_product_warehouse_data = null;
                
                if($product_adjustment_data->variant_id) {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_adjustment_data->product_id],
                        ['variant_id', $product_adjustment_data->variant_id],
                        ['warehouse_id', $lims_adjustment_data->warehouse_id]
                    ])->first();
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_adjustment_data->product_id],
                        ['warehouse_id', $lims_adjustment_data->warehouse_id]
                    ])->first();
                }
                
                if(!$lims_product_warehouse_data || $lims_product_warehouse_data->qty < $product_adjustment_data->qty) {
                    $product_name = Product::find($product_adjustment_data->product_id)->name;
                    $required_qty = $product_adjustment_data->qty;
                    $available_qty = $lims_product_warehouse_data ? $lims_product_warehouse_data->qty : 0;
                    
                    return redirect('qty_adjustment')->with('not_permitted', "Cannot delete adjustment: Insufficient quantity for product '{$product_name}'. Required: {$required_qty}, Available: {$available_qty}");
                }
            }
        }
        
        // If validation passes, proceed with deletion
        foreach ($lims_product_adjustment_data as $key => $product_adjustment_data) {
            $lims_product_data = Product::find($product_adjustment_data->product_id);
            
            if($product_adjustment_data->variant_id) {
                $lims_product_variant_data = ProductVariant::select('id', 'qty')->FindExactProduct($product_adjustment_data->product_id, $product_adjustment_data->variant_id)->first();
                
                if (!$lims_product_variant_data) {
                    continue;
                }
                
                $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_adjustment_data->product_id],
                        ['variant_id', $product_adjustment_data->variant_id],
                        ['warehouse_id', $lims_adjustment_data->warehouse_id]
                    ])->first();
                
                if($product_adjustment_data->action == '-'){
                    $old_qty = $lims_product_variant_data->qty;
                    $lims_product_variant_data->qty += $product_adjustment_data->qty;
                }
                elseif($product_adjustment_data->action == '+'){
                    $old_qty = $lims_product_variant_data->qty;
                    $lims_product_variant_data->qty -= $product_adjustment_data->qty;
                }
                $lims_product_variant_data->save();
            }
            else {
                
                // For batched products, find the specific warehouse record with the correct batch_id
                if($product_adjustment_data->product_batch_id) {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_adjustment_data->product_id],
                        ['warehouse_id', $lims_adjustment_data->warehouse_id],
                        ['product_batch_id', $product_adjustment_data->product_batch_id]
                    ])->first();
                    
                
                } else {
                    $lims_product_warehouse_data = Product_Warehouse::where([
                        ['product_id', $product_adjustment_data->product_id],
                        ['warehouse_id', $lims_adjustment_data->warehouse_id],
                        ['variant_id', null],
                        ['product_batch_id', null]
                    ])->first();
                    
                }
            }
            
            if (!$lims_product_warehouse_data) {
                continue;
            }
            
            if($product_adjustment_data->action == '-'){
                $old_product_qty = $lims_product_data->qty;
                $old_warehouse_qty = $lims_product_warehouse_data->qty;
                
                $lims_product_data->qty += $product_adjustment_data->qty;
                $lims_product_warehouse_data->qty += $product_adjustment_data->qty;
                
                
            }
            elseif($product_adjustment_data->action == '+'){
                $old_product_qty = $lims_product_data->qty;
                $old_warehouse_qty = $lims_product_warehouse_data->qty;
                
                $lims_product_data->qty -= $product_adjustment_data->qty;
                $lims_product_warehouse_data->qty -= $product_adjustment_data->qty;
                
                
            }
            
            // Handle batch quantities if this adjustment has a batch
            if($product_adjustment_data->product_batch_id) {
                $batch_data = \App\ProductBatch::find($product_adjustment_data->product_batch_id);
                if($batch_data) {
                    if($product_adjustment_data->action == '-') {
                        $old_batch_qty = $batch_data->qty;
                        $batch_data->qty += $product_adjustment_data->qty;
                        
                    } elseif($product_adjustment_data->action == '+') {
                        $old_batch_qty = $batch_data->qty;
                        $batch_data->qty -= $product_adjustment_data->qty;
                        
                    }
                    $batch_data->save();
                } else {
                    
                }
            }
            
                $lims_product_data->save();
                $lims_product_warehouse_data->save();

            
                $product_adjustment_data->delete();
            
            
        }
        
        try {
            $lims_adjustment_data->delete();

        } catch (\Exception $e) {

            throw $e;
        }
        
        return redirect('qty_adjustment')->with('not_permitted', 'Data deleted successfully');
    }

    public function checkBatchNumber(Request $request)
    {
        $product_id = $request->product_id;
        $batch_no = $request->batch_no;
        $exclude_batch_id = $request->exclude_batch_id; // For edit mode, exclude current batch
        
        $query = \App\ProductBatch::where([
            ['product_id', $product_id],
            ['batch_no', $batch_no]
        ]);
        
        // Exclude current batch in edit mode
        if($exclude_batch_id) {
            $query->where('id', '!=', $exclude_batch_id);
        }
        
        $exists = $query->exists();
        
        return response()->json(['exists' => $exists]);
    }
}

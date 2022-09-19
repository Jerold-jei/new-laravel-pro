<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Product;
use App\Model\SavedAppalam;
use App\SavedAppalam as AppSavedAppalam;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function list(Request $request, $status)
    {
        $query_param = [];
        $search = $request['search'];
        if ($request->has('search')){
            $key = explode(' ', $request['search']);
            $query = Order::where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('id', 'like', "%{$value}%")
                        ->orWhere('order_status', 'like', "%{$value}%")
                        ->orWhere('transaction_reference', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        } else {
            if (session()->has('branch_filter') == false) {
                session()->put('branch_filter', 0);
            }
            Order::where(['checked' => 0])->update(['checked' => 1]);
            if (session('branch_filter') == 0) {
                if ($status != 'all') {
                    $query = Order::with(['customer', 'branch'])->where(['order_status' => $status]);
                } else {
                    $query = Order::with(['customer', 'branch']);
                }
            } else {
                if ($status != 'all') {
                    $query = Order::with(['customer', 'branch'])->where(['order_status' => $status, 'branch_id' => session('branch_filter')]);
                } else {
                    $query = Order::with(['customer', 'branch'])->where(['branch_id' => session('branch_filter')]);
                }
            }
        }

        $orders = $query->where('order_type', '!=', 'pos')->orderByDesc('id')->paginate(Helpers::pagination_limit())->appends($query_param);
        return view('admin-views.order.list', compact('orders', 'status', 'search'));
    }

    public function details($id)
    {
        $order = Order::with('details')->where(['id' => $id])->first();
        if (isset($order)) {
            return view('admin-views.order.order-view', compact('order'));
        } else {
            Toastr::info(translate('No more orders!'));
            return back();
        }
    }

    public function status(Request $request)
    {
        $order = Order::find($request->id);
        // if ($request->order_status == 'out_for_delivery' && $order['delivery_man_id'] == null && $order['order_type'] != 'self_pickup') {
        //     Toastr::warning(translate('Please assign delivery man first!'));
        //     return back();
        // }
        if ($request->order_status == 'returned' || $request->order_status == 'failed' || $request->order_status == 'canceled') {
            foreach ($order->details as $detail) {
                if ($detail['is_stock_decreased'] == 1) {
                    $product = Product::find($detail['product_id']);
                    $type = json_decode($detail['variation'])[0]->type;
                    $var_store = [];
                    foreach (json_decode($product['variations'], true) as $var) {
                        if ($type == $var['type']) {
                            $var['stock'] += $detail['quantity'];
                        }
                        array_push($var_store, $var);
                    }
                    Product::where(['id' => $product['id']])->update([
                        'variations' => json_encode($var_store),
                        'total_stock' => $product['total_stock'] + $detail['quantity'],
                    ]);
                    OrderDetail::where(['id' => $detail['id']])->update([
                        'is_stock_decreased' => 0
                    ]);
                }
            }
        } else {
            foreach ($order->details as $detail) {
                if ($detail['is_stock_decreased'] == 0) {
                    $product = Product::find($detail['product_id']);

                    //check stock
                    foreach ($order->details as $c) {
                        $product = Product::find($c['product_id']);
                        $type = json_decode($c['variation'])[0]->type;
                        foreach (json_decode($product['variations'], true) as $var) {
                            if ($type == $var['type'] && $var['stock'] < $c['quantity']) {
                                Toastr::error(translate('Stock is insufficient!'));
                                return back();
                            }
                        }
                    }

                    $type = json_decode($detail['variation'])[0]->type;
                    $var_store = [];
                    foreach (json_decode($product['variations'], true) as $var) {
                        if ($type == $var['type']) {
                            $var['stock'] -= $detail['quantity'];
                        }
                        array_push($var_store, $var);
                    }
                    Product::where(['id' => $product['id']])->update([
                        'variations' => json_encode($var_store),
                        'total_stock' => $product['total_stock'] - $detail['quantity'],
                    ]);
                    OrderDetail::where(['id' => $detail['id']])->update([
                        'is_stock_decreased' => 1
                    ]);
                }
            }
        }

        $order->order_status = $request->order_status;
        $order->save();

        $fcm_token = isset($order->customer) ? $order->customer->cm_firebase_token : null;
        $value = Helpers::order_status_update_message($request->order_status);
        try {
            if ($value) {
                $data = [
                    'title' => \App\CentralLogics\translate('Order'),
                    'description' => $value,
                    'order_id' => $order['id'],
                    'image' => '',
                ];
                if($fcm_token != null) {
                    Helpers::send_push_notif_to_device($fcm_token, $data);
                }
            }
        } catch (\Exception $e) {
            Toastr::warning(\App\CentralLogics\translate('Push notification failed for Customer!'));
        }

        //delivery man notification
        if ($request->order_status == 'processing' && $order->delivery_man != null) {
            $fcm_token = $order->delivery_man->fcm_token;
            $value = \App\CentralLogics\translate('One of your order is in processing');
            try {
                if ($value) {
                    $data = [
                        'title' => \App\CentralLogics\translate('Order'),
                        'description' => $value,
                        'order_id' => $order['id'],
                        'image' => '',
                    ];
                    Helpers::send_push_notif_to_device($fcm_token, $data);
                }
            } catch (\Exception $e) {
                Toastr::warning(\App\CentralLogics\translate('Push notification failed for DeliveryMan!'));
            }
        }

        Toastr::success(translate('Order status updated!'));
        return back();
    }

    public function add_delivery_man($order_id, $delivery_man_id)
    {
        if ($delivery_man_id == 0) {
            return response()->json([], 401);
        }
        $order = Order::find($order_id);
        if($order->order_status == 'delivered' || $order->order_status == 'returned' || $order->order_status == 'failed' || $order->order_status == 'canceled') {
            return response()->json(['status' => false], 200);
        }
        $order->delivery_man_id = $delivery_man_id;
        $order->save();

        $fcm_token = $order->delivery_man->fcm_token;
        $customer_fcm_token = isset($order->customer) ? $order->customer->cm_firebase_token : null;
        $value = Helpers::order_status_update_message('del_assign');
        try {
            if ($value) {
                $data = [
                    'title' => translate('Order'),
                    'description' => $value,
                    'order_id' => $order['id'],
                    'image' => '',
                ];
                Helpers::send_push_notif_to_device($fcm_token, $data);
                $cs_notify_message = Helpers::order_status_update_message('customer_notify_message');
                if($cs_notify_message) {
                    $data['description'] = $cs_notify_message;
                    if($customer_fcm_token != null) {
                        Helpers::send_push_notif_to_device($customer_fcm_token, $data);
                    }
                }
            }
        } catch (\Exception $e) {
            Toastr::warning(\App\CentralLogics\translate('Push notification failed for DeliveryMan!'));
        }

        return response()->json(['status' => true], 200);
    }

    public function payment_status(Request $request)
    {
        $order = Order::find($request->id);
        if ($request->payment_status == 'paid' && $order['transaction_reference'] == null && $order['payment_method'] != 'cash_on_delivery') {
            Toastr::warning('Add your payment reference code first!');
            return back();
        }
        $order->payment_status = $request->payment_status;
        $order->save();
        Toastr::success(translate('Payment status updated!'));
        return back();
    }

    public function update_shipping(Request $request, $id)
    {
        $request->validate([
            'contact_person_name' => 'required',
            'address_type' => 'required',
            'contact_person_number' => 'required',
            'address' => 'required'
        ]);

        $address = [
            'contact_person_name' => $request->contact_person_name,
            'contact_person_number' => $request->contact_person_number,
            'address_type' => $request->address_type,
            'address' => $request->address,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'created_at' => now(),
            'updated_at' => now()
        ];

        DB::table('customer_addresses')->where('id', $id)->update($address);
        Toastr::success(translate('Payment status updated!'));
        return back();
    }

    public function generate_invoice($id)
    {
        $order = Order::where('id', $id)->first();
        return view('admin-views.order.invoice-2', compact('order'));
    }  
    
    public function print_address($id)
    {
        $order = Order::where('id', $id)->first();
        return view('admin-views.order.new', compact('order'));
    }  


    public function print_image($id)
    {   
        // $customized = DB::table('saved_appalam')
        // ->select('saved_appalam.name','saved_appalam.image','order_details.order_id')
        // ->join('order_details','saved_appalam.order_id','=','order_details.order_id')
        // ->where('saved_appalam.order_id', $id)->get();   

        // $customized = DB::table('saved_appalam')
        // ->select('saved_appalam.name','saved_appalam.image','saved_appalam.category_id','order_details.order_id','categories.id')
        // ->leftJoin('order_details','saved_appalam.order_id','=','order_details.order_id')
        // ->leftJoin('categories','saved_appalam.category_id','=','categories.id')
        // ->orWhere('saved_appalam.category_id','=','categories.id')
        // ->orWhere('saved_appalam.order_id', $id)->get();   

        // $customized = DB::table('saved_appalam')
        // ->select("saved_appalam.name",'saved_appalam.image',"saved_appalam.category_id","order_details.order_id","categories.id")
        // ->leftJoin("order_details","order_details.order_id","=","saved_appalam.order_id")
        // ->leftJoin("products","products.id","=","order_details.product_id")
        // ->leftJoin("categories",function($join){
        //     $join->on("categories.id","=","saved_appalam.category_id");
        // })->where('saved_appalam.order_id', $id)->get();        
        
        $customized = DB::table('saved_appalam')
        ->select("saved_appalam.name","saved_appalam.image")
        ->orWhere("saved_appalam.order_id", $id)
        ->get();

        $order = Order::where('id', $id)->first();
        return view('admin-views.order.print', compact('order','customized'));
    }

    public function check_cat($cat_id)
    {  
    $check_category = DB::table('categories')
    ->select('categories.name')       
    ->where('categories.id', $cat_id)->get();
    return view('admin-views.order.print', compact('check_category'));
    }

    public function add_payment_ref_code(Request $request, $id)
    {
        Order::where(['id' => $id])->update([
            'transaction_reference' => $request['transaction_reference']
        ]);

        Toastr::success(translate('Payment reference code is added!'));
        return back();
    }

    public function branch_filter($id)
    {
        session()->put('branch_filter', $id);
        return back();
    }
}

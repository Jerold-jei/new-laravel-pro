<?php

namespace App\Http\Controllers\Branch;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Order;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function list($status, Request $request)
    {
        $query_param = [];
        $search = $request['search'];
        $orders= Order::where(['branch_id' => auth('branch')->id()]);
        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $orders=$orders->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('id', 'like', "%{$value}%")
                        ->orWhere('order_status', 'like', "%{$value}%")
                        ->orWhere('transaction_reference', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        }

        Order::where(['checked' => 0, 'branch_id' => auth('branch')->id()])->update(['checked' => 1]);
        if ($status != 'all') {
            $orders = $orders->where(['order_status' => $status]);
        }

        $orders = $orders->notPos()->orderByDesc('id')->with(['customer'])->latest()->paginate(Helpers::pagination_limit())->appends($query_param);
        return view('branch-views.order.list', compact('orders', 'status', 'search'));
    }

    public function search(Request $request){
        $key = explode(' ', $request['search']);
        $orders=Order::where(['branch_id'=>auth('branch')->id()])->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('id', 'like', "%{$value}%")
                    ->orWhere('order_status', 'like', "%{$value}%")
                    ->orWhere('transaction_reference', 'like', "%{$value}%");
            }
        })->get();
        return response()->json([
            'view'=>view('branch-views.order.partials._table',compact('orders'))->render()
        ]);
    }

    public function details($id)
    {
        $order = Order::with('details')->where(['id' => $id, 'branch_id' => auth('branch')->id()])->first();
        if (isset($order)) {
            return view('branch-views.order.order-view', compact('order'));
        } else {
            Toastr::info(translate('No more orders!'));
            return back();
        }
    }

    public function status(Request $request)
    {
        $order = Order::where(['id' => $request->id, 'branch_id' => auth('branch')->id()])->first();

        //Branch can not change order status after being delivered
        if($order->order_status == 'delivered') {
            Toastr::warning(translate('The order has already been delivered!'));
            return back();
        }

        if ($request->order_status == 'out_for_delivery' && $order['delivery_man_id'] == null && $order['order_type'] != 'self_pickup') {
            Toastr::warning(translate('Please assign delivery man first!'));
            return back();
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
                    'image' => '',
                    'order_id' => $order->id,
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
        $order = Order::where(['id' => $order_id, 'branch_id' => auth('branch')->id()])->first();
        $order->delivery_man_id = $delivery_man_id;
        $order->save();

        $fcm_token = $order->delivery_man->fcm_token;
        $value = Helpers::order_status_update_message('del_assign');
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

        Toastr::success(translate('Order deliveryman added!'));
        return response()->json([], 200);
    }

    public function payment_status(Request $request)
    {
        $order = Order::where(['id' => $request->id, 'branch_id' => auth('branch')->id()])->first();
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
        $order = Order::where(['id' => $id, 'branch_id' => auth('branch')->id()])->first();
        return view('branch-views.order.invoice', compact('order'));
    }

    public function add_payment_ref_code(Request $request, $id)
    {
        Order::where(['id' => $id, 'branch_id' => auth('branch')->id()])->update([
            'transaction_reference' => $request['transaction_reference']
        ]);

        Toastr::success(translate('Payment reference code is added!'));
        return back();
    }
}

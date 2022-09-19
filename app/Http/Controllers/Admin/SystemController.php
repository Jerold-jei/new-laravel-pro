<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Admin;
use App\Model\Branch;
use App\Model\Brand;
use App\Model\Category;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Product;
use App\Model\Review;
use App\Model\SellerWalletHistory;
use App\Model\Shop;
use App\User;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemController extends Controller
{
    public function fcm($id)
    {
        $fcm_token = Admin::find(auth('admin')->id())->fcm_token;
        $data = [
            'title' => 'New auto generate message arrived from admin dashboard',
            'description' => $id,
            'order_id' => '',
            'image' => '',
        ];
        Helpers::send_push_notif_to_device($fcm_token, $data);

        return "Notification sent to admin";
    }

    public function dashboard()
    {
        $top_sell = OrderDetail::with(['product'])
            ->select('product_id', DB::raw('SUM(quantity) as count'))
            ->groupBy('product_id')
            ->orderBy("count", 'desc')
            ->take(6)
            ->get();

        $most_rated_products = Product::rightJoin('reviews', 'reviews.product_id', '=', 'products.id')
            ->groupBy('product_id')
            ->select(['product_id',
                DB::raw('AVG(reviews.rating) as ratings_average'),
                DB::raw('count(*) as total')
            ])
            ->orderBy('total', 'desc')
            ->take(6)
            ->get();

        $top_customer = Order::with(['customer'])
            ->select('user_id', DB::raw('COUNT(user_id) as count'))
            ->groupBy('user_id')
            ->orderBy("count", 'desc')
            ->take(6)
            ->get();

        $data = self::order_stats_data();

        $data['customer'] = User::count();
        $data['product'] = Product::count();
        $data['order'] = Order::count();
        $data['category'] = Category::where('parent_id', 0)->count();
        $data['branch'] = Branch::count();

        $data['top_sell'] = $top_sell;
        $data['most_rated_products'] = $most_rated_products;
        $data['top_customer'] = $top_customer;

        $from = \Carbon\Carbon::now()->startOfYear()->format('Y-m-d');
        $to = \Illuminate\Support\Carbon::now()->endOfYear()->format('Y-m-d');

        $earning = [];
        $earning_data = Order::where([
            'order_status' => 'delivered'
        ])->select(
            DB::raw('IFNULL(sum(order_amount),0) as sums'),
            DB::raw('YEAR(created_at) year, MONTH(created_at) month')
        )->whereBetween('created_at', [$from, $to])->groupby('year', 'month')->get()->toArray();
        for ($inc = 1; $inc <= 12; $inc++) {
            $earning[$inc] = 0;
            foreach ($earning_data as $match) {
                if ($match['month'] == $inc) {
                    $earning[$inc] = $match['sums'];
                }
            }
        }

        return view('admin-views.dashboard', compact('data', 'earning'));
    }

    public function order_stats(Request $request)
    {
        session()->put('statistics_type', $request['statistics_type']);
        $data = self::order_stats_data();

        return response()->json([
            'view' => view('admin-views.partials._dashboard-order-stats', compact('data'))->render()
        ], 200);
    }

    public function order_stats_data() {
        $today = session()->has('statistics_type') && session('statistics_type') == 'today' ? 1 : 0;
        $this_month = session()->has('statistics_type') && session('statistics_type') == 'this_month' ? 1 : 0;

        $pending = Order::where(['order_status' => 'pending'])
                        ->when($today, function ($query) {
                            return $query->whereDate('created_at', Carbon::today());
                        })
                        ->when($this_month, function ($query) {
                            return $query->whereMonth('created_at', Carbon::now());
                        })
                        ->count();
        $confirmed = Order::where(['order_status' => 'confirmed'])
                        ->when($today, function ($query) {
                            return $query->whereDate('created_at', Carbon::today());
                        })
                        ->when($this_month, function ($query) {
                            return $query->whereMonth('created_at', Carbon::now());
                        })
                        ->count();
        $processing = Order::where(['order_status' => 'processing'])
                        ->when($today, function ($query) {
                            return $query->whereDate('created_at', Carbon::today());
                        })
                        ->when($this_month, function ($query) {
                            return $query->whereMonth('created_at', Carbon::now());
                        })
                        ->count();
        $out_for_delivery = Order::where(['order_status' => 'out_for_delivery'])
                        ->when($today, function ($query) {
                            return $query->whereDate('created_at', Carbon::today());
                        })
                        ->when($this_month, function ($query) {
                            return $query->whereMonth('created_at', Carbon::now());
                        })
                        ->count();
        $delivered = Order::where(['order_status' => 'delivered'])->notPos()
                        ->when($today, function ($query) {
                            return $query->whereDate('created_at', Carbon::today());
                        })
                        ->when($this_month, function ($query) {
                            return $query->whereMonth('created_at', Carbon::now());
                        })
                        ->count();
        $all = Order::notPos()
            ->when($today, function ($query) {
                return $query->whereDate('created_at', \Illuminate\Support\Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        $returned = Order::where(['order_status' => 'returned'])
                        ->when($today, function ($query) {
                            return $query->whereDate('created_at', Carbon::today());
                        })
                        ->when($this_month, function ($query) {
                            return $query->whereMonth('created_at', Carbon::now());
                        })
                        ->count();
        $failed = Order::where(['order_status' => 'failed'])
                        ->when($today, function ($query) {
                            return $query->whereDate('created_at', Carbon::today());
                        })
                        ->when($this_month, function ($query) {
                            return $query->whereMonth('created_at', Carbon::now());
                        })
                        ->count();

        $data = [
            'pending' => $pending,
            'confirmed' => $confirmed,
            'processing' => $processing,
            'out_for_delivery' => $out_for_delivery,
            'delivered' => $delivered,
            'all' => $all,
            'returned' => $returned,
            'failed' => $failed
        ];

        return $data;
    }

    public function restaurant_data()
    {
        $new_order = DB::table('orders')->where(['checked' => 0])->count();
        return response()->json([
            'success' => 1,
            'data' => ['new_order' => $new_order]
        ]);
    }

    public function settings()
    {
        return view('admin-views.settings');
    }

    public function settings_update(Request $request)
    {
        $request->validate([
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required',
            'phone' => 'required',
        ], [
            'f_name.required' => 'First name is required!',
            'l_name.required' => 'Last name is required!',
        ]);

        $admin = Admin::find(auth('admin')->id());
        $admin->f_name = $request->f_name;
        $admin->l_name = $request->l_name;
        $admin->email = $request->email;
        $admin->phone = $request->phone;
        $admin->image = $request->has('image') ? Helpers::update('admin/', $admin->image, 'png', $request->file('image')) : $admin->image;
        $admin->save();
        Toastr::success(translate('Admin updated successfully!'));
        return back();
    }

    public function settings_password_update(Request $request)
    {
        $request->validate([
            'password' => 'required|same:confirm_password|min:8',
            'confirm_password' => 'required',
        ]);

        $admin = Admin::find(auth('admin')->id());
        $admin->password = bcrypt($request['password']);
        $admin->save();
        Toastr::success(translate('Admin password updated successfully!'));
        return back();
    }
}

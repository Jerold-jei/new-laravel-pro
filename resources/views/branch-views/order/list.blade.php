@extends('layouts.branch.app')

@section('title', translate('Order List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center mb-3">
                <div class="col-sm">
                    <h1 class="page-header-title">{{\App\CentralLogics\translate('orders')}}
                        <span
                            class="badge badge-soft-dark ml-2">{{ $orders->total() }}</span>
                    </h1>
                </div>
            </div>
            <!-- End Row -->

            <!-- Nav Scroller -->
            <div class="js-nav-scroller hs-nav-scroller-horizontal">
            <span class="hs-nav-scroller-arrow-prev" style="display: none;">
              <a class="hs-nav-scroller-arrow-link" href="javascript:;">
                <i class="tio-chevron-left"></i>
              </a>
            </span>

                <span class="hs-nav-scroller-arrow-next" style="display: none;">
              <a class="hs-nav-scroller-arrow-link" href="javascript:;">
                <i class="tio-chevron-right"></i>
              </a>
            </span>

            </div>
            <!-- End Nav Scroller -->
        </div>
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header">
                <div class="row justify-content-end align-items-center flex-grow-1">
                    <div class="mr-2">
                        <form action="{{url()->current()}}" method="GET">
                            <div class="input-group">
                                <input id="datatableSearch_" type="search" name="search"
                                       class="form-control"
                                       placeholder="{{translate('Search')}}" aria-label="Search"
                                       value="{{$search}}" required autocomplete="off">
                                <div class="input-group-append">
                                    <button type="submit" class="input-group-text"><i class="tio-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- End Row -->
            </div>
            <!-- End Header -->

            <!-- Table -->
            <div class="table-responsive datatable-custom">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                       style="width: 100%">
                    <thead class="thead-light">
                    <tr>
                        <th class="">
                            {{\App\CentralLogics\translate('#')}}
                        </th>
                        <th class="table-column-pl-0">{{\App\CentralLogics\translate('order')}}</th>
                        <th>{{\App\CentralLogics\translate('date')}}</th>
                        <th>{{\App\CentralLogics\translate('customer')}}</th>
                        <th>{{\App\CentralLogics\translate('payment')}} {{\App\CentralLogics\translate('status')}}</th>

                        <th>{{\App\CentralLogics\translate('total')}}</th>
                        <th>{{\App\CentralLogics\translate('order')}} {{\App\CentralLogics\translate('status')}}</th>
                        <th>{{\App\CentralLogics\translate('actions')}}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                    @foreach($orders as $key=>$order)

                        <tr class="status-{{$order['order_status']}} class-all">
                            <td class="">
                                {{$orders->firstitem()+$key}}
                            </td>
                            <td class="table-column-pl-0">
                                <a href="{{route('branch.orders.details',['id'=>$order['id']])}}">{{$order['id']}}</a>
                            </td>
                            <td>{{date('d M Y',strtotime($order['created_at']))}}</td>
                            <td>
                                @if($order->customer)
                                    <a class="text-body text-capitalize"
                                       href="{{route('branch.orders.details',['id'=>$order['id']])}}">{{$order->customer['f_name'].' '.$order->customer['l_name']}}</a>
                                @else
                                    <label
                                        class="badge badge-danger">{{\App\CentralLogics\translate('invalid')}} {{\App\CentralLogics\translate('customer')}} {{\App\CentralLogics\translate('data')}}</label>
                                @endif
                            </td>
                            <td>
                                @if($order->payment_status=='paid')
                                    <span class="badge badge-soft-success">
                                      <span class="legend-indicator bg-success"></span>{{\App\CentralLogics\translate('paid')}}
                                    </span>
                                @else
                                    <span class="badge badge-soft-danger">
                                      <span class="legend-indicator bg-danger"></span>{{\App\CentralLogics\translate('unpaid')}}
                                    </span>
                                @endif
                            </td>
                            <td>{{ Helpers::set_symbol($order['order_amount']) }}</td>
                            <td class="text-capitalize">
                                @if($order['order_status']=='pending')
                                    <span class="badge badge-soft-info ml-2 ml-sm-3">
                                        <span class="legend-indicator bg-info"></span>{{\App\CentralLogics\translate('pending')}}
                                    </span>
                                @elseif($order['order_status']=='confirmed')
                                    <span class="badge badge-soft-info ml-2 ml-sm-3">
                                      <span class="legend-indicator bg-info"></span>{{\App\CentralLogics\translate('confirmed')}}
                                    </span>
                                @elseif($order['order_status']=='processing')
                                    <span class="badge badge-soft-warning ml-2 ml-sm-3">
                                      <span class="legend-indicator bg-warning"></span>{{\App\CentralLogics\translate('processing')}}
                                    </span>
                                @elseif($order['order_status']=='out_for_delivery')
                                    <span class="badge badge-soft-warning ml-2 ml-sm-3">
                                      <span class="legend-indicator bg-warning"></span>{{\App\CentralLogics\translate('out_for_delivery')}}
                                    </span>
                                @elseif($order['order_status']=='delivered')
                                    <span class="badge badge-soft-success ml-2 ml-sm-3">
                                      <span class="legend-indicator bg-success"></span>{{\App\CentralLogics\translate('delivered')}}
                                    </span>
                                @else
                                    <span class="badge badge-soft-danger ml-2 ml-sm-3">
                                      <span class="legend-indicator bg-danger"></span>{{str_replace('_',' ',$order['order_status'])}}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                                            id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                            aria-expanded="false">
                                        <i class="tio-settings"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <a class="dropdown-item"
                                           href="{{route('branch.orders.details',['id'=>$order['id']])}}"><i
                                                class="tio-visible"></i> {{\App\CentralLogics\translate('view')}}</a>
                                        <a class="dropdown-item" target="_blank"
                                           href="{{route('branch.orders.generate-invoice',[$order['id']])}}"><i
                                                class="tio-download"></i> {{\App\CentralLogics\translate('invoice')}}</a>
                                    </div>
                                </div>
                            </td>
                        </tr>

                    @endforeach
                    </tbody>
                </table>
            </div>
            <!-- End Table -->

            <!-- Footer -->
            <div class="card-footer">
                {!! $orders->links() !!}
            </div>
            <!-- End Footer -->
        </div>
        <!-- End Card -->
    </div>
@endsection

@push('script_2')
    <script>
        $('#search-form').on('submit', function () {
            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('branch.order.search')}}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function () {
                    $('#loading').show();
                },
                success: function (data) {
                    $('#set-rows').html(data.view);
                    $('.card-footer').hide();
                },
                complete: function () {
                    $('#loading').hide();
                },
            });
        });
    </script>
@endpush

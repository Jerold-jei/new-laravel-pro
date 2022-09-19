@extends('layouts.admin.app')

@section('title', translate('Ordered Papad List'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">{{\App\CentralLogics\translate('ordered')}} {{\App\CentralLogics\translate('papad')}} {{\App\CentralLogics\translate('list')}} <span class="mx-1 text-primary">{{ $reviews->total() }}</span></h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <!-- Card -->
                <div class="card">
                    <!-- Header -->
                    <div class="card-header flex-between">
                        <h5 class="card-header-title"></h5>
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
                    <!-- End Header -->

                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table id="columnSearchDatatable"
                               class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                               data-hs-datatables-options='{
                                 "order": [],
                                 "orderCellsTop": true
                               }'>
                            <thead class="thead-light">
                            <tr>
                                <th>{{\App\CentralLogics\translate('#')}}</th>
                                <th style="width: 30%">{{\App\CentralLogics\translate('User ID')}}</th>
                                <th style="width: 25%">{{\App\CentralLogics\translate('order ID')}}</th>
                                <th>{{\App\CentralLogics\translate('image')}}</th>
                                <!-- <th>{{\App\CentralLogics\translate('rating')}}</th> -->
                            </tr>
                            </thead>

                            <tbody>
                            @foreach($reviews as $key=>$review)
                                    <tr>
                                        <td>{{$reviews->firstitem()+$key}}</td>
                                        <td>
                                        <span class="d-block font-size-sm text-body">
                                                @if($review->delivery_man)
                                                <a href="{{route('admin.delivery-man.preview',[$review['delivery_man_id']])}}">
                                                    {{$review->delivery_man->f_name.' '.$review->delivery_man->l_name}}
                                                </a>
                                                @else
                                                    <span class="badge-pill badge-soft-dark text-muted text-sm small">
                                                        {{\App\CentralLogics\translate('DeliveryMan unavailable')}}
                                                    </span>
                                                @endif
                                        </span>
                                        </td>
                                        <td>
                                            @if(isset($review->customer))
                                                <a href="{{route('admin.customer.view',[$review->user_id])}}">
                                                    {{$review->customer->f_name." ".$review->customer->l_name}}
                                                </a>
                                            @else
                                                <span class="badge-pill badge-soft-dark text-muted text-sm small">
                                                    {{translate('Customer unavailable')}}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            {{$review->comment}}
                                        </td>
                                        <!-- <td>
                                            <label class="badge badge-soft-info">
                                                {{$review->rating}} <i class="tio-star"></i>
                                            </label>
                                        </td> -->
                                    </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <!-- <hr> -->
                        <!-- <table>
                            <tfoot>
                            {!! $reviews->links() !!}
                            </tfoot>
                        </table> -->
                    </div>
                    <!-- End Table -->
                </div>
                <!-- End Card -->
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    <script>
        $(document).on('ready', function () {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            var datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

            $('#column1_search').on('keyup', function () {
                datatable
                    .columns(1)
                    .search(this.value)
                    .draw();
            });

            $('#column2_search').on('keyup', function () {
                datatable
                    .columns(2)
                    .search(this.value)
                    .draw();
            });

            $('#column3_search').on('change', function () {
                datatable
                    .columns(3)
                    .search(this.value)
                    .draw();
            });

            $('#column4_search').on('keyup', function () {
                datatable
                    .columns(4)
                    .search(this.value)
                    .draw();
            });


            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function () {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });
    </script>
@endpush

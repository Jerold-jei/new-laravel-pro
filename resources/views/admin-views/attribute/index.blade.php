@extends('layouts.admin.app')

@section('title', translate('Add new attribute'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title"><i class="tio-add-circle-outlined"></i> {{\App\CentralLogics\translate('add')}} {{\App\CentralLogics\translate('new')}} {{\App\CentralLogics\translate('attribute')}}</h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <form action="{{route('admin.attribute.store')}}" method="post">
                    @csrf
                    @php($language=\App\Model\BusinessSetting::where('key','language')->first())
                    @php($language = $language->value ?? null)
                    @php($default_lang = 'en')

                    @if($language)
                        @php($default_lang = json_decode($language)[0])
                        <ul class="nav nav-tabs mb-4">
                            @foreach(json_decode($language) as $lang)
                                <li class="nav-item">
                                    <a class="nav-link lang_link {{$lang == $default_lang? 'active':''}}" href="#" id="{{$lang}}-link">{{\App\CentralLogics\Helpers::get_language_name($lang).'('.strtoupper($lang).')'}}</a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="row">
                            <div class="col-12">
                                @foreach(json_decode($language) as $lang)
                                    <div class="form-group {{$lang != $default_lang ? 'd-none':''}} lang_form" id="{{$lang}}-form">
                                        <label class="input-label" for="exampleFormControlInput1">{{\App\CentralLogics\translate('name')}} ({{strtoupper($lang)}})</label>
                                        <input type="text" name="name[]" class="form-control" placeholder="New Attribute" {{$lang == $default_lang? 'required':''}} oninvalid="document.getElementById('en-link').click()" maxlength="255">
                                    </div>
                                    <input type="hidden" name="lang[]" value="{{$lang}}">
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group {{$lang != $default_lang ? 'd-none':''}} lang_form" id="{{$lang}}-form">
                                    <label class="input-label" for="exampleFormControlInput1">{{\App\CentralLogics\translate('name')}} ({{strtoupper($lang)}})</label>
                                    <input type="text" name="name[]" class="form-control" placeholder="New Attribute" {{$lang == $default_lang? 'required':''}}>
                                </div>
                                <input type="hidden" name="lang[]" value="{{$lang}}">
                            </div>
                        </div>
                    @endif
                    <button type="submit" class="btn btn-primary">{{\App\CentralLogics\translate('submit')}}</button>
                </form>
            </div>

            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-header">
                        <div class="flex-start">
                            <h5 class="card-header-title">{{\App\CentralLogics\translate('Attribute Table')}} <span class="card-header-title text-primary mx-1">({{ $attributes->total() }})</span></h5>
                        </div>
                    </div>
                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                            <tr>
                                <th>{{\App\CentralLogics\translate('#')}}</th>
                                <th style="width: 50%">{{\App\CentralLogics\translate('name')}}</th>
                                <th style="width: 10%">{{\App\CentralLogics\translate('action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($attributes as $key=>$attribute)
                                <tr>
                                    <td>{{$attributes->firstitem()+$key}}</td>
                                    <td>
                                    <span class="d-block font-size-sm text-body">
                                        {{$attribute['name']}}
                                    </span>
                                    </td>
                                    <td>
                                        <!-- Dropdown -->
                                        <div class="dropdown">
                                            <button class="btn btn-secondary dropdown-toggle" type="button"
                                                    id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                                    aria-expanded="false">
                                                <i class="tio-settings"></i>
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <a class="dropdown-item"
                                                   href="{{route('admin.attribute.edit',[$attribute['id']])}}">{{\App\CentralLogics\translate('edit')}}</a>
                                                <a class="dropdown-item" href="javascript:"
                                                   onclick="form_alert('attribute-{{$attribute['id']}}','{{\App\CentralLogics\translate('Want to delete this attribute ?')}}')">{{\App\CentralLogics\translate('delete')}}</a>
                                                <form action="{{route('admin.attribute.delete',[$attribute['id']])}}"
                                                      method="post" id="attribute-{{$attribute['id']}}">
                                                    @csrf @method('delete')
                                                </form>
                                            </div>
                                        </div>
                                        <!-- End Dropdown -->
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <hr>
                        <table>
                            <tfoot>
                            {!! $attributes->links() !!}
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <!-- End Table -->
        </div>
    </div>

@endsection

@push('script_2')
    <script>
        $(".lang_link").click(function(e){
            e.preventDefault();
            $(".lang_link").removeClass('active');
            $(".lang_form").addClass('d-none');
            $(this).addClass('active');

            let form_id = this.id;
            let lang = form_id.split("-")[0];
            console.log(lang);
            $("#"+lang+"-form").removeClass('d-none');
            if(lang == '{{$default_lang}}')
            {
                $(".from_part_2").removeClass('d-none');
            }
            else
            {
                $(".from_part_2").addClass('d-none');
            }
        });
    </script>
@endpush

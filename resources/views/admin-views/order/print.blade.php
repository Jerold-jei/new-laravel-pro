<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Title -->
    <title>{{ translate('Print Image') }}</title>
    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&amp;display=swap" rel="stylesheet">
    <!-- CSS Implementing Plugins -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/vendor.min.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/vendor/icon-set/style.css">
    <!-- CSS Front Template -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/theme.minc619.css?v=1.0">
</head>

<body class="footer-offset">

    <main id="content" role="main" class="main pointer-event">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-6">

                </div>
                <div class="col-6">
                    <h2 class="float-right">{{ translate('#Image') }}</h2>
                </div>
            </div>

            <div class="row">
                <div class="col-4">
                    <!-- <img width="150"
                     src="{{asset('storage/app/public/ecommerce')}}/{{\App\Model\BusinessSetting::where(['key'=>'logo'])->first()->value}}">
                <br><br> -->
                    <!-- <strong>{{ translate('Phone') }} : {{\App\Model\BusinessSetting::where(['key'=>'phone'])->first()->value}}</strong><br>
                <strong>{{ translate('Email') }} : {{\App\Model\BusinessSetting::where(['key'=>'email_address'])->first()->value}}</strong><br>
                <strong>{{ translate('Address') }} : {{\App\Model\BusinessSetting::where(['key'=>'address'])->first()->value}}</strong> -->
                    <br><br>
                </div>
                <div class="col-4"></div>
                <div class="col-4">
                    @if($order->customer)
                    <strong class="float-right">{{ translate('Order ID') }} : {{$order['id']}}</strong><br>
                    <strong class="float-right">{{ translate('Customer Name') }}
                        : {{$order->customer['f_name'].' '.$order->customer['l_name']}}</strong><br>
                    <strong class="float-right">{{ translate('Phone') }}
                        : {{$order->customer['phone']}}</strong><br>                    
                    @endif
                </div>
            </div>
        
            <div class="row">
                <button type="submit" id="p_image" class="btn btn-primary">Print Image</button>
                <div class="col-12">
                    <!-- Card -->       
                        @php($arr = json_decode($customized))         
                        {{$customized}}             
                        @foreach($arr as $new)  
                        @php($cus_url = "https://libsitservices.com/arulapi/api/Uploads/") 
                        @php($cus_img = $new->name)

                            <!-- Media $fileName-->
                           <div class="media"  style="margin-left:180px; margin-top:100px; margin-bottom:100px;">
                                <div class="avatar avatar-xl">
                                    <img class="center" id="img"
                                             src="{{$cus_url}}{{$cus_img}}"
                                             onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'"
                                             alt="Image Description" style="width:630px; height:912px;">                               
                                </div>                                
                            </div>
                       
                        @endforeach
                       
                </div>
            </div>
            
        </div>
        <!-- <div class="footer">
            <div class="row justify-content-between align-items-center">
                <div class="col">
                    <p class="font-size-sm mb-0">
                        &copy; {{\App\Model\BusinessSetting::where(['key'=>'restaurant_name'])->first()->value}}. <span class="d-none d-sm-inline-block">{{\App\Model\BusinessSetting::where(['key'=>'footer_text'])->first()->value}}</span>
                    </p>
                </div>
            </div>
        </div> -->
    </main>

    <script src="{{asset('public/assets/admin')}}/js/demo.js"></script>
    <!-- JS Implementing Plugins -->
    <!-- JS Front -->
    <script src="{{asset('public/assets/admin')}}/js/vendor.min.js"></script>
    <script src="{{asset('public/assets/admin')}}/js/theme.min.js"></script>
    <script>
         let count = 0;
        $("#img").on("click", function() {
            count++;
              if (count == 1) {
                $(this).clone().appendTo(this);
                $(this).css("transform", "rotateY(180deg)");
                $(this).attr("src", $cus_url.$cus_img);
            }
            })
        
        
        $("#p_image").on("click", function() {            
            window.print("#img");
            })
            
    </script>
</body>

</html>
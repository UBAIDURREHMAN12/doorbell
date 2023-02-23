{{--<h1>***Password Reset***</h1>--}}

{{--<a href="{{$link}}">Click here to reset your password</a>--}}

    <!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Favicon-->
    <link rel="icon" href="#!" style="cursor: default;" type="image/x-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700&subset=latin,cyrillic-ext" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" type="text/css">

    <!-- Bootstrap Core Css -->
    <link href="{{ asset('/assets/plugins/bootstrap/css/bootstrap.css') }}" rel="stylesheet">

    <!-- Waves Effect Css -->
    <link href="{{ asset('/assets/plugins/node-waves/waves.css') }}" rel="stylesheet" />

    <!-- Animation Css -->
    <link href="{{ asset('/assets/plugins/animate-css/animate.css') }}" rel="stylesheet" />

    <!-- Custom Css -->
    <link href="{{ asset('/assets/css/style.css') }}" rel="stylesheet">
</head>

<body>


<div class="container" style="border: 2px solid lightgray;">


    <div class="row" style="border-bottom: 2px solid lightgray;background-color: #E6E6E6;">
        <div class="col-md-12" style="text-align: center;">
            <img src="https://doorbell.ioptime.com/public/logo.png" height="50" width="70" alt="">
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <P style="font-size: 13px;padding-left: 2%;padding-top: 2%"> Hi {{$invited_person}},</P>
            <br>
            <p style="font-size: 14px;padding-left: 2%;"> {{$woner_name}} invited you as a host person for their door</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12" style="margin-top: 3%;margin-bottom: 3%;margin-left: 2%;">
            <p style="font-size: 14px;">Click on the below "download" link to download and then install "Doorbell" app
                and create your account in order to accept invitation</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12" style="margin-top: 3%;margin-bottom: 3%;margin-left: 2%;">
            <p style="font-size: 14px;">if you have already installed "Doorbell" app
                then just click on the "Accept invitation" link to accept invitation</p>
        </div>
    </div>

    <div class="row" style="border: 2px solid lightgray;">
        <div class="col-md-4" style="border: 2px solid lightgray;text-align: center;">
           <span>For Android: </span> <a href="{{$andriod_downlink}}">Download</a>
        </div>
        <div class="col-md-4" style="border: 2px solid lightgray;text-align: center;">
            <span>For IOS: </span> <a href="{{$ios_downloadlink}}">Download</a>
        </div>
        <div class="col-md-4" style="border: 2px solid lightgray;text-align: center;">
            <a href="{{$join_link}}">Accept invitation</a>
        </div>
    </div>

    <div class="row" style="border-bottom: 2px solid lightgray;">
        <div class="col-md-12" style="margin-left: 2%;">
            <p style="font-size: 8px;">
                <b>This is an automatically generated email, please do not reply ... It's basically set by the system and “responds” while “other person can't”.</b>
            </p>
        </div>
    </div>


</div>



<!-- Jquery Core Js -->
<script src="{{ asset('/assets/plugins/jquery/jquery.min.js') }}"></script>

<!-- Bootstrap Core Js -->
<script src="{{ asset('/assets/plugins/bootstrap/js/bootstrap.js') }}"></script>

<!-- Waves Effect Plugin Js -->
<script src="{{ asset('/assets/plugins/node-waves/waves.js') }}"></script>

<!-- Validation Plugin Js -->
<script src="{{ asset('/assets/plugins/jquery-validation/jquery.validate.js') }}"></script>

<!-- Custom Js -->
<script src="{{ asset('/assets/js/admin.js') }}"></script>
<script src="{{ asset('/assets/js/pages/examples/sign-in.js') }}"></script>
</body>

</html>

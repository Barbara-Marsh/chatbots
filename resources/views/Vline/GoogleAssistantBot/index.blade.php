<?php
/**
 * Created by PhpStorm.
 * User: Barbara Marsh
 * Date: 20/11/2017
 * Time: 6:24 AM
 */
?>
        <!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>V/Line Next Five Departures</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">

    <!-- Styles -->
    <link href="{{ asset('/css/vline.css') }}" rel="stylesheet">

    <link rel="shortcut icon" href="/img/google-actions/vline/favicon.ico" type="image/x-icon">
</head>
<body>
<div class="container">
    <header class="header">
        <figure class="header-figure">
            <img src="../img/google-actions/vline/vlineheader.jpg">
        </figure>
    </header>
    <div class="row">
        <div class="col-md-3">
            <figure class="logo one-to-one">
                <img src="../img/google-actions/vline/train-icon.jpg" class="auto-size">
            </figure>
        </div>
        <div class="col-md-9 text-center">
            <h1 class="margin-top-10">V/Line Next Five Departures</h1>
        </div>
        <div class="col-md-9 text-justify">
            <p>V/Line Next Five Departures is a Google Home and Google Assistant app that lets you check departure times
                for the next five trains to leave a V/Line station in regional Victoria, Australia.</p>
            <p>You can invoke this app by saying to your Google Assistant or Google Home device:</p>
            <ul>
                <li>Ok Google, talk to V/Line Next Five Departures</li>
                <li>Ok Google, open V/Line Next Five Departures</li>
                <li>Ok Google, speak to V/Line Next Five Departures</li>
            </ul>
            <p>This app was made using data provided by PTV Timetable API</p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <h2 class="text-center">List of V/Line railway stations</h2>
        </div>
    </div>
    <div class="row">
        @foreach($stations as $station)
            <div class="col-md-3 text-center">
                <p>{{ ucwords($station) }}</p>
            </div>
        @endforeach
    </div>
    <hr>
    <footer class="footer">
        <div class="row">
            <div class="col-md-6">
                <p><a href="{{ Route("privacy-policy") }}">Privacy Policy</a></p>
            </div>
            <div class="col-md-6">
                <p style="text-align: right">
                    &copy; Barbara Marsh <?php echo date('Y | '); ?>
                    <a href="mailto:barbara@bjmarsh.me">
                        <span class="glyphicon glyphicon-envelope"></span> barbara@bjmarsh.me
                    </a>
                </p>
            </div>
        </div>
    </footer>
</div>
</body>
</html>

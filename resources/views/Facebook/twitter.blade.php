<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

        <title>Barb's Twitter Trending Chatbot</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">

        <!-- Styles -->
        <link href="{{ asset('/css/twitter.css') }}" rel="stylesheet">
        <style>
            html, body {
                font-family: 'Raleway', sans-serif;
                text-align: center;
            }
            .row {
                max-width: 100%;
            }
        </style>
    </head>
    <body class="twitter">
        <div>
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <h1>Barb's Twitter Trending Chatbot</h1>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 col-md-offset-2">
                        <figure class="logo">
                            <img src="../img/facebook/barb-twitter-trending-chatbot.png">
                        </figure>
                    </div>
                    <div class="col-md-4 text-justify">
                        <p>Barb's Twitter Trending Chatbot is a Facebook Messenger chatbot that responds to an entered location name by returning the top ten Twitter trends for that location.</p>
                        <p><strong>Instructions for use:</strong></p>
                        <p>Go to the Facebook app page <a href="https://www.facebook.com/Barbs-Twitter-Trending-Chatbot-1013971122072764/" target="_blank">here.</a></p>
                        <p>Click on the 'Send Message' button.</p>
                        <p>Enter one of the locations listed below into the Messenger chat box, or 'Worldwide' to see the global Twitter trends.</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <h2>Currently available locations</h2>
                    </div>
                </div>
                <div class="row">
                    <?php $index = 0; ?>
                    @foreach($locations as $location => $id)
                        <div class="col-md-2">
                            <p>{{ ucwords($location) }}</p>
                            <?php $index++; ?>
                        </div>
                    @endforeach
                </div>
                <footer class="footer">
                    <div class="row">
                        <p>
                            &copy; Barbara Marsh <?php echo date('Y | '); ?>
                            <a href="mailto:barbara@bjmarsh.me">
                                <span class="glyphicon glyphicon-envelope"></span> barbara@bjmarsh.me
                            </a>
                        </p>
                    </div>
                </footer>
            </div>
        </div>
    </body>
</html>

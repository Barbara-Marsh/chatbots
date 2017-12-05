<?php

namespace App\Http\Controllers\Vline;

use App\Http\Controllers\Controller;
use Ixudra\Curl\Facades\Curl;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Storage;

class VlineController extends Controller
{
    protected $baseURL = "https://timetableapi.ptv.vic.gov.au";
    protected $version = "v3";
    protected $routeTypeString = "vline";
    protected $routeTypeInt = 3;
    protected $dialogflowUrl = "https://api.api.ai/v1/query?v=20150910";

    public function googleWebhook()
    {
        $input = json_decode(file_get_contents('php://input'), TRUE);
        $action = $input['result']['action'];
        $sessionId = $input['sessionId'];

        // check the Dialogflow action and perform different functions according to action
        if ($action == 'getStop') {
            $stop = $input['result']['parameters']['stop'];
            $this->getStation($stop, $sessionId);
        } else if ($action == "showStationResponse") {
            $stopName = $input['result']['parameters']['stopName'];
            $stopId = $input['result']['parameters']['stopId'];
            $this->followupResponse($stopName, $stopId, $sessionId);
        } else if ($action == 'showFinalResponse') {
            $stopName = $input['result']['parameters']['stopName'];
            //$departures = $input['result']['parameters']['departures'];
            $stopId = $input['result']['parameters']['stopId'];
            $this->getTimetable($stopName, $stopId, $sessionId);
        } else if ($action == 'repeatResponse') {
            $this->getLastResponse($sessionId);
        } else {
            $this->noPTVResponse($sessionId);
        }
    }

    // searches for railway station based on user request
    public function getStation($requestStop, $sessionId)
    {
        $requestStop = rawurlencode($requestStop);
        $ptvID = env("PTV_ID");
        $dialogflowClientKey = env('DIALOGFLOW_CLIENT_KEY');

        // search for requested station
        $searchURLEndpoint = "/$this->version/search/$requestStop?route_types=$this->routeTypeString&devid=$ptvID";
        $searchSignature = $this->makeSignature($searchURLEndpoint);
        $searchRequestURL = $this->baseURL . $searchURLEndpoint . "&signature=" . $searchSignature;
        $searchResults = $this->getFromPTV($searchRequestURL);

        $stops = $searchResults->stops;
        $stopName = "";
        $stopId = 0;

        // check if stop is a railway station
        foreach ($stops as $stop) {
            $name = $stop->stop_name;
            if (stristr($name, "Railway Station")) {
                $stopName = $name;
                $stopId = $stop->stop_id;
                break;
            }
        }

        if (!empty($stopName)) {
            $query = "I found $stopName. Is this the station you were looking for?";
            $body = [
                'speech' => $query,
                'displayText' => $query,
                'sessionId' => $sessionId,
                "followupEvent" => [
                    "name" => "show-station-response",
                    "data" => [
                        "stopName" => $stopName,
                        "stopId" => $stopId
                    ]
                ]
            ];

        } else {
            $query = "Sorry, I couldn't find that station. Please try again.";
            $body = [
                'speech' => $query,
                'displayText' => $query,
                'sessionId' => $sessionId
            ];
        }

        header('Content-Type: application/json');
        header('Authorization: Bearer ' . $dialogflowClientKey);
        echo json_encode($body);
    }

    // runs if user responds "yes" when asked if the retrieved station is correct
    public function followupResponse($stopName, $stopId, $sessionId)
    {
        $dialogflowClientKey = env('DIALOGFLOW_CLIENT_KEY');

        $body = [
            'speech' => "Follow-up response triggered.",
            'displayText' => "Follow-up response triggered.",
            'sessionId' => $sessionId,
            "followupEvent" => [
                "name" => "followup-response",
                "data" => [
                    "stopName" => $stopName,
                    "stopId" => $stopId
                ]
            ]
        ];

        header('Content-Type: application/json');
        header('Authorization: Bearer ' . $dialogflowClientKey);
        echo json_encode($body);
    }

//    public function getDepartures($stopName, $stopId, $sessionId)
//    {
//        $ptvID = env("PTV_ID");
//        $dialogflowClientKey = env('DIALOGFLOW_CLIENT_KEY');
//        $departures = [];
//
//        // search for departures from station
//        // max_results doesn't seem to be doing anything at this point and is returning all departures
//        $timetableURLEndpoint = "/$this->version/departures/route_type/$this->routeTypeInt/stop/$stopId?max_results=5&devid=$ptvID";
//        $timetableSignature = $this->makeSignature($timetableURLEndpoint);
//        $timetableRequestURL = $this->baseURL . $timetableURLEndpoint . "&signature=" . $timetableSignature;
//        $timetableResults = $this->getFromPTV($timetableRequestURL);
//        $results = array_slice($timetableResults->departures, 0, 5, TRUE);
//
//        foreach ($results as $result) {
//            $departures[] = array($result->scheduled_departure_utc, $result->route_id);
//        }
//
//        error_log("Created departures array");
//
//        $body = [
//            'speech' => "Retrieving timetable.",
//            'displayText' => "Retrieving timetable.",
//            'sessionId' => $sessionId,
//            'data' => [
//                'google' => [
//                    'expectUserResponse' => TRUE,
//                    'isSsml' => FALSE,
//                    'richResponse' => [
//                        'items' => [
//                            'simpleResponse' => [
//                                'textToSpeech' => 'Retrieving timetable',
//                                'displayText' => 'Retrieving timetable',
//                            ]
//                        ]
//                    ],
//                    'systemIntent' => [
//                        'intent' => 'actions.intent.TEXT',
//                    ]
//                ]
//            ],
//            "followupEvent" => [
//                "name" => "get-response",
//                "data" => [
//                    "stopName" => $stopName,
//                    "stopId" => $stopId,
//                    "departures" => $departures,
//                ]
//            ]
//        ];
//
//        error_log("Created response body");
//
//        header('Content-Type: application/json');
//        header('Authorization: Bearer ' . $dialogflowClientKey);
//        echo json_encode($body);
//        error_log("Message sent");
//    }

//    // search separately for route name because it isn't returned in the results for stop
//    public function getRouteName($stopName, $departures, $sessionId)
//    {
//        $speech = "The next five trains to leave $stopName are: ";
//
//        $dialogflowClientKey = env('DIALOGFLOW_CLIENT_KEY');
//        $routesList = array(
//            0 => (array('route_type' => 3, 'route_id' => 1512, 'route_name' => 'Warrnambool - Melbourne via Ararat & Hamilton', 'route_number' => '',)),
//            1 => (array('route_type' => 3, 'route_id' => 1706, 'route_name' => 'Albury - Melbourne via Seymour', 'route_number' => '',)),
//            2 => (array('route_type' => 3, 'route_id' => 1710, 'route_name' => 'Seymour - Melbourne via Broadmeadows', 'route_number' => '',)),
//            3 => (array('route_type' => 3, 'route_id' => 1717, 'route_name' => 'Batemans Bay - Melbourne via Bairnsdale', 'route_number' => '',)),
//            4 => (array('route_type' => 3, 'route_id' => 1718, 'route_name' => 'Canberra - Melbourne via Bairnsdale', 'route_number' => '',)),
//            5 => (array('route_type' => 3, 'route_id' => 1719, 'route_name' => 'Sale - Melbourne via Maffra & Traralgon', 'route_number' => '',)),
//            6 => (array('route_type' => 3, 'route_id' => 1720, 'route_name' => 'Cowes and Inverloch - Melbourne via Dandenong & Koo Wee Rup', 'route_number' => '',)),
//            7 => (array('route_type' => 3, 'route_id' => 1721, 'route_name' => 'Marlo - Lake Tyers Beach - Melbourne via Bairnsdale', 'route_number' => '',)),
//            8 => (array('route_type' => 3, 'route_id' => 1722, 'route_name' => 'Yarram - Melbourne via Koo Wee Rup & Dandenong', 'route_number' => '',)),
//            9 => (array('route_type' => 3, 'route_id' => 1723, 'route_name' => 'Griffith - Melbourne via Shepparton', 'route_number' => '',)),
//            10 => (array('route_type' => 3, 'route_id' => 1724, 'route_name' => 'Corowa - Melbourne via Rutherglen & Wangaratta', 'route_number' => '',)),
//            11 => (array('route_type' => 3, 'route_id' => 1725, 'route_name' => 'Mt Buller-Mansfield - Melbourne via Yea', 'route_number' => '',)),
//            12 => (array('route_type' => 3, 'route_id' => 1726, 'route_name' => 'Mulwala - Melbourne via Benalla & Seymour', 'route_number' => '',)),
//            13 => (array('route_type' => 3, 'route_id' => 1727, 'route_name' => 'Shepparton - Sydney via Benalla', 'route_number' => '',)),
//            14 => (array('route_type' => 3, 'route_id' => 1728, 'route_name' => 'Ballarat - Melbourne via Melton', 'route_number' => '',)),
//            15 => (array('route_type' => 3, 'route_id' => 1731, 'route_name' => 'Halls Gap - Melbourne via Stawell & Ballarat', 'route_number' => '',)),
//            16 => (array('route_type' => 3, 'route_id' => 1732, 'route_name' => 'Mount Gambier - Melbourne via Hamilton & Ballarat', 'route_number' => '',)),
//            17 => (array('route_type' => 3, 'route_id' => 1733, 'route_name' => 'Ouyen - Melbourne via Warracknabeal & Ballarat', 'route_number' => '',)),
//            18 => (array('route_type' => 3, 'route_id' => 1734, 'route_name' => 'Mildura - Ballarat via Swan Hill & Bendigo', 'route_number' => '',)),
//            19 => (array('route_type' => 3, 'route_id' => 1735, 'route_name' => 'Warrnambool - Melbourne via Ballarat', 'route_number' => '',)),
//            20 => (array('route_type' => 3, 'route_id' => 1737, 'route_name' => 'Adelaide - Melbourne via Nhill & Bendigo', 'route_number' => '',)),
//            21 => (array('route_type' => 3, 'route_id' => 1738, 'route_name' => 'Sydney - Adelaide via Albury', 'route_number' => '',)),
//            22 => (array('route_type' => 3, 'route_id' => 1740, 'route_name' => 'Bendigo - Melbourne via Gisborne', 'route_number' => '',)),
//            23 => (array('route_type' => 3, 'route_id' => 1744, 'route_name' => 'Barham - Melbourne via Bendigo', 'route_number' => '',)),
//            24 => (array('route_type' => 3, 'route_id' => 1745, 'route_name' => 'Geelong - Melbourne', 'route_number' => '',)),
//            25 => (array('route_type' => 3, 'route_id' => 1749, 'route_name' => 'Warrnambool - Melbourne via Apollo Bay & Geelong', 'route_number' => '',)),
//            26 => (array('route_type' => 3, 'route_id' => 1751, 'route_name' => 'Geelong - Bendigo via Ballarat', 'route_number' => '',)),
//            27 => (array('route_type' => 3, 'route_id' => 1755, 'route_name' => 'Adelaide - Melbourne via Horsham & Ballarat & Geelong', 'route_number' => '',)),
//            28 => (array('route_type' => 3, 'route_id' => 1756, 'route_name' => 'Casterton - Melbourne via Hamilton & Warrnambool', 'route_number' => '',)),
//            29 => (array('route_type' => 3, 'route_id' => 1758, 'route_name' => 'Barmah - Melbourne via Shepparton & Heathcote', 'route_number' => '',)),
//            30 => (array('route_type' => 3, 'route_id' => 1759, 'route_name' => 'Albury - Bendigo via Wangaratta & Shepparton', 'route_number' => '',)),
//            31 => (array('route_type' => 3, 'route_id' => 1760, 'route_name' => 'Daylesford - Melbourne via Woodend or Castlemaine', 'route_number' => '',)),
//            32 => (array('route_type' => 3, 'route_id' => 1761, 'route_name' => 'Deniliquin - Melbourne via Moama & Echuca & Heathcote', 'route_number' => '',)),
//            33 => (array('route_type' => 3, 'route_id' => 1762, 'route_name' => 'Ballarat - Warrnambool via Skipton', 'route_number' => '',)),
//            34 => (array('route_type' => 3, 'route_id' => 1767, 'route_name' => 'Mount Gambier - Melbourne via Warrnambool & Geelong', 'route_number' => '',)),
//            35 => (array('route_type' => 3, 'route_id' => 1768, 'route_name' => 'Canberra - Melbourne via Albury', 'route_number' => '',)),
//            36 => (array('route_type' => 3, 'route_id' => 1773, 'route_name' => 'Donald - Melbourne via Bendigo', 'route_number' => '',)),
//            37 => (array('route_type' => 3, 'route_id' => 1774, 'route_name' => 'Lancefield - Melbourne via Sunbury or Gisborne', 'route_number' => '',)),
//            38 => (array('route_type' => 3, 'route_id' => 1775, 'route_name' => 'Maryborough - Melbourne via Castlemaine', 'route_number' => '',)),
//            39 => (array('route_type' => 3, 'route_id' => 1776, 'route_name' => 'Mildura - Albury via Kerang & Shepparton', 'route_number' => '',)),
//            40 => (array('route_type' => 3, 'route_id' => 1782, 'route_name' => 'Mildura - Melbourne via Ballarat & Donald', 'route_number' => '',)),
//            41 => (array('route_type' => 3, 'route_id' => 1783, 'route_name' => 'Mildura - Melbourne via Swan Hill & Bendigo', 'route_number' => '',)),
//            42 => (array('route_type' => 3, 'route_id' => 1784, 'route_name' => 'Sea Lake - Melbourne via Charlton & Bendigo', 'route_number' => '',)),
//            43 => (array('route_type' => 3, 'route_id' => 1823, 'route_name' => 'Bairnsdale - Melbourne via Sale & Traralgon', 'route_number' => '',)),
//            44 => (array('route_type' => 3, 'route_id' => 1824, 'route_name' => 'Traralgon - Melbourne via Morwell & Moe & Pakenham', 'route_number' => '',)),
//            45 => (array('route_type' => 3, 'route_id' => 1837, 'route_name' => 'Ararat - Melbourne via Ballarat', 'route_number' => '',)),
//            46 => (array('route_type' => 3, 'route_id' => 1838, 'route_name' => 'Nhill - Melbourne via Ararat & Ballarat', 'route_number' => '',)),
//            47 => (array('route_type' => 3, 'route_id' => 1848, 'route_name' => 'Swan Hill - Melbourne via Bendigo', 'route_number' => '',)),
//            48 => (array('route_type' => 3, 'route_id' => 1849, 'route_name' => 'Echuca-Moama - Melbourne via Bendigo or Heathcote', 'route_number' => '',)),
//            49 => (array('route_type' => 3, 'route_id' => 1853, 'route_name' => 'Warrnambool - Melbourne via Colac & Geelong', 'route_number' => '',)),
//            50 => (array('route_type' => 3, 'route_id' => 1908, 'route_name' => 'Shepparton - Melbourne via Seymour', 'route_number' => '',)),
//            51 => (array('route_type' => 3, 'route_id' => 1912, 'route_name' => 'Mount Beauty - Melbourne via Bright', 'route_number' => '',)),
//            52 => (array('route_type' => 3, 'route_id' => 1914, 'route_name' => 'Echuca-Moama - Melbourne via Shepparton', 'route_number' => '',)),
//            53 => (array('route_type' => 3, 'route_id' => 1915, 'route_name' => 'Daylesford - Melbourne via Ballarat', 'route_number' => '',)),
//            54 => (array('route_type' => 3, 'route_id' => 4871, 'route_name' => 'Maryborough - Melbourne via Ballarat', 'route_number' => '',)),
//            55 => (array('route_type' => 3, 'route_id' => 5838, 'route_name' => 'Paynesville - Melbourne via Bairnsdale', 'route_number' => '',)),
//            56 => (array('route_type' => 3, 'route_id' => 7601, 'route_name' => 'Geelong - Colac via Winchelsea and Birregurra', 'route_number' => '',)),
//            57 => (array('route_type' => 3, 'route_id' => 11342, 'route_name' => 'Bendigo - Shepparton via Kyabram', 'route_number' => '',)),
//        );
//
//        foreach ($departures as &$departure) {
//            // format time
//            $dt = new DateTime($departure[0], new DateTimeZone('UTC'));
//            $dt->setTimezone(new DateTimeZone('Australia/Melbourne'));
//            $departure[0] = date_format($dt, "g:i a");
//
//            // search for route name based on route id
//            foreach ($routesList as $route) {
//                if ($route['route_id'] == $departure[1]) {
//                    $departure[2] = $route['route_name'];
//                    break;
//                }
//            }
//            $speech .= " The $departure[0] on the $departure[2] service. ";
//        }
//
//        $speech = str_replace(" - ", " to ", $speech);
//        $body = [
//            'speech' => $speech,
//            'displayText' => $speech,
//            'sessionId' => $sessionId,
//        ];
//
//        header('Content-Type: application/json');
//        header('Authorization: Bearer ' . $dialogflowClientKey);
//        echo json_encode($body);
//
//        Storage::put("$sessionId.txt", $speech);
//        Storage::setVisibility("$sessionId.txt", 'public');
//    }

    // get the departures from the station from the API, get the route name, then send response to Dialogflow
    public function getTimetable($stopName, $stopId, $sessionId)
    {
        $ptvID = env("PTV_ID");
        $dialogflowClientKey = env('DIALOGFLOW_CLIENT_KEY');
        $routesList = array(
            0 => (array('route_type' => 3, 'route_id' => 1512, 'route_name' => 'Warrnambool - Melbourne via Ararat & Hamilton', 'route_number' => '',)),
            1 => (array('route_type' => 3, 'route_id' => 1706, 'route_name' => 'Albury - Melbourne via Seymour', 'route_number' => '',)),
            2 => (array('route_type' => 3, 'route_id' => 1710, 'route_name' => 'Seymour - Melbourne via Broadmeadows', 'route_number' => '',)),
            3 => (array('route_type' => 3, 'route_id' => 1717, 'route_name' => 'Batemans Bay - Melbourne via Bairnsdale', 'route_number' => '',)),
            4 => (array('route_type' => 3, 'route_id' => 1718, 'route_name' => 'Canberra - Melbourne via Bairnsdale', 'route_number' => '',)),
            5 => (array('route_type' => 3, 'route_id' => 1719, 'route_name' => 'Sale - Melbourne via Maffra & Traralgon', 'route_number' => '',)),
            6 => (array('route_type' => 3, 'route_id' => 1720, 'route_name' => 'Cowes and Inverloch - Melbourne via Dandenong & Koo Wee Rup', 'route_number' => '',)),
            7 => (array('route_type' => 3, 'route_id' => 1721, 'route_name' => 'Marlo - Lake Tyers Beach - Melbourne via Bairnsdale', 'route_number' => '',)),
            8 => (array('route_type' => 3, 'route_id' => 1722, 'route_name' => 'Yarram - Melbourne via Koo Wee Rup & Dandenong', 'route_number' => '',)),
            9 => (array('route_type' => 3, 'route_id' => 1723, 'route_name' => 'Griffith - Melbourne via Shepparton', 'route_number' => '',)),
            10 => (array('route_type' => 3, 'route_id' => 1724, 'route_name' => 'Corowa - Melbourne via Rutherglen & Wangaratta', 'route_number' => '',)),
            11 => (array('route_type' => 3, 'route_id' => 1725, 'route_name' => 'Mt Buller-Mansfield - Melbourne via Yea', 'route_number' => '',)),
            12 => (array('route_type' => 3, 'route_id' => 1726, 'route_name' => 'Mulwala - Melbourne via Benalla & Seymour', 'route_number' => '',)),
            13 => (array('route_type' => 3, 'route_id' => 1727, 'route_name' => 'Shepparton - Sydney via Benalla', 'route_number' => '',)),
            14 => (array('route_type' => 3, 'route_id' => 1728, 'route_name' => 'Ballarat - Melbourne via Melton', 'route_number' => '',)),
            15 => (array('route_type' => 3, 'route_id' => 1731, 'route_name' => 'Halls Gap - Melbourne via Stawell & Ballarat', 'route_number' => '',)),
            16 => (array('route_type' => 3, 'route_id' => 1732, 'route_name' => 'Mount Gambier - Melbourne via Hamilton & Ballarat', 'route_number' => '',)),
            17 => (array('route_type' => 3, 'route_id' => 1733, 'route_name' => 'Ouyen - Melbourne via Warracknabeal & Ballarat', 'route_number' => '',)),
            18 => (array('route_type' => 3, 'route_id' => 1734, 'route_name' => 'Mildura - Ballarat via Swan Hill & Bendigo', 'route_number' => '',)),
            19 => (array('route_type' => 3, 'route_id' => 1735, 'route_name' => 'Warrnambool - Melbourne via Ballarat', 'route_number' => '',)),
            20 => (array('route_type' => 3, 'route_id' => 1737, 'route_name' => 'Adelaide - Melbourne via Nhill & Bendigo', 'route_number' => '',)),
            21 => (array('route_type' => 3, 'route_id' => 1738, 'route_name' => 'Sydney - Adelaide via Albury', 'route_number' => '',)),
            22 => (array('route_type' => 3, 'route_id' => 1740, 'route_name' => 'Bendigo - Melbourne via Gisborne', 'route_number' => '',)),
            23 => (array('route_type' => 3, 'route_id' => 1744, 'route_name' => 'Barham - Melbourne via Bendigo', 'route_number' => '',)),
            24 => (array('route_type' => 3, 'route_id' => 1745, 'route_name' => 'Geelong - Melbourne', 'route_number' => '',)),
            25 => (array('route_type' => 3, 'route_id' => 1749, 'route_name' => 'Warrnambool - Melbourne via Apollo Bay & Geelong', 'route_number' => '',)),
            26 => (array('route_type' => 3, 'route_id' => 1751, 'route_name' => 'Geelong - Bendigo via Ballarat', 'route_number' => '',)),
            27 => (array('route_type' => 3, 'route_id' => 1755, 'route_name' => 'Adelaide - Melbourne via Horsham & Ballarat & Geelong', 'route_number' => '',)),
            28 => (array('route_type' => 3, 'route_id' => 1756, 'route_name' => 'Casterton - Melbourne via Hamilton & Warrnambool', 'route_number' => '',)),
            29 => (array('route_type' => 3, 'route_id' => 1758, 'route_name' => 'Barmah - Melbourne via Shepparton & Heathcote', 'route_number' => '',)),
            30 => (array('route_type' => 3, 'route_id' => 1759, 'route_name' => 'Albury - Bendigo via Wangaratta & Shepparton', 'route_number' => '',)),
            31 => (array('route_type' => 3, 'route_id' => 1760, 'route_name' => 'Daylesford - Melbourne via Woodend or Castlemaine', 'route_number' => '',)),
            32 => (array('route_type' => 3, 'route_id' => 1761, 'route_name' => 'Deniliquin - Melbourne via Moama & Echuca & Heathcote', 'route_number' => '',)),
            33 => (array('route_type' => 3, 'route_id' => 1762, 'route_name' => 'Ballarat - Warrnambool via Skipton', 'route_number' => '',)),
            34 => (array('route_type' => 3, 'route_id' => 1767, 'route_name' => 'Mount Gambier - Melbourne via Warrnambool & Geelong', 'route_number' => '',)),
            35 => (array('route_type' => 3, 'route_id' => 1768, 'route_name' => 'Canberra - Melbourne via Albury', 'route_number' => '',)),
            36 => (array('route_type' => 3, 'route_id' => 1773, 'route_name' => 'Donald - Melbourne via Bendigo', 'route_number' => '',)),
            37 => (array('route_type' => 3, 'route_id' => 1774, 'route_name' => 'Lancefield - Melbourne via Sunbury or Gisborne', 'route_number' => '',)),
            38 => (array('route_type' => 3, 'route_id' => 1775, 'route_name' => 'Maryborough - Melbourne via Castlemaine', 'route_number' => '',)),
            39 => (array('route_type' => 3, 'route_id' => 1776, 'route_name' => 'Mildura - Albury via Kerang & Shepparton', 'route_number' => '',)),
            40 => (array('route_type' => 3, 'route_id' => 1782, 'route_name' => 'Mildura - Melbourne via Ballarat & Donald', 'route_number' => '',)),
            41 => (array('route_type' => 3, 'route_id' => 1783, 'route_name' => 'Mildura - Melbourne via Swan Hill & Bendigo', 'route_number' => '',)),
            42 => (array('route_type' => 3, 'route_id' => 1784, 'route_name' => 'Sea Lake - Melbourne via Charlton & Bendigo', 'route_number' => '',)),
            43 => (array('route_type' => 3, 'route_id' => 1823, 'route_name' => 'Bairnsdale - Melbourne via Sale & Traralgon', 'route_number' => '',)),
            44 => (array('route_type' => 3, 'route_id' => 1824, 'route_name' => 'Traralgon - Melbourne via Morwell & Moe & Pakenham', 'route_number' => '',)),
            45 => (array('route_type' => 3, 'route_id' => 1837, 'route_name' => 'Ararat - Melbourne via Ballarat', 'route_number' => '',)),
            46 => (array('route_type' => 3, 'route_id' => 1838, 'route_name' => 'Nhill - Melbourne via Ararat & Ballarat', 'route_number' => '',)),
            47 => (array('route_type' => 3, 'route_id' => 1848, 'route_name' => 'Swan Hill - Melbourne via Bendigo', 'route_number' => '',)),
            48 => (array('route_type' => 3, 'route_id' => 1849, 'route_name' => 'Echuca-Moama - Melbourne via Bendigo or Heathcote', 'route_number' => '',)),
            49 => (array('route_type' => 3, 'route_id' => 1853, 'route_name' => 'Warrnambool - Melbourne via Colac & Geelong', 'route_number' => '',)),
            50 => (array('route_type' => 3, 'route_id' => 1908, 'route_name' => 'Shepparton - Melbourne via Seymour', 'route_number' => '',)),
            51 => (array('route_type' => 3, 'route_id' => 1912, 'route_name' => 'Mount Beauty - Melbourne via Bright', 'route_number' => '',)),
            52 => (array('route_type' => 3, 'route_id' => 1914, 'route_name' => 'Echuca-Moama - Melbourne via Shepparton', 'route_number' => '',)),
            53 => (array('route_type' => 3, 'route_id' => 1915, 'route_name' => 'Daylesford - Melbourne via Ballarat', 'route_number' => '',)),
            54 => (array('route_type' => 3, 'route_id' => 4871, 'route_name' => 'Maryborough - Melbourne via Ballarat', 'route_number' => '',)),
            55 => (array('route_type' => 3, 'route_id' => 5838, 'route_name' => 'Paynesville - Melbourne via Bairnsdale', 'route_number' => '',)),
            56 => (array('route_type' => 3, 'route_id' => 7601, 'route_name' => 'Geelong - Colac via Winchelsea and Birregurra', 'route_number' => '',)),
            57 => (array('route_type' => 3, 'route_id' => 11342, 'route_name' => 'Bendigo - Shepparton via Kyabram', 'route_number' => '',)),
        );
        $departures = [];
        $speech = "The next five trains to leave $stopName are: ";

        // search for departures from station
        // max_results doesn't seem to be doing anything at this point and is returning all departures
        $timetableURLEndpoint = "/$this->version/departures/route_type/$this->routeTypeInt/stop/$stopId?max_results=5&devid=$ptvID";
        $timetableSignature = $this->makeSignature($timetableURLEndpoint);
        $timetableRequestURL = $this->baseURL . $timetableURLEndpoint . "&signature=" . $timetableSignature;
        $timetableResults = $this->getFromPTV($timetableRequestURL);
        $results = array_slice($timetableResults->departures, 0, 5, TRUE);

        foreach ($results as $result) {
            $departures[] = array($result->scheduled_departure_utc, $result->route_id);
        }

        // assign route names to departures
        foreach ($departures as &$departure) {
            // format time
            $dt = new DateTime($departure[0], new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone('Australia/Melbourne'));
            $departure[0] = date_format($dt, "g:i a");

            // search for route name based on route id
            foreach ($routesList as $route) {
                if ($route['route_id'] == $departure[1]) {
                    $departure[2] = $route['route_name'];
                    break;
                }
            }
            $speech .= " The $departure[0] on the $departure[2] service. ";
        }

        // build response to send to Dialogflow
        $speech = str_replace(" - ", " to ", $speech);
        $body = [
            'speech' => $speech,
            'displayText' => $speech,
            'sessionId' => $sessionId,
        ];

        // send response
        header('Content-Type: application/json');
        header('Authorization: Bearer ' . $dialogflowClientKey);
        echo json_encode($body);

        // store information in case user requests repeat
        Storage::put("$sessionId.txt", $speech);
        Storage::setVisibility("$sessionId.txt", 'public');
    }

    // re-displays previous response if user says "repeat"
    public function getLastResponse($sessionId)
    {
        $dialogflowClientKey = env('DIALOGFLOW_CLIENT_KEY');
        $speech = Storage::get("$sessionId.txt");

        $body = [
            'speech' => $speech,
            'displayText' => $speech,
            'sessionId' => $sessionId,
        ];

        header('Content-Type: application/json');
        header('Authorization: Bearer ' . $dialogflowClientKey);
        echo json_encode($body);
    }

    // default response
    public function noPTVResponse($sessionId)
    {
        $dialogflowClientKey = env('DIALOGFLOW_CLIENT_KEY');

        $body = [
            'speech' => "Sorry, there was an error connecting to the P T V service. Please try again.",
            'displayText' => "Sorry, there was an error connecting to the PTV service. Please try again.",
            'sessionId' => $sessionId,
        ];

        header('Content-Type: application/json');
        header('Authorization: Bearer ' . $dialogflowClientKey);
        echo json_encode($body);
    }

    // get all stops with railway station in name and display index page
    public function getAllStations()
    {
        $ptvID = env("PTV_ID");
        $searchStations = rawurlencode("railway station");

        $endpoint = "/$this->version/search/$searchStations?route_types=$this->routeTypeString&devid=$ptvID";
        $searchSignature = $this->makeSignature($endpoint);
        $searchRequestURL = $this->baseURL . $endpoint . "&signature=" . $searchSignature;
        $searchResults = $this->getFromPTV($searchRequestURL);
        $stationsObject = $searchResults->stops;
        $stations = [];

        foreach ($stationsObject as $station) {
            $stations[] = str_replace(' Railway Station', '', $station->stop_name);
        }

        return view('Vline.GoogleAssistantBot.index')->with(['stations' => $stations]);
    }

    // retrieve data from PTV API
    public function getFromPTV($url)
    {
        $result = Curl::to($url)
            ->get();

        return json_decode($result);
    }

    // create signature for PTV API request
    public function makeSignature($endpoint)
    {
        $ptvKey = env("PTV_KEY");
        $hash = hash_hmac('sha1', $endpoint, $ptvKey);
        $signature = strtoupper($hash);

        return $signature;
    }

    // sanitise received text
    public function sanitise($text)
    {
        return trim(strtolower($text));
    }

    // temp function to create route list
    public function getAllRoutes()
    {
        $ptvID = env("PTV_ID");

        $routesArray = [];

        $endpoint = "/$this->version/routes?route_types=$this->routeTypeInt&devid=$ptvID";
        $searchSignature = $this->makeSignature($endpoint);
        $searchRequestURL = $this->baseURL . $endpoint . "&signature=" . $searchSignature;
        $searchResults = $this->getFromPTV($searchRequestURL);

        $routes = $searchResults->routes;

        foreach ($routes as $route) {
            $routeArray = [];
            $routeArray[] = $route->route_id;
            $routeArray[] = $route->route_name;
            $routesArray[] = $routeArray;
        }

        var_export($routes);
    }
}

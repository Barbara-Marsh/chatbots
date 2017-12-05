<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use pimax\FbBotApp;
use pimax\Messages\Message;
use Twitter;

class MessengerController extends Controller
{
    public function webhook()
    {
        $local_verify_token = env("FB_TWITTER_WEBHOOK_VERIFY_TOKEN");
        $hub_verify_token = Input::get('hub_verify_token');
        error_log($local_verify_token);
        error_log($hub_verify_token);

        if ($local_verify_token == $hub_verify_token) {
            return Input::get("hub_challenge");
        } else {
            return "Bad verify token";
        }
    }

    public function webhook_post()
    {
        $locations = ['worldwide' => 1, 'winnipeg' => 2972, 'ottawa' => 3369, 'quebec' => 3444, 'montreal' => 3534, 'toronto' => 4118, 'edmonton' => 8676, 'calgary' => 8775, 'vancouver' => 9807, 'birmingham' => 12723, 'blackpool' => 12903, 'bournemouth' => 13383, 'brighton' => 13911, 'bristol' => 13963, 'cardiff' => 15127, 'coventry' => 17044, 'derby' => 18114, 'edinburgh' => 19344, 'glasgow' => 21125, 'hull' => 25211, 'leeds' => 26042, 'leicester' => 26062, 'liverpool' => 26734, 'manchester' => 28218, 'middlesbrough' => 28869, 'newcastle' => 30079, 'nottingham' => 30720, 'plymouth' => 32185, 'portsmouth' => 32452, 'preston' => 32566, 'sheffield' => 34503, 'stoke-on-trent' => 36240, 'swansea' => 36758, 'london' => 44418, 'belfast' => 44544, 'santo domingo' => 76456, 'guatemala city' => 83123, 'acapulco' => 110978, 'aguascalientes' => 111579, 'chihuahua' => 115958, 'mexico city' => 116545, 'ciudad juarez' => 116556, 'nezahualcóyotl' => 116564, 'culiacán' => 117994, 'ecatepec de morelos' => 118466, 'guadalajara' => 124162, 'hermosillo' => 124785, 'león' => 131068, 'mérida' => 133327, 'mexicali' => 133475, 'monterrey' => 134047, 'morelia' => 134091, 'naucalpan de juárez' => 134395, 'puebla' => 137612, 'querétaro' => 138045, 'saltillo' => 141272, 'san luis potosí' => 144265, 'tijuana' => 149361, 'toluca' => 149769, 'zapopan' => 151582, 'mendoza' => 332471, 'santiago' => 349859, 'concepcion' => 349860, 'valparaiso' => 349861, 'bogotá' => 368148, 'cali' => 368149, 'medellín' => 368150, 'barranquilla' => 368151, 'quito' => 375732, 'guayaquil' => 375733, 'caracas' => 395269, 'maracaibo' => 395270, 'maracay' => 395271, 'valencia' => 395272, 'barcelona' => 395273, 'ciudad guayana' => 395275, 'turmero' => 395277, 'lima' => 418440, 'brasília' => 455819, 'belém' => 455820, 'belo horizonte' => 455821, 'curitiba' => 455822, 'porto alegre' => 455823, 'recife' => 455824, 'rio de janeiro' => 455825, 'salvador' => 455826, 'são paulo' => 455827, 'campinas' => 455828, 'fortaleza' => 455830, 'goiânia' => 455831, 'manaus' => 455833, 'são luís' => 455834, 'guarulhos' => 455867, 'córdoba' => 466861, 'rosario' => 466862, 'barquisimeto' => 468382, 'maturín' => 468384, 'buenos aires' => 468739, 'gdańsk' => 493417, 'kraków' => 502075, 'lodz' => 505120, 'poznań' => 514048, 'warsaw' => 523920, 'wroclaw' => 526363, 'vienna' => 551801, 'cork' => 560472, 'dublin' => 560743, 'galway' => 560912, 'bordeaux' => 580778, 'lille' => 608105, 'lyon' => 609125, 'marseille' => 610264, 'montpellier' => 612977, 'nantes' => 613858, 'paris' => 615702, 'rennes' => 619163, 'strasbourg' => 627791, 'toulouse' => 628886, 'berlin' => 638242, 'bremen' => 641142, 'dortmund' => 645458, 'dresden' => 645686, 'dusseldorf' => 646099, 'essen' => 648820, 'frankfurt' => 650272, 'hamburg' => 656958, 'cologne' => 667931, 'leipzig' => 671072, 'munich' => 676757, 'stuttgart' => 698064, 'bologna' => 711080, 'genoa' => 716085, 'milan' => 718345, 'naples' => 719258, 'palermo' => 719846, 'rome' => 721943, 'turin' => 725003, 'den haag' => 726874, 'amsterdam' => 727232, 'rotterdam' => 733075, 'utrecht' => 734047, 'barcelona' => 753692, 'bilbao' => 754542, 'las palmas' => 764814, 'madrid' => 766273, 'malaga' => 766356, 'murcia' => 768026, 'palma' => 769293, 'seville' => 774508, 'valencia' => 776688, 'zaragoza' => 779063, 'geneva' => 782538, 'lausanne' => 783058, 'zurich' => 784794, 'brest' => 824382, 'grodno' => 825848, 'gomel' => 825978, 'minsk' => 834463, 'riga' => 854823, 'bergen' => 857105, 'oslo' => 862592, 'gothenburg' => 890869, 'stockholm' => 906057, 'dnipropetrovsk' => 918981, 'donetsk' => 919163, 'kharkiv' => 922137, 'kyiv' => 924938, 'lviv' => 924943, 'odesa' => 929398, 'zaporozhye' => 939628, 'athens' => 946738, 'thessaloniki' => 963291, 'bekasi' => 1030077, 'depok' => 1032539, 'pekanbaru' => 1040779, 'surabaya' => 1044316, 'makassar' => 1046138, 'bandung' => 1047180, 'jakarta' => 1047378, 'medan' => 1047908, 'palembang' => 1048059, 'semarang' => 1048324, 'tangerang' => 1048536, 'singapore' => 1062617, 'perth' => 1098081, 'adelaide' => 1099805, 'brisbane' => 1100661, 'canberra' => 1100968, 'darwin' => 1101597, 'melbourne' => 1103816, 'sydney' => 1105779, 'kitakyushu' => 1110809, 'saitama' => 1116753, 'chiba' => 1117034, 'fukuoka' => 1117099, 'hamamatsu' => 1117155, 'hiroshima' => 1117227, 'kawasaki' => 1117502, 'kobe' => 1117545, 'kumamoto' => 1117605, 'nagoya' => 1117817, 'niigata' => 1117881, 'sagamihara' => 1118072, 'sapporo' => 1118108, 'sendai' => 1118129, 'takamatsu' => 1118285, 'tokyo' => 1118370, 'yokohama' => 1118550, 'goyang' => 1130853, 'yongin' => 1132094, 'ansan' => 1132444, 'bucheon' => 1132445, 'busan' => 1132447, 'changwon' => 1132449, 'daegu' => 1132466, 'gwangju' => 1132481, 'incheon' => 1132496, 'seongnam' => 1132559, 'suwon' => 1132567, 'ulsan' => 1132578, 'seoul' => 1132599, 'kajang' => 1141268, 'ipoh' => 1154679, 'johor bahru' => 1154698, 'klang' => 1154726, 'kuala lumpur' => 1154781, 'calocan' => 1167715, 'makati' => 1180689, 'pasig' => 1187115, 'taguig' => 1195098, 'antipolo' => 1198785, 'cagayan de oro' => 1199002, 'cebu city' => 1199079, 'davao city' => 1199136, 'manila' => 1199477, 'quezon city' => 1199682, 'zamboanga city' => 1199980, 'bangkok' => 1225448, 'hanoi' => 1236594, 'hai phong' => 1236690, 'can tho' => 1252351, 'da nang' => 1252376, 'ho chi minh city' => 1252431, 'algiers' => 1253079, 'accra' => 1326075, 'kumasi' => 1330595, 'benin city' => 1387660, 'ibadan' => 1393672, 'kaduna' => 1396439, 'kano' => 1396803, 'lagos' => 1398823, 'port harcourt' => 1404447, 'giza' => 1521643, 'cairo' => 1521894, 'alexandria' => 1522006, 'mombasa' => 1528335, 'nairobi' => 1528488, 'durban' => 1580913, 'johannesburg' => 1582504, 'port elizabeth' => 1586614, 'pretoria' => 1586638, 'soweto' => 1587677, 'cape town' => 1591691, 'medina' => 1937801, 'dammam' => 1939574, 'riyadh' => 1939753, 'jeddah' => 1939873, 'mecca' => 1939897, 'sharjah' => 1940119, 'abu dhabi' => 1940330, 'dubai' => 1940345, 'haifa' => 1967449, 'tel aviv' => 1968212, 'jerusalem' => 1968222, 'amman' => 1968902, 'chelyabinsk' => 1997422, 'khabarovsk' => 2018708, 'krasnodar' => 2028717, 'krasnoyarsk' => 2029043, 'samara' => 2077746, 'voronezh' => 2108210, 'yekaterinburg' => 2112237, 'irkutsk' => 2121040, 'kazan' => 2121267, 'moscow' => 2122265, 'nizhny novgorod' => 2122471, 'novosibirsk' => 2122541, 'omsk' => 2122641, 'perm' => 2122814, 'rostov-on-don' => 2123177, 'saint petersburg' => 2123260, 'ufa' => 2124045, 'vladivostok' => 2124288, 'volgograd' => 2124298, 'karachi' => 2211096, 'lahore' => 2211177, 'multan' => 2211269, 'rawalpindi' => 2211387, 'faisalabad' => 2211574, 'muscat' => 2268284, 'nagpur' => 2282863, 'lucknow' => 2295377, 'kanpur' => 2295378, 'patna' => 2295381, 'ranchi' => 2295383, 'kolkata' => 2295386, 'srinagar' => 2295387, 'amritsar' => 2295388, 'jaipur' => 2295401, 'ahmedabad' => 2295402, 'rajkot' => 2295404, 'surat' => 2295405, 'bhopal' => 2295407, 'indore' => 2295408, 'thane' => 2295410, 'mumbai' => 2295411, 'pune' => 2295412, 'hyderabad' => 2295414, 'bangalore' => 2295420, 'chennai' => 2295424, 'mersin' => 2323778, 'adana' => 2343678, 'ankara' => 2343732, 'antalya' => 2343733, 'bursa' => 2343843, 'diyarbakır' => 2343932, 'eskişehir' => 2343980, 'gaziantep' => 2343999, 'istanbul' => 2344116, 'izmir' => 2344117, 'kayseri' => 2344174, 'konya' => 2344210, 'okinawa' => 2345896, 'daejeon' => 2345975, 'auckland' => 2348079, 'albuquerque' => 2352824, 'atlanta' => 2357024, 'austin' => 2357536, 'baltimore' => 2358820, 'baton rouge' => 2359991, 'birmingham' => 2364559, 'boston' => 2367105, 'charlotte' => 2378426, 'chicago' => 2379574, 'cincinnati' => 2380358, 'cleveland' => 2381475, 'colorado springs' => 2383489, 'columbus' => 2383660, 'dallas-ft. worth' => 2388929, 'denver' => 2391279, 'detroit' => 2391585, 'el paso' => 2397816, 'fresno' => 2407517, 'greensboro' => 2414469, 'harrisburg' => 2418046, 'honolulu' => 2423945, 'houston' => 2424766, 'indianapolis' => 2427032, 'jackson' => 2428184, 'jacksonville' => 2428344, 'kansas city' => 2430683, 'las vegas' => 2436704, 'long beach' => 2441472, 'los angeles' => 2442047, 'louisville' => 2442327, 'memphis' => 2449323, 'mesa' => 2449808, 'miami' => 2450022, 'milwaukee' => 2451822, 'minneapolis' => 2452078, 'nashville' => 2457170, 'new haven' => 2458410, 'new orleans' => 2458833, 'new york' => 2459115, 'norfolk' => 2460389, 'oklahoma city' => 2464592, 'omaha' => 2465512, 'orlando' => 2466256, 'philadelphia' => 2471217, 'phoenix' => 2471390, 'pittsburgh' => 2473224, 'portland' => 2475687, 'providence' => 2477058, 'raleigh' => 2478307, 'richmond' => 2480894, 'sacramento' => 2486340, 'st. louis' => 2486982, 'salt lake city' => 2487610, 'san antonio' => 2487796, 'san diego' => 2487889, 'san francisco' => 2487956, 'san jose' => 2488042, 'seattle' => 2490383, 'tallahassee' => 2503713, 'tampa' => 2503863, 'tucson' => 2508428, 'virginia beach' => 2512636, 'washington' => 2514815, 'osaka' => 15015370, 'kyoto' => 15015372, 'delhi' => 20070458, 'united arab emirates' => 23424738, 'algeria' => 23424740, 'argentina' => 23424747, 'australia' => 23424748, 'austria' => 23424750, 'bahrain' => 23424753, 'belgium' => 23424757, 'belarus' => 23424765, 'brazil' => 23424768, 'canada' => 23424775, 'chile' => 23424782, 'colombia' => 23424787, 'denmark' => 23424796, 'dominican republic' => 23424800, 'ecuador' => 23424801, 'egypt' => 23424802, 'ireland' => 23424803, 'france' => 23424819, 'ghana' => 23424824, 'germany' => 23424829, 'greece' => 23424833, 'guatemala' => 23424834, 'indonesia' => 23424846, 'india' => 23424848, 'israel' => 23424852, 'italy' => 23424853, 'japan' => 23424856, 'jordan' => 23424860, 'kenya' => 23424863, 'korea' => 23424868, 'kuwait' => 23424870, 'lebanon' => 23424873, 'latvia' => 23424874, 'oman' => 23424898, 'mexico' => 23424900, 'malaysia' => 23424901, 'nigeria' => 23424908, 'netherlands' => 23424909, 'norway' => 23424910, 'new zealand' => 23424916, 'peru' => 23424919, 'pakistan' => 23424922, 'poland' => 23424923, 'panama' => 23424924, 'portugal' => 23424925, 'qatar' => 23424930, 'philippines' => 23424934, 'puerto rico' => 23424935, 'russia' => 23424936, 'saudi arabia' => 23424938, 'south africa' => 23424942, 'singapore' => 23424948, 'spain' => 23424950, 'sweden' => 23424954, 'switzerland' => 23424957, 'thailand' => 23424960, 'turkey' => 23424969, 'united kingdom' => 23424975, 'ukraine' => 23424976, 'united states' => 23424977, 'venezuela' => 23424982, 'vietnam' => 23424984, 'petaling' => 56013632, 'hulu langat' => 56013645, 'ahsa' => 56120136, 'okayama' => 90036018, ];

        // get message input
        $input = Input::all();
        $recipient = $input['entry'][0]['messaging'][0]['sender']['id'];
        $input_text = strtolower($input['entry'][0]['messaging'][0]['message']['text']);

        // create bot instance
        $token = env('FB_TWITTER_PAGE_ACCESS_TOKEN');
        $bot = new FbBotApp($token);

        // handle input
        if (in_array($input_text, ['help', 'i need help', 'help me'])) {
            $text = "Enter a city or location to find out the top ten tags trending on Twitter, or enter 'worldwide'.";
        }
        elseif (in_array($input_text, ['hi', 'hello'])) {
            $text = "Hey there!";
        }
        elseif (array_key_exists($input_text, $locations)) {
            $trends = Twitter::getTrendsPlace(['id' => $locations[$input_text]]);
            $text = 'Trends in ' . $trends[0]->locations[0]->name . ':';
            foreach ($trends[0]->trends as $index => $trend) {
                if ($index > 9) {
                    break;
                }
                $text .= "\n" . ($index+1) . ". " . $trend->name;
            }
        }
        else {
            $text = "Sorry, that location is not available. Please enter another location, or 'worldwide'.";
        }

        // send message
        $message = new Message($recipient, $text);
        $bot->send($message);
    }
}

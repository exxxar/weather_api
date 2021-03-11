<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use KubAT\PhpSimple\HtmlDomParser;

class testApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:gis';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function prepare($str)
    {
        $string = trim(strip_tags($str));

        return $string;
    }

    public function parser($dom, $day_index)
    {

        $time_sections = ["00:00", "03:00", "06:00", "09:00", "12:00", "15:00", "18:00", "21:00"];


        $table = $dom->find('.archive-table-wrap')[0];
        $day_part_index = 0;

        $month = [];
        $day = [];


        foreach ($table->find('tr') as $tr) {

            $elements = $tr->find('td');

            if ($day_index == 0) {
                $day_index++;
                continue;
            }


            if ($day_part_index <= 7) {

                $tmp_visibility = explode(" ",$this->prepare($elements[2]->innertext ?? ''));

                $object = (object)[
                    "wind" => (object)[
                        "type" => $this->prepare($elements[0]->innertext ?? ''),
                        "speed" => $this->prepare($elements[1]->innertext ?? '')
                    ],
                    "visibility" => [
                        "type" => $tmp_visibility[1] ?? 'км',
                        "value" => $tmp_visibility[0] ?? 0,
                    ],

                    "weather_condition" => $this->prepare($elements[3]->innertext ?? ''),
                    "cloudy" => $this->prepare($elements[4]->innertext ?? ''),
                    "relative_humidity" => $this->prepare($elements[7]->innertext ?? ''),
                    "temperature" => (object)[
                        "air" => $this->prepare($elements[5]->innertext ?? ''),
                        "dew_point" => $this->prepare($elements[6]->innertext ?? ''),
                        "effective_in_shade" => $this->prepare($elements[8]->innertext ?? ''),
                        "effective_on_sun" => $this->prepare($elements[9]->innertext ?? ''),
                        "min" => $this->prepare($elements[13]->innertext ?? ''),
                        "max" => $this->prepare($elements[14]->innertext ?? ''),
                    ],
                    "comfort" => $this->prepare($elements[10]->innertext ?? ''),
                    "pressure" => (object)[
                        "sea_level_atmospheric" => $this->prepare($elements[11]->innertext ?? ''),
                        "meteorological_station_level_atmospheric" => $this->prepare($elements[12]->innertext ?? ''),
                    ],
                    "precipitation" => (object)[
                        "r" => $this->prepare($elements[15]->innertext ?? ''),
                        "r24" => $this->prepare($elements[16]->innertext ?? ''),
                        "s" => $this->prepare($elements[17]->innertext ?? ''),
                    ]

                ];

                array_push($day, (object)[
                    "time" => $time_sections[$day_part_index],
                    "section" => $object
                ]);

                $day_part_index++;

                Log::info("$day_index $day_part_index");

                if ($day_part_index == 8) {

                    array_push($month, (object)[
                        "index" => $day_index,
                        "day" => $day
                    ]);


                    $day = [];
                    $day_part_index = 0;
                    $day_index++;
                }

            }


        }

    }

    public function handle()
    {

        ini_set('memory_limit', '-1');

        /*  $dom = HtmlDomParser::file_get_html("http://www.pogodaiklimat.ru/weather.php?id=27962&bday=1&fday=15&amonth=1&ayear=2021&bot=2", false, null, 0);


          $this->parser($dom, 0);*/

        /*        $dom = HtmlDomParser::file_get_html("http://www.pogodaiklimat.ru/weather.php?id=27962&bday=10&fday=20&amonth=1&ayear=2021&bot=2", false, null, 0);

                $this->parser($dom, 11);*/

        $dom = HtmlDomParser::file_get_html("http://www.pogodaiklimat.ru/weather.php?id=27962&bday=1&fday=31&amonth=1&ayear=2021&bot=2", false, null, 0);

        $this->parser($dom, 0);


        //Log::info(print_r($month, true));

        return 0;
    }
}

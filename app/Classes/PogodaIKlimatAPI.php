<?php


namespace App\Classes;


use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use KubAT\PhpSimple\HtmlDomParser;

class PogodaIKlimatAPI
{
    private function prepare($str)
    {
        return trim(strip_tags($str));
    }

    private function parser($dom, $day_index)
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

                $tmp_visibility = explode(" ", $this->prepare($elements[2]->innertext ?? ''));

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

        return $month;

    }

    //27962
    public function oneMonthAPI($region, $year, $month)
    {
        $path = "result-pogodaiklimat-$region-$year-$month.json";


        if (Storage::exists($path)) {
            $tmp_array = json_decode(Storage::get($path));

            $tmp_array = array_filter($tmp_array, function ($item) {
                return ((object)$item)->index == Carbon::now()->subDay()->day;
            });

            if (count($tmp_array) > 0) {
                return json_decode(Storage::get($path));
            }
        }


        $daysInMonth = Carbon::create($year, $month)->daysInMonth;

        $dom = HtmlDomParser::file_get_html("http://www.pogodaiklimat.ru/weather.php?id=$region&bday=1&fday=$daysInMonth&amonth=$month&ayear=$year&bot=2", false, null, 0);


        $month = $this->parser($dom, 0);

        Storage::put($path, json_encode($month));

        return $month;

    }
}

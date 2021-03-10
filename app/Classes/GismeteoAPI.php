<?php

namespace App\Classes;


use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use KubAT\PhpSimple\HtmlDomParser;


class GismeteoAPI
{

    const PRECIPITATION = [
        "rain" => "Дождь",
        "snow" => "Снег",
        "storm" => "Ветер",
        "normal" => "Нормально",
    ];

    const WIND = [
        "w0" => "Ш",//штиль
        "w2" => "В",//в
        "w3" => "ЮВ",//юв
        "w4" => "Ю",//ю
        "w5" => "С",//с
        "w6" => "З",//з
        "w7" => "СЗ",//сз
    ];

    const CLOUDY = [
        "dull" => "Пасмурно",
        "sun" => "Солнечно",
        "sunc" => "Малооблочно",
        "suncl" => "Облачно"
    ];

    public function getWindDictionary()
    {
        return (array)self::WIND;
    }

    public function getCloudyDictionary()
    {
        return (array)self::CLOUDY;
    }

    public function getPrecipitationDictionary()
    {
        return (array)self::PRECIPITATION;
    }


    private function getPrecipitationTypeByString($str)
    {

        foreach (self::PRECIPITATION as $key => $value) {

            if (mb_strpos($str, $key . ".png") > 0) {
                return $key;
            }
        }

        return "normal";
    }

    private function getWindDirTypeByString($str)
    {

        foreach (self::WIND as $key => $value) {
            if (mb_strpos($str, $key . ".gif") > 0) {
                return $key;
            }
        }

        return "w0";//штиль
    }

    private function getCloudyTypeByString($str)
    {

        foreach (self::CLOUDY as $key => $value) {
            if (mb_strpos($str, $key . ".png") > 0) {

                return $key;
            }
        }
    }

    public function fieldProccessor($index, $str)
    {
        //Log::info($index . " " . $str);

        if (mb_strpos("$str", "still.gif") > 0)
            return "";

        switch ($index) {
            case 4:
            case 9:
                return $this->getCloudyTypeByString($str);
                break;
            case 6:
            case 11:

                try {
                    preg_match_all("|([0-9]+)м.с|U",
                        $str,
                        $out, PREG_PATTERN_ORDER);

                    $tmp_speed = $out[1][0];

                } catch (\Exception $ex) {
                    $tmp_speed = "0";
                }
                //  Log::info(print_r($out,true));
                return (object)[
                    "type" => $this->getWindDirTypeByString($str),
                    "speed" => $tmp_speed
                ];

            case 5:
            case 10:
                return $this->getPrecipitationTypeByString($str);

            default:
                return $str;
        }
    }

    //$region = 4445
    public function oneMonthAPI($region, $year, $month)
    {
        $path = "result-$region-$year-$month.json";


        if (Storage::exists($path)) {
            $tmp_array = json_decode(Storage::get($path));

            $tmp_array = array_filter($tmp_array, function ($item) {
                return ((object)$item)->index == Carbon::now()->subDay()->day;
            });

            if (count($tmp_array) > 0) {
                return json_decode(Storage::get($path));
            }
        }

        $dom = HtmlDomParser::file_get_html("https://www.gismeteo.ru/diary/$region/$year/$month/", false, null, 0);

        $global_tmp = [];
        $tmp = [];
        $index = 1;
        foreach ($dom->find('td') as $element) {

            if ($index % 11 != 0) {
                array_push($tmp, $this->fieldProccessor($index, $element->innertext));
            } else {
                array_push($tmp, $this->fieldProccessor($index, $element->innertext));
                array_push($global_tmp, $tmp);
                $tmp = [];
                $index = 0;
            }

            $index++;
        }

        $jsonResult = [];
        foreach ($global_tmp as $item) {
            array_push($jsonResult, (object)[
                "index" => $item[0],
                "day" => (object)[
                    "temperature" => $item[1],
                    "pressure" => $item[2],
                    "cloudy" => $item[3],
                    "weather" => $item[4],
                    "wind" => $item[5],
                ],
                "night" => (object)[
                    "temperature" => $item[6],
                    "pressure" => $item[7],
                    "cloudy" => $item[8],
                    "weather" => $item[9],
                    "wind" => $item[10],
                ]
            ]
            );


        }

        Storage::put($path, json_encode($jsonResult));

        return $jsonResult;
    }


}

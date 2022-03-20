<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use KubAT\PhpSimple\HtmlDomParser;

class WeatherTestCommand extends Command
{

    const CLOUDY = [
        "sun" => "Солнечно",
        "sunc" => "Малооблачно",
        "suncl" => "Облачно",
        "dull" => "Пасмурно",

    ];

    const WEATHER = [
        "rain" => "Дождь",
        "snow" => "Снег",
        "storm" => "Шторм"
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

    private function getWindType($text)
    {

        foreach (self::WIND as $key => $value) {
            if (strpos($text, $key . ".gif") !== false) {
                return $value;
            }
        }

        return "Ш";
    }

    private function getWeatherType($text)
    {

        foreach (self::WEATHER as $key => $value) {
            if (strpos($text, $key . ".png") !== false) {
                return $value;
            }
        }

        return "Штиль";
    }

    private function getCloudyType($text)
    {

        foreach (self::CLOUDY as $key => $value) {
            if (strpos($text, $key . ".png") !== false) {
                return $value;
            }
        }

        return "Конец света";
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Парсер погоды с gismeteo';

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
    public function handle()
    {


        Log::info("command work!");

        ini_set('memory_limit', '-1');

        $dom = HtmlDomParser::file_get_html("https://www.gismeteo.ru/diary/14406/2012/3/", false, null, 0);


        $row = [];
        $days = [];
        $index = 1;

        foreach ($dom->find('td') as $element) {

            if ($index < 11) {

                switch ($index) {
                    case 6:

                        array_push($row, $this->getWindType($element->innertext));
                        break;
                    case 9:
                    case 4:
                        array_push($row, $this->getCloudyType($element->innertext));
                        break;
                    case 5:
                    case 10:
                        array_push($row, $this->getWeatherType($element->innertext));
                        break;
                    default:
                        array_push($row, $element->innertext);
                }
            } else {
                array_push($row, $this->getWindType($element->innertext));
                $index = 0;
                array_push($days, [
                    "index" => $row[0],
                    "day" => (object)[
                        "temperature" => $row[1],
                        "pressure" => $row[2],
                        "cloudy" => $row[3],
                        "weather" => $row[4],
                        "wind" => $row[5],
                    ],
                    "night" => (object)[
                        "temperature" => $row[6],
                        "pressure" => $row[7],
                        "cloudy" => $row[8],
                        "weather" => $row[9],
                        "wind" => $row[10],
                    ]
                ]);
                $row = [];
            }
            $index++;

        }
        Log::info(print_r($days, true));
        return 0;
    }
}

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
                        "type" => $this->prepare($elements[0]->innertext ?? 'СВ'),
                        "speed" => $this->prepare($elements[1]->innertext ?? '0')
                    ],
                    "visibility" => (object)[
                        "type" => $tmp_visibility[1] ?? 'км',
                        "value" => $tmp_visibility[0] ?? '0',
                    ],

                    "weather_condition" => $this->prepare($elements[3]->innertext ?? ''),
                    "cloudy" => $this->prepare($elements[4]->innertext ?? ''),
                    "relative_humidity" => $this->prepare($elements[7]->innertext ?? '0'),
                    "temperature" => (object)[
                        "air" => $this->prepare($elements[5]->innertext ?? '0'),
                        "dew_point" => $this->prepare($elements[6]->innertext ?? '0'),
                        "effective_in_shade" => $this->prepare($elements[8]->innertext ?? '0'),
                        "effective_on_sun" => $this->prepare($elements[9]->innertext ?? '0'),
                        "min" => $this->prepare($elements[13]->innertext ?? '0'),
                        "max" => $this->prepare($elements[14]->innertext ?? '0'),
                    ],
                    "comfort" => $this->prepare($elements[10]->innertext ?? '0'),
                    "pressure" => (object)[
                        "sea_level_atmospheric" => $this->prepare($elements[11]->innertext ?? '0'),
                        "meteorological_station_level_atmospheric" => $this->prepare($elements[12]->innertext ?? '0'),
                    ],
                    "precipitation" => (object)[
                        "r" => $this->prepare($elements[15]->innertext ?? '0'),
                        "r24" => $this->prepare($elements[16]->innertext ?? '0'),
                        "s" => $this->prepare($elements[17]->innertext ?? '0'),
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

    public function getFullDescription()
    {
        return (object)[
            "wind" => (object)[
                "type" => "Направление ветра",
                "speed" => "м\с",
                "full_description" => "Ветер - указаны скорость ветра в м/с - средняя за 10 мин, порывы в срок и между сроками (в фигурных скобках) и направление, откуда дует ветер: С - северный,
СВ - северо-восточный, В - восточный, ЮВ - юго-восточный, Ю - южный, ЮЗ - юго-западный, З - западный, СЗ - северо-западный."
            ],
            "visibility" => (object)[
                "type" => "Размерность расстояния видимости",
                "value" => "Значение расстояния видимости",
                "full_description" => "Видимость - горизонтальная дальность видимости в метрах или километрах. При видимости от 1 до 10 км при отсутствии осадков обычно наблюдается дымка, при ухудшении видимости до 1 км и менее - туман. В сухую погоду видимость может ухудшаться дымом, пылью или мглою."
            ],

            "weather_condition" => "Явления  - указаны атмосферные явления, наблюдавшиеся в срок или в последний час перед сроком; фигурными скобками обозначены явления, наблюдавшиеся между сроками (за 1-3 часа до срока); квадратными скобками обозначены град или гололедные отложения с указанием их диаметра в мм."
            ,
            "cloudy" => "Облачность - указаны через наклонную черту общая и нижняя облачность в баллах и высота нижней границы облаков в метрах; квадратными скобками обозначены формы облаков: Ci - перистые, Cs - перисто-слоистые, Cc - перисто-кучевые, Ac - высококучевые, As - высокослоистые, Sc - слоисто-кучевые, Ns - слоисто-дождевые, Cu - кучевые, Cb - кучево-дождевые."

            ,
            "relative_humidity" => "%, Относительная влажность воздуха - влажноcть воздуха, измеренная на высоте 2 м над землей."
            ,
            "temperature" => (object)[
                "air" => "С, Температура воздуха - температура, измеренная на высоте 2 м над землей."
                ,
                "dew_point" => "С, Температура точки росы - температура, при понижении до которой содержащийся в воздухе водяной пар достигнет насыщения."
                ,
                "effective_in_shade" => "С, Эффективная температура - температура, которую ощущает одетый по сезону человек в тени. Характеристика душности погоды. При расчете учитывается влияние влажности воздуха и скорости ветра на теплоощущения человека."
                ,
                "effective_on_sun" => "С, Эффективная температура на солнце - температура, которую ощущает человек, с поправкой на солнечный нагрев. Характеристика знойности погоды. Зависит от высоты солнца над горизонтом, облачности и скорости ветра. Ночью, в пасмурную погоду, а также при ветре 12 м/с и более поправка равна нулю."
                ,
                "min" => "С, Минимальная температура - минимум температуры воздуха на высоте 2 м над землей."
                ,
                "max" => "С, Максимальная температура - максимум температуры воздуха на высоте 2 м над землей."
                ,
            ],
            "comfort" => "Комфортность для человека"
            ,
            "pressure" => (object)[
                "sea_level_atmospheric" => "гПа, Атмосферное давление - приведенное к уровню моря атмосферное давление."
                ,
                "meteorological_station_level_atmospheric" => "гПа, Атмосферное давление - измеренное на уровне метеостанции атмосферное давление."
                ,
            ],
            "precipitation" => (object)[
                "r" => "мм, Количество осадков - Количество выпавших осадков за период времени, мм. При наведении курсора мыши на число - период времени, за который выпало указанное количество осадков."
                ,
                "r24" => "мм, Количество осадков - Количество выпавших осадков за 24 часа, мм."
                ,
                "s" => "см, Снежный покров - Высота снежного покрова, см. При наведении курсора мыши на число - состояние снежного покрова и степень покрытия местности в баллах."

            ]

        ];
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
        $currentMonth = Carbon::now()->month;
        $currentDay = Carbon::now()->day;

        $daysInMonth = $month == $currentMonth ? $currentDay :$daysInMonth ;

        $dom = HtmlDomParser::file_get_html("http://www.pogodaiklimat.ru/weather.php?id=$region&bday=1&fday=$daysInMonth&amonth=$month&ayear=$year&bot=2", false, null, 0);


        $month = $this->parser($dom, 0);

        Storage::put($path, json_encode($month));

        return $month;

    }
}

<?php

namespace App\Http\Controllers;

use App\Classes\GismeteoAPI;
use App\Classes\PogodaIKlimatAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{
    //

    protected $gismeteo;

    protected $pogodaiklimat;

    public function __construct()
    {
        $this->gismeteo = new GismeteoAPI();
        $this->pogodaiklimat = new PogodaIKlimatAPI();
    }

    public function indexV1($region_id, $year, $month)
    {
        try {
            return response()->json($this->gismeteo->oneMonthAPI($region_id, $year, $month));
        } catch (\Exception $ex) {
            Log::info($ex->getMessage());
            return response()->json([
                "message" => "Ошибка параметров запроса"
            ]);
        }
    }

    public function indexV2($region_id, $year, $month)
    {
        try {
            return response()->json($this->pogodaiklimat->oneMonthAPI($region_id, $year, $month));
        } catch (\Exception $ex) {
            Log::info($ex->getMessage());
            return response()->json([
                "message" => "Ошибка параметров запроса"
            ]);
        }
    }

    public function dictionaries()
    {
        return response()->json([
            "cloudy" => $this->gismeteo->getCloudyDictionary(),
            "wind" => $this->gismeteo->getWindDictionary(),
            "precipitation" => $this->gismeteo->getPrecipitationDictionary()
        ]);
    }

}

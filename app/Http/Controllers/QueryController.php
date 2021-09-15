<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Query;
use Illuminate\Support\Facades\Validator;

class QueryController extends Controller
{
    public function get_data(){
        try{
            require_once("coordinates.php");
            ini_set('memory_limit', '-1');
            $geojson = new \stdClass;
            $geojson->type = 'FeatureCollection';
            // $spatial_reference = new \stdClass;
            // $spatial_reference->wkid = 4326;
            // $spatial_reference->latestWkid = 4326;
            // $geojson->spatialReference = $spatial_reference;
            $geojson->features = [];

            $query = \DB::table('queries')->select('feature');
            $query->orderBy('id')->chunk(5000, function ($kadastrs) use (&$geojson) {
                foreach ($kadastrs as $item) {
                    $feature = json_decode($item->feature);
                    $arr_coords = [];
                    foreach ($feature->geometry->rings[0] as $elem) {
                        $init_x = (float)$elem[0];
                        $init_y = (float)$elem[1];
                        $coords = json_decode(utm2ll($init_x, $init_y, 42, true), true);
                        $x = $coords['attr']['lon'];
                        $y = $coords['attr']['lat'];
                        $x = $x-0.000264;//-0.000264
                        $y = $y+0.000633;//0.000633
                        $arr_coords[] = [$x, $y];
                    }
                    $feature->type = 'Feature';
                    $feature->geometry->rings[0] = $arr_coords;
                    array_push($geojson->features, $feature);
                }
            });
            file_put_contents('lands.json', json_encode($geojson));
            // $fp = fopen('lidn.txt', 'w');
            // fwrite($fp, $geojson);
            // fclose($fp);
            return response()->json('ok', 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function get_data2(){
        try{
            require_once("coordinates.php");
            ini_set('memory_limit', '-1');
            $geojson = new \stdClass;
            $geojson->type = 'FeatureCollection';
            // $spatial_reference = new \stdClass;
            // $spatial_reference->wkid = 4326;
            // $spatial_reference->latestWkid = 4326;
            // $geojson->spatialReference = $spatial_reference;
            $geojson->features = [];

            $query = \DB::table('queries')->select('feature')->where('id','<', 100);
            $query->orderBy('id')->chunk(5000, function ($kadastrs) use (&$geojson) {
                foreach ($kadastrs as $item) {
                    $feature = json_decode($item->feature);
                    $feature->type = 'Feature';
                    array_push($geojson->features, $feature);
                }
            });
            return response()->json($geojson, 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}

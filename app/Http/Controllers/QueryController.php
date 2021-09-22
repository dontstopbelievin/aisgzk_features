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
            $query->where('id', '<', 10);
            $query->orderBy('id')->chunk(5000, function ($kadastrs) use (&$geojson) {
                foreach ($kadastrs as $item) {
                    // $feature = new \stdClass;
                    // $feature->type = 'Feature';
                    // $feature->geometry = new \stdClass;
                    // $feature->geometry->type = 'Polygon';
                    // $feature->geometry->coordinates = [];
                    // $arr_coords = [];
                    // $item = json_decode($item->feature);
                    // foreach ($item->geometry->rings[0] as $elem) {
                    //     $x = (float)$elem[0];
                    //     $y = (float)$elem[1];
                    //     // $coords = json_decode(utm2ll($x, $y, 42, true), true);
                    //     // $x = $coords['attr']['lon'];
                    //     // $y = $coords['attr']['lat'];
                    //     // $x = $x-0.000264;
                    //     // $y = $y+0.000633;
                    //     $arr_coords[] = [$x, $y];
                    // }
                    $feature = json_decode($item->feature);
                    $feature->attributes->KadNumber = $feature->attributes->KAD_NOMER;
                    $feature->attributes->KadNumberInt = intval($feature->attributes->KAD_NOMER);
                    $geojson->features[] = $feature;
                    // $feature->geometry->coordinates[] = $item->geometry->rings[0];
                    // $feature->properties = new \stdClass;
                    // foreach ($item->attributes as $key => $value) {
                    //     $feature->properties->{(string)$key} = (string)$value;
                    // }
                    // array_push($geojson->features, $feature);
                }
            });
            file_put_contents('lands.json', json_encode($geojson));
            // $fp = fopen('lidn.txt', 'w');
            // fwrite($fp, $geojson);
            // fclose($fp);
            return response()->json($geojson, 200);
            return response()->json('ok', 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function get_data2(){
        try{
            ini_set('memory_limit', '-1');
            $esrijson = new \stdClass;
            $esrijson->displayFieldName = 'KAD_NOMER';
            $fieldAliases = new \stdClass;
            $fieldAliases->OBJECTID = 'OBJECTID';
            $fieldAliases->KAD_NOMER = 'KAD_NOMER';
            $fieldAliases->Shape_Length = 'Shape_Length';
            $fieldAliases->Shape_Area = 'Shape_Area';
            $fieldAliases->KOORDINATED = 'KOORDINATED';
            $esrijson->fieldAliases = $fieldAliases;
            $esrijson->geometryType = 'esriGeometryPolygon';
            $spatial_reference = new \stdClass;
            $spatial_reference->wkid = 32642;
            $spatial_reference->latestWkid = 32642;
            $esrijson->spatialReference = $spatial_reference;
            $f1 = new \stdClass;
            $f1->name = 'OBJECTID';
            $f1->type = 'esriFieldTypeOID';
            $f1->alias = 'OBJECTID';
            $f2 = new \stdClass;
            $f2->name = 'KAD_NOMER';
            $f2->type = 'esriFieldTypeString';
            $f2->alias = 'KAD_NOMER';
            $f2->length = 12;
            $f3 = new \stdClass;
            $f3->name = 'Shape_Length';
            $f3->type = 'esriFieldTypeDouble';
            $f3->alias = 'Shape_Length';
            $f4 = new \stdClass;
            $f4->name = 'Shape_Area';
            $f4->type = 'esriFieldTypeDouble';
            $f4->alias = 'Shape_Area';
            $f5 = new \stdClass;
            $f5->name = 'KOORDINATED';
            $f5->type = 'esriFieldTypeSmallInteger';
            $f5->alias = 'KOORDINATED';
            $esrijson->fields = [$f1, $f2, $f3, $f4, $f5];            
            $esrijson->features = [];

            $query = \DB::table('queries')->select('feature');
            // $query->where('id', '<', 10);
            // $query->where('id', '<', 20000);
            // $query->whereBetween('id', [10000, 25000]);
            $query->orderBy('id')->chunk(5000, function ($kadastrs) use (&$esrijson) {
                foreach ($kadastrs as $item) {
                    $feature = json_decode($item->feature);
                    unset($feature->attributes->OBJECTID);
                    array_push($esrijson->features, $feature);
                }
            });
            file_put_contents('lands_original.json', json_encode($esrijson));
            // return response()->json($esrijson, 200);
            return response()->json('ok', 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}

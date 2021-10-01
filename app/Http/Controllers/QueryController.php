<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Query;
use Illuminate\Support\Facades\Validator;
include("proj4php/vendor/autoload.php");
use proj4php\Proj4php;
use proj4php\Proj;
use proj4php\Point;

class QueryController extends Controller
{
    public function get_data(){
        try{
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
            // Initialise Proj4
            $proj4 = new Proj4php();
            // Create two different projections.
            $projFrom    = new Proj('EPSG:32642', $proj4);
            $projTo  = new Proj('EPSG:4326', $proj4);

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
            $spatial_reference->wkid = 4326;
            $spatial_reference->latestWkid = 4326;
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
            // $query->where('CadNumber', '213201354690');
            // $query->where('id', '<', 20000);
            // $query->whereBetween('id', [10000, 25000]);
            // $query->orderBy('id')->chunk(5000, function ($kadastrs) use (&$esrijson) {
            $query->orderBy('id')->chunk(5000, function ($kadastrs) use (&$esrijson, &$projFrom, &$projTo, &$proj4) {
                foreach ($kadastrs as $item) {
                    $feature = json_decode($item->feature);
                    unset($feature->attributes->OBJECTID);
                    $arr_coords = [];
                    foreach ($feature->geometry->rings as $ring) {
                        $sub_array = [];
                        foreach ($ring as $item) {
                            $x = (float)$item[0];
                            $y = (float)$item[1];
                            $pointSrc = new Point($x, $y, $projFrom);
                            $pointDest = $proj4->transform($projTo, $pointSrc);
                            $coords = explode(" ", $pointDest->toShortString());
                            $x = (float)$coords[0]-0.00024752;
                            $y = (float)$coords[1]+0.00066566;
                            // $x -= 19.7517; -0,00024752
                            // $y += 73.5397;0,00066566
                            // $arr_coords[] = [(float)$coords[0], (float)$coords[1]];
                            $sub_array[] = [$x, $y];
                        }
                        $arr_coords[] = $sub_array;
                    }
                    $feature->geometry->rings = $arr_coords;
                    array_push($esrijson->features, $feature);
                }
            });
            file_put_contents('lands_4326.json', json_encode($esrijson));
            // return response()->json($esrijson, 200);
            return response()->json('ok', 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}

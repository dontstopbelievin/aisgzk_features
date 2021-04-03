<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Land;
use Illuminate\Support\Facades\Validator;

class LandController extends Controller
{
	public function get_data(){
		try{
			$lands = Land::select('feature')->get();
			// $lands = Land::select('feature')->limit(3)->get();
			// $lands = Land::select('feature')->where('CadNumber', '21324120114')->get();
			$geojson = new \stdClass;
			$geojson->type = 'FeatureCollection';
			$geojson->features = [];
			foreach ($lands as $item) {
				$geojson->features[] = json_decode($item->feature);
			}
    		return response()->json($geojson, 200);
    	} catch (Exception $e) {
    		return response()->json(['message' => $e->getMessage()], 500);
    	}
	}

	public function get_data2(Request $request){
		$validator = Validator::make($request->all(),[
            'x' => 'required|numeric',
            'y' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 500);
        }
		try{
			$move_x = $request->all()['x'];
			$move_y = $request->all()['y'];
			$lands = Land::select('feature_gps')->get();
			// $lands = Land::select('feature_gps')->limit(3)->get();
			// $lands = Land::select('feature_gps')->where('CadNumber', '21324032827')->get();
			$geojson = new \stdClass;
			$geojson->type = 'FeatureCollection';
			$geojson->features = [];
			foreach ($lands as $item) {
				$f = json_decode($item->feature_gps);
				foreach ($f->geometry->coordinates[0] as &$cords){
					$cords[0] = $cords[0]+(float)$move_x;//-0.000264
					$cords[1] = $cords[1]+(float)$move_y;//0.000633
				}
				$geojson->features[] = $f;
			}
    		return response()->json($geojson, 200);
    	} catch (Exception $e) {
    		return response()->json(['message' => $e->getMessage()], 500);
    	}
	}

    public function from_xml_file(){
    	try {
    		require_once("coordinates.php");
    		$files = array_diff(scandir('/var/www/aisgzk_data/public/xml_files'), array('.', '..'));
    		$insert = 0;
			$update = 0;
    		foreach ($files as $file) {
    			$fileContents = file_get_contents("/var/www/aisgzk_data/public/xml_files/".$file);
		        $fileContents = str_replace(array("\n", "\r", "\t"), '', $fileContents);
		        $fileContents = trim(str_replace('"', "'", $fileContents));
		        $clean_xml = str_ireplace(['SOAP-ENV:', 'SOAP:', 'shep1:', 'shep2:'], '', $fileContents);
				$simpleXml = simplexml_load_string($clean_xml);
				foreach ($simpleXml->Body->SendMessage->request->requestData->data->Data as $item) {
					$CadNumber = trim(str_replace('-', '', $item->GzkObject->CadNumber));
					$feature = $this->get_feature($item);
					$feature_gps = $this->get_feature_gps($item);
					if(Land::where('CadNumber', $CadNumber)->count() > 0){
						$land = Land::where('CadNumber', $CadNumber)->first();
						$land->feature = $feature;
						$land->feature_gps = $feature_gps;
			    		$land->ActualDate = $item->ActualDate;
			    		$sd_arr = [];
						foreach ($item->SemanticData as $sd){
							$sd_arr[] = $sd;
						}
			    		$land->SemanticData = json_encode($sd_arr);
			    		$land->Geometry = json_encode($item->Geometry);
			    		$land->GzkObject = json_encode($item->GzkObject);
			    		$land->save();
						$update++;
					}else{
						$land = new Land();
						$land->CadNumber = $CadNumber;
						$land->feature = $feature;
						$land->feature_gps = $feature_gps;
			    		$land->ActualDate = $item->ActualDate;
			    		$sd_arr = [];
						foreach ($item->SemanticData as $sd){
							$sd_arr[] = $sd;
						}
			    		$land->SemanticData = json_encode($sd_arr);
			    		$land->Geometry = json_encode($item->Geometry);
			    		$land->GzkObject = json_encode($item->GzkObject);
			    		$land->save();
			    		$insert++;
					}
				}
    		}
    		return response()->json(['insert' => $insert, 'update' => $update], 200);
    	} catch (Exception $e) {
    		return response()->json(['message' => $e->getMessage()], 500);
    	}
    }

    public function get_feature($item){
    	$feature = new \stdClass;
		$feature->type = 'Feature';
		$feature->geometry = new \stdClass;
		$feature->geometry->type = 'Polygon';
		// $spatial_reference = new \stdClass;
		// $spatial_reference->wkid = 32642;
		// $spatial_reference->latestWkid = 32642;
		// $feature->spatialReference = $spatial_reference;
		$feature->geometry->coordinates = [];
		// $feature->geometry->spatialReference = $spatial_reference;
		$arr_coords = [];
		foreach ($item->Geometry->NewGeometry->Vertexes as $elem) {
			$init_x = (float)str_replace(',', '.', $elem->X);
			$init_y = (float)str_replace(',', '.', $elem->Y);
			$arr_coords[] = [$init_x, $init_y];
		}
		$feature->geometry->coordinates[] = $arr_coords;
		$feature->properties = new \stdClass;
		foreach ($item->SemanticData as $elem) {
			$feature->properties->{(string)$elem->ElementName} = (string)$elem->ElementValue;
		}
		return json_encode($feature);
    }

    public function get_feature_gps($item){
    	$feature = new \stdClass;
		$feature->type = 'Feature';
		$feature->geometry = new \stdClass;
		$feature->geometry->type = 'Polygon';
		// $spatial_reference = new \stdClass;
		// $spatial_reference->wkid = 32642;
		// $spatial_reference->latestWkid = 32642;
		// $feature->spatialReference = $spatial_reference;
		$feature->geometry->coordinates = [];
		// $feature->geometry->spatialReference = $spatial_reference;
		$arr_coords = [];
		foreach ($item->Geometry->NewGeometry->Vertexes as $elem) {
			$init_x = (float)str_replace(',', '.', $elem->X);
			$init_y = (float)str_replace(',', '.', $elem->Y);
			$coords = json_decode(utm2ll($init_x, $init_y, 42, true), true);
			$x = $coords['attr']['lon'];
			$y = $coords['attr']['lat'];
			$arr_coords[] = [$x, $y];
			// $arr_coords[] = [$init_x, $init_y];
		}
		$feature->geometry->coordinates[] = $arr_coords;
		$feature->properties = new \stdClass;
		foreach ($item->SemanticData as $elem) {
			$feature->properties->{(string)$elem->ElementName} = (string)$elem->ElementValue;
		}
		return json_encode($feature);
    }
}

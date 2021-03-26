<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Land;

class LandController extends Controller
{
	public function get_data(){
		try{
			// $lands = Land::select('feature')->limit(3)->get();
			$lands = Land::select('feature')->get();
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

    public function from_xml_file(){
    	try {
    		$files = array_diff(scandir('/var/www/aisgzk_data/public/xml_files'), array('.', '..'));
    		foreach ($files as $file) {
    			$fileContents = file_get_contents("/var/www/aisgzk_data/public/xml_files/".$file);
		        $fileContents = str_replace(array("\n", "\r", "\t"), '', $fileContents);
		        $fileContents = trim(str_replace('"', "'", $fileContents));
		        $clean_xml = str_ireplace(['SOAP-ENV:', 'SOAP:', 'shep1:', 'shep2:'], '', $fileContents);
				$simpleXml = simplexml_load_string($clean_xml);
				$insert = 0;
				$update = 0;
				foreach ($simpleXml->Body->SendMessage->request->requestData->data->Data as $item) {
					$CadNumber = trim(str_replace('-', '', $item->GzkObject->CadNumber));
					$feature = $this->get_feature($item);
					if(Land::where('CadNumber', $CadNumber)->count() > 0){
						$land = Land::where('CadNumber', $CadNumber)->first();
						$land->feature = $feature;
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
		$feature->geometry->coordinates = [];
		$arr_coords = [];
		foreach ($item->Geometry->NewGeometry->Vertexes as $elem) {
			$arr_coords[] = [(float)str_replace(',', '.', $elem->X), (float)str_replace(',', '.', $elem->Y)];
		}
		$feature->geometry->coordinates[] = $arr_coords;
		$feature->properties = new \stdClass;
		foreach ($item->SemanticData as $elem) {
			$feature->properties->{(string)$elem->ElementName} = (string)$elem->ElementValue;
		}
		return json_encode($feature);
    }
}

<?php
/**
 * @author Rainer Spittel (rainer at silverstripe dot com)
 * @package openlayers
 * @subpackage code
 */

/**
 *
 */
class OLMapPage extends Page {
	
	static $db = array(
	);	
	
	static $has_one = array(
		'Map' => 'OLMapObject'
	);

	/**
	 * Overwrites SiteTree.getCMSFields.
	 *
	 * @return fieldset
	 */
	function getCMSFields() {
		$fields = parent::getCMSFields();
		
		$maps  = DataObject::get("OLMapObject");
		$items = array();

		// get all assigned agent-members
		if ($maps) {
			// get first member (agents)
			$items = $maps->map('ID','Title');
		}

		$fields->addFieldsToTab("Root.OpenLayers", 
			array(
				new LiteralField("MapLabel","<h2>Map Selection</h2>"),
				// Display parameters
				new CompositeField( 
					new CompositeField( 
						new LiteralField("DefLabel","<h3>Default OpenLayers Map</h3>"),
						new DropdownField("MapID", "Map", $items, $this->MapID, null, true)
					)
				)
			)
		);
		return $fields;
	}
	
	/**
	 * This method returns the default configuration array structure of the 
	 * default map. It is used to initialize the OpenLayer JavaScript classes
	 * after the page has been loaded.
	 *
	 * @return array Configuration array which is processed by JS:initMap
	 */
	public function getDefaultMapConfiguration() {
		$result    = array();
		$mapObject = $this->GetComponent('Map');
		
		if($mapObject) {
			$result = $mapObject->getConfigurationArray();
		}
		return $result;
	}
	
	/**
	 * Returns a viewable data object to render the layer control of the default
	 * map.
	 * @return ViewableData
	 */
	public function getLayerControlObject() {
		$mapObject = $this->GetComponent('Map');
		$obj = new ViewableData();
		
		$result = array();
		if($mapObject) {
			$layers = $mapObject->getComponents('Layers','','DisplayPriority DESC');
			$obj->customise( array( "layers" => $layers ) );
		}
		return $obj;
	}
}

/**
 * Controller Class for Main OpenLayers Page
 *
 * Page controller class for OpenLayersPage (@link OpenLayersPage). The controller
 * class handles the requests and delegates the requests to the page instance
 * as well as to the available OGC webservices.
 */
class OLMapPage_Controller extends Page_Controller {
	
	/**
	 * varaible to store the open layers instance in the controller class.
	 * @var OpenLayers openLayers
	 */
	protected $openLayers = null;

	/**
	 * Returns the open layers instance (via singleton pattern).
	 *
	 * @return OpenLayers model class for the open layers implementation.
	 */
	function getOpenLayers() {
		if ($this->openLayers == null) {
			$this->openLayers = new OpenLayersModel();
		}
		return $this->openLayers;
	}

	/**
	 * Initialisation function that is run before any action on the controller is called.
	 */
	public function init() {
		
		parent::init();
		
		$page = $this->data();
		
		$openLayers = $this->getOpenLayers();
		Requirements::javascript( $openLayers->getRequiredJavaScript() );		
		
		Requirements::javascript('openlayers/javascript/jquery/jquery-1.3.2.min.js');
		Requirements::javascript('openlayers/javascript/jquery/jquery-ui-1.7.2.custom.min.js');
		//Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
		
		Requirements::javascript('openlayers/javascript/OLMapWrapper.js');
		Requirements::javascript('openlayers/javascript/OLMapPage.js');
		
		Requirements::themedCSS('OLMapPage');

		// serialize map cofiguration 
		$config = $page->getDefaultMapConfiguration();		
		
		// add url segment for this page (required for js ajax calls).
		$config['PageName'] = $this->getField('URLSegment');
		
		// create json string
		$jsConfig = "var ss_config = ".json_encode($config);
		
		// add configuration json object to custom scripts
		Requirements::customScript($jsConfig);
	}

	/**
	 * Render the layer selector.
	 *
	 * @return string HTML-string which represents the layer-list div object.
	 */
	function FormLayerSwitcher() {		
		$page = $this->data();
		
		$obj = $page->getLayerControlObject();
		return $obj->renderWith('LayerControl');
	}

	/**
	 * Returns the HTML response for the map popup-box. After the user clicks
	 * on the map, the CMS will send of a request to the OGC server to request
	 * a XML data structure for the features on that selected location, parses
	 * the XML response and renders the HTML, which will be returned to the
	 * popup window.
	 *
	 * @param Request $data
	 *
	 * @return string HTML segment
	 */
	public function dogetfeatureinfo( $request ) {
		
		$output = "Sorry we cannot retrieve feature information, please try again";
		// check if the request is for more than one station (clustered)
		$stationID = Director::urlParam("OtherID");
		if(strpos($stationID,",") === FALSE){
			// process request parameters
			$mapid   = (int)Director::urlParam("ID"); 
			$feature = explode(".",Director::urlParam("OtherID")); 
			
			// test if user requests feature-info for one element (otherwise create 
			// overview template.
		
			$layerName = Convert::raw2sql($feature[0]);
			$featureID = Convert::raw2sql($feature[1]);
		
			$layer = DataObject::get_one('OLLayer',"ogc_name = '{$layerName}' AND MapID = '{$mapid}'");

			if($layer){
				$atts = array();
				$pattern = '/.attribute./';
				$output = $layer->getFeatureInfo($featureID);
				
				$obj = new DataObjectSet();
				$out = new ViewableData();
				$reader = new XMLReader();
				$reader->XML($output);
				
				// loop xml for attributes 
				while ($reader->read()) {
					if(preg_match($pattern,$reader->name)){
						if($reader->readInnerXML() != ""){
							$atts[$reader->name] = $reader->readInnerXML();
						}
					} 
				}
				$reader->close();
				foreach($atts as $key => $value){
					$obj->push(new ArrayData(array(
						'attributeName' => $key,
						'attributeValue' => $value
					)));
				}
				$out->customise( array( "attributes" => $obj, "stationName" => $stationID ) );
				return $out->renderWith('SingleStationPopup');
			}
		
			
		} else{
			$stationIDs = explode(",",$stationID);
			$obj = new DataObjectSet();
			$out = new ViewableData();
			foreach($stationIDs as $stationID){
				$obj->push(new ArrayData(array(
					'Station' => $stationID
				)));
			}
			$out->customise( array( "stations" => $obj ) );
			return $out->renderWith('MultipleStationsPopup');
		}

		return $output;
	}
	
}

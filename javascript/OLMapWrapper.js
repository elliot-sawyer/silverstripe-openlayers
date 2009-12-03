/**
 * 
 */
var map   = null;		// global map instance


/**
 * Initialise the open layers map instance and uses a div object which
 * must exist in the DOM. 
 *
 * @param string divMap name of the target div object
 **/
function initMap(divMap, mapConfig) {

	// set default location of the map
	var map_config = mapConfig['Map'];

	var lon  = map_config['Longitude'];
	var lat  = map_config['Latitude'];
	var zoom = parseInt(map_config['Zoom']);

	var minScale      = parseInt(map_config['MinScale']);
	var maxScale      = parseInt(map_config['MaxScale']);
	var maxResolution = parseInt(map_config['MaxResolution']);
	var maxExtent     = parseInt(map_config['maxExtent']);

	map_extent = mapConfig['MaxMapExtent'];

	var extent_left    = (map_extent['left']);
	var extent_bottom  = (map_extent['bottom']);
	var extent_right   = (map_extent['right']);
	var extent_top     = (map_extent['top']);

	map = new OpenLayers.Map(divMap, {
	    controls: [
	        new OpenLayers.Control.Navigation(),
	        new OpenLayers.Control.PanZoomBar(),
	        new OpenLayers.Control.Permalink(),
	        new OpenLayers.Control.ScaleLine(),
	        new OpenLayers.Control.Permalink('permalink'),
	        new OpenLayers.Control.MousePosition(),
	        new OpenLayers.Control.OverviewMap(),
	        new OpenLayers.Control.KeyboardDefaults()
	    ],

		// apply extent/resolution settings to the map
        minScale: minScale,
        maxResolution: maxResolution,
        maxScale: maxScale,

		maxExtent: new OpenLayers.Bounds(extent_left,extent_bottom,extent_right,extent_top),
	});

	// initiate all overlay layers
	var layers = mapConfig['Layers'];
	jQuery.each( layers , initLayer );

	/*
	name = 'tilecache';
	options = {  transparent: 'false', layers: 'basic'};

	url = 'http://192.168.1.199/cgi-bin/tilecache.cgi';
	layer = new OpenLayers.Layer.WMS( name, url, options );
	map.addLayer(layer);
	*/	

	map.setCenter(new OpenLayers.LonLat(lon, lat), zoom);
}

/**
 * Initiate a single layer by its layer-definitiion array. The array
 * is generated via the CMS backend.
 *
 * @param int index Index of layer in the complate layer-array.
 * @param array layerDef layer definition array.
 */
function initLayer( index, layerDef ) {	

	var layer = null;

	if (layerDef.Type == 'wms' || layerDef.Type == 'wmsUntiled') {
		var title = layerDef.Title;
		var url = layerDef.Url;
 		var options = layerDef.Options;

		LayerType = 'wms';
		if(layerDef.Type == 'wmsUntiled'){
			layer = new OpenLayers.Layer.WMS.Untiled( title, url, options );
		} 
		else{
			layer = new OpenLayers.Layer.WMS( title, url, options );
		} 			
	} else
	if (layerDef.Type == 'wfs') {
	
		// get a WFS layer
		layer = createClusteredWFSLayer(layerDef);
	//	layer = createWFSLayer(layerDef);
	}  

	// add new created layer to the map
	if (layer) {
		var visible = layerDef.Visible;
	
		layer.setVisibility(false);
		if (visible == "1") {
			layer.setVisibility(true);
		}
		map.addLayer(layer);

		if(current_layer == null && layerDef.ogc_transparent != 0) {
			current_layer =  layer;
		}
	}
}


/**
 * Create a WFS layer instance for Open Layers.
 */
function createClusteredWFSLayer(layerDef) {

    var style_normal = new OpenLayers.Style({
        pointRadius: "3",
        fillColor: "#ffcc66",
        fillOpacity: 0.8,
        strokeColor: "#cc6633",
        strokeWidth: 2,
        strokeOpacity: 0.8
    });

    var style_select = new OpenLayers.Style({
        fillColor: "#66ccff",
        strokeColor: "#3399ff"
    });

		
    var style_clustered = new OpenLayers.Style({
        pointRadius: "${radius}",
        fillColor: "#ffcc66",
        fillOpacity: 0.8,
        strokeColor: "#cc6633",
        strokeWidth: 2,
        strokeOpacity: 0.8
    }, {
        context: {
            radius: function(feature) {
                return Math.min(feature.attributes.count, 7) + 3;
            }
        }
    });

	var title   = layerDef.Title;
	var options = layerDef.Options;
	var wfs_url = layerDef.Url+"?map="+options['map'];
	var featureType = layerDef.ogc_name;

	var p = new OpenLayers.Protocol.WFS({ 
			url: wfs_url,
			featureType: featureType,
			featurePrefix: null,
	});			
	p.format.setNamespace("feature", "http://mapserver.gis.umn.edu/mapserver");

	var s = new OpenLayers.StyleMap({
        "default": style_clustered,
        "select": {
            fillColor: "#8aeeef",
            strokeColor: "#32a8a9"
        },
		"temporary" : {
            fillColor: "#000000",
            strokeColor: "#f0f0f0"
		}
    });

    var strategyCluster = new OpenLayers.Strategy.Cluster();
	strategyCluster.distance = 25;

	strategies =  [
        new OpenLayers.Strategy.Fixed(),
    	strategyCluster
    ];

    layer = new OpenLayers.Layer.Vector(title, {
        strategies: strategies,
        protocol: p ,
        styleMap: s
    });

	return layer;
}


/**
 * Create a WFS layer instance for Open Layers.
 */
function createWFSLayer(layerDef) {

	var wfs_url = layerDef.Url;
	var title   = layerDef.Title;
	var options = layerDef.Options;

	var layer   = new OpenLayers.Layer.WFS(title, wfs_url, options);

	// STYLE
	var myStyles = new OpenLayers.StyleMap({
        "default": new OpenLayers.Style({
            pointRadius: 5, // sized according to type attribute
            fillColor: "#FFD68F",
            strokeColor: "#ff9933",
            strokeWidth: 2,
			fillOpacity: 0.3
        }),
        "select": new OpenLayers.Style({
			pointRadius: 5, // sized according to type attribute
            fillColor: "#66ccff",
            strokeColor: "#3399ff",
			strokeWidth: 3
        })
    });

	layer.styleMap = myStyles;
	return layer;
}	
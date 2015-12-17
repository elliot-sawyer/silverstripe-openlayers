<?php
/**
 * @author Rainer Spittel (rainer at silverstripe dot com)
 * @package openlayers
 * @subpackage tests
 */

class OLMapObjectTest extends SapphireTest
{

    /**
     * Initiate the controller and page classes and configure GeoNetwork service
     * to use the mockup-controller for testing.
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Remove test controller from global controller-stack.
     */
    public function tearDown()
    {
        parent::tearDown();
    }
    
    /**
     * Test the configuration array generation for the map object only (layers
     * will be tested below in this test-class.
     */
    public function testGetConfigurationArray()
    {
        
        // test standard map object settings
        $map = new OLMapObject();
        
        $map->Title = "Title";
        $map->Description = "Description";
        $map->Enabled = true;

        $map->ExtentLeft = -10.2;
        $map->ExtentBottom = 20.2;
        $map->ExtentRight = 10.2;
        $map->ExtentTop = -20.5;

        $map->InitLatitude = 10.99;
        $map->InitLongitude = 12.23;
        $map->InitZoom = 12;
        $map->write();
        
        $result = $map->getConfigurationArray();
        
        $array_map    = $result['Map'];
        $array_extent = $result['MaxMapExtent'];

        $this->assertEquals($array_map['Title'], 'Title');
        $this->assertEquals($array_map['Latitude'], 10.99);
        $this->assertEquals($array_map['Longitude'], 12.23);
        $this->assertEquals($array_map['Zoom'], 12);

        $this->assertEquals($array_extent['left'], -10.20);
        $this->assertEquals($array_extent['bottom'], 20.2);
        $this->assertEquals($array_extent['right'], 10.2);
        $this->assertEquals($array_extent['top'], -20.5);
    }

    /**
     * Test the configuration array generation for the map object with no 
     * layers.
     */
    public function testGetConfigurationArray_NoLayers()
    {
        
        // test standard map object settings
        $map = new OLMapObject();
        $map->write();
        
        // no layers attached to the map -> empty array
        $result = $map->getConfigurationArray();
    
        $array_layers = $result['Layers'];

        // no layers found
        $this->assertEquals(sizeof($array_layers), 0);
    }

    /**
     * Test the configuration array generation for the map object with disabled 
     * layers.
     */
    public function testGetConfigurationArray_DisabledLayer()
    {
        
        // test standard map object settings
        $map = new OLMapObject();
        $map->write();
        
        // add a layer
        $layer = new OLLayer();
        $layer->Enabled = "0";
        $layer->MapID = $map->ID;
        $layer->write();

        $result = $map->getConfigurationArray();

        $array_layers = $result['Layers'];
        $this->assertEquals(sizeof($array_layers), 0);
    }

    /**
     * Test the configuration array generation for the map object with enabled 
     * layers.
     */
    public function testGetConfigurationArray_EnabledLayer()
    {
        
        // test standard map object settings
        $map = new OLMapObject();
        $map->write();
        
        // add a layer
        $layer = new OLLayer();
        $layer->Enabled = "1";
        $layer->MapID = $map->ID;
        $layer->write();

        $result = $map->getConfigurationArray();

        $array_layers = $result['Layers'];
        $this->assertEquals(sizeof($array_layers), 1);
    }
    

    /**
     * Basic test to see if getCMSFields returns a fieldset.
     */
    public function testGetCMSFields()
    {
        $layer = new OLMapObject();
        $fieldset = $layer->getCMSFields();

        $this->assertTrue(is_a($fieldset, "FieldSet"));
    }
}

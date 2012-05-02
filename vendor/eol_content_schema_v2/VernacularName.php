<?php
namespace eol_schema;

class VernacularName extends DarwinCoreExtensionBase
{
    const EXTENSION_URL = "http://rs.gbif.org/extension/gbif/1.0/vernacularname.xml";
    const ROW_TYPE = "http://rs.gbif.org/terms/1.0/VernacularName";
    
    public static function validation_rules()
    {
        
    }
    
    protected function load_extension()
    {
        parent::load_extension();
        
        if(!isset($this->accepted_properties_by_uri['http://rs.tdwg.org/dwc/terms/taxonID']))
        {
            $this->accepted_properties = $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties'];
            $this->accepted_properties_by_name = $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties_by_name'];
            $this->accepted_properties_by_uri = $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties_by_uri'];
            
            // add dwc:taxonID
            $property = array();
            $property['name'] = 'taxonID';
            $property['namespace'] = 'http://rs.tdwg.org/dwc/terms';
            $property['uri'] = "http://rs.tdwg.org/dwc/terms/taxonID";
            $this->accepted_properties[] = $property;
            $this->accepted_properties_by_name[$property['name']] = $property;
            $this->accepted_properties_by_uri[$property['uri']] = $property;
            
            $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties'] = $this->accepted_properties;
            $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties_by_name'] = $this->accepted_properties_by_name;
            $GLOBALS['DarwinCoreExtensionProperties'][static::EXTENSION_URL]['accepted_properties_by_uri'] = $this->accepted_properties_by_uri;
        }
    }
}

?>
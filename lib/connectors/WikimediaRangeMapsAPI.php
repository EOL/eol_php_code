<?php
namespace php_active_record;
/* connector: No specific connector. But now used in WikiDataAPI.php. That's in Wikimedia range map images. */

class WikimediaRangeMapsAPI
{
    function __construct()
    {
        $this->txt_file = DOC_ROOT."/update_resources/connectors/files/Wikimedia_range_maps.txt";
    }
    public function store_wikimedia_range_map_images()
    {
        
    }
}
?>
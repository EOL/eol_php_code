<?php
namespace php_active_record;
/* connector: [collections_scrape.php]
*/
class CollectionsScrapeAPI
{
    function __construct($folder, $collection_id)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->object_ids = array();
        $this->dwca_file = "http://localhost/~eolit/cp/India Biodiversity Portal/520.tar.gz";
        $this->dwca_file = "https://dl.dropboxusercontent.com/u/7597512/India Biodiversity Portal/520.tar.gz";
        $this->taxon_page = "http://www.marinespecies.org/aphia.php?p=taxdetails&id=";
        $this->accessURI = array();
        
        $this->download_options = array("cache" => 1, "download_wait_time" => 2000000, "timeout" => 3600, "download_attempts" => 1); //"delay_in_minutes" => 1
        $this->download_options['expire_seconds'] = false; //always false, will not change anymore...
        if(Functions::is_production()) $this->download_options['cache_path'] = "/extra/eol_cache_collections/";
        else                           $this->download_options['cache_path'] = "/Volumes/AKiTiO4/eol_cache_collections/";
        
        $this->url["eol_collection"] = "https://eol.org/api/collections/1.0/".$collection_id.".json?filter=data_type&sort_by=recently_added&sort_field=&cache_ttl=";
        $this->url["eol_collection_page"] = "http://eol.org/collections/".$collection_id."/data_type?sort_by=1&view_as=3"; //&page=2 
        //e.g. "http://eol.org/collections/9528/images?page=2&sort_by=1&view_as=3";
        $this->url["eol_object"]     = "http://eol.org/api/data_objects/1.0/";
        
        $this->multimedia_data_types = array('images', 'video', 'sounds'); //multimedia types
        
        if(Functions::is_production()) $this->lifedesk_images_path = '/extra/other_files/LifeDesk_images/';
        else                           $this->lifedesk_images_path = '/Volumes/AKiTiO4/other_files/LifeDesk_images/';
        $this->media_path = "https://editors.eol.org/other_files/LifeDesk_images/";
    }

    // http://media.eol.org/content/2011/12/18/03/38467_orig.jpg        -> orig
    // http://media.eol.org/content/2012/03/28/09/98457_88_88.jpg       -> thumbnail
    
    function start()
    {
        if(!is_dir($this->lifedesk_images_path)) mkdir($this->lifedesk_images_path);
        // /* normal operation
        foreach($this->multimedia_data_types as $data_type) {
            $do_ids_sciname = self::get_obj_ids_from_html($data_type);
            $arr = array_keys($do_ids_sciname);                             echo "\n".count($arr)."\n";
            $do_ids = self::get_obj_ids_from_collections_api($data_type);   echo "\n".count($do_ids)."\n";
            $do_ids = array_merge($do_ids, $arr);
            $do_ids = array_unique($do_ids);                                echo "\n".count($do_ids)."\n";
            unset($arr); //not needed anymore
            foreach($do_ids as $do_id) self::process_do_id($do_id, @$do_ids_sciname[$do_id]);
        }
        // */
        /* preview mode
        $do_ids = array(13230214, 30865886, 30866171, 30866142); $do_ids_sciname = array(); //preview mode  ??? no taxon 29246746 29189521 //debug
        foreach($do_ids as $do_id) self::process_do_id($do_id, @$do_ids_sciname[$do_id]);
        */
        $this->archive_builder->finalize(TRUE);
    }
    private function download_multimedia_object($rec)
    {   /* 
        $mr->identifier     = $rec['dataObjectVersionID']; //$rec['identifier'];
        $mr->accessURI      = $rec['eolMediaURL'];
        */
        $options = $this->download_options;
        $options['expire_seconds'] = false; //doesn't need to expire at all
        $destination = $this->lifedesk_images_path.$rec['dataObjectVersionID'].".".pathinfo($rec['eolMediaURL'], PATHINFO_EXTENSION);
        if(!file_exists($destination)) {
            $local = Functions::save_remote_file_to_local($rec['eolMediaURL'], $options);
            Functions::file_rename($local, $destination);
            // echo "\n[$local]\n[$destination]";
        }
    }
    
    private function process_do_id($do_id, $sciname)
    {
        if($json = Functions::lookup_with_cache($this->url["eol_object"] . $do_id . ".json?cache_ttl=", $this->download_options)) {
            $obj = json_decode($json, true);
            if(!@$obj['scientificName']) {//e.g. collection_id = 106941 -> has hidden data_objects and dataObject API doesn't have taxon info.
                $obj['scientificName'] = $sciname;
                $obj['identifier'] = str_replace(" ", "_", strtolower($sciname));
            }
            //print_r($obj); //exit;
            self::create_archive($obj);
        }
    }
    private function create_archive($o)
    {   // FOR TAXON  ================================================
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $o['identifier'];
        $taxon->scientificName  = $o['scientificName'];
        // $taxon->furtherInformationURL = $this->page['species'].$rec['taxon_id'];
        // if($reference_ids = @$this->taxa_reference_ids[$t['int_id']]) $taxon->referenceID = implode("; ", $reference_ids);
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
        // FOR DATA_OBJECT ================================================
        /* [dataObjects] => Array
                    [0] => Array
                            [identifier] => 40a44c87cf6688bf6f531c75eb33c773
                            [dataObjectVersionID] => 13230214
                            [dataType] => http://purl.org/dc/dcmitype/StillImage
                            [dataSubtype] => 
                            [vettedStatus] => Trusted
                            [dataRatings] => Array
                                    [1] => 0
                                    [2] => 0
                                    [3] => 0
                                    [4] => 0
                                    [5] => 0
                            [dataRating] => 2.5
                            [mimeType] => image/jpeg
                            [created] => 2010-05-13T22:18:58Z
                            [modified] => 2010-05-13T22:18:58Z
                            [title] => Nectophrynoides viviparus from Udzungwa Scarp
                            [language] => en
                            [license] => http://creativecommons.org/licenses/by-nc/3.0/
                            [source] => http://africanamphibians.lifedesks.org/node/768
                            [description] => Nectophrynoides viviparus from Udzungwa Scarp<br><p><em>Nectophrynoides viviparus </em>from Udzungwa Scarp.</p>
                            [mediaURL] => http://africanamphibians.lifedesks.org/image/view/768/_original
                            [eolMediaURL] => http://media.eol.org/content/2011/10/14/16/90814_orig.jpg
                            [eolThumbnailURL] => http://media.eol.org/content/2011/10/14/16/90814_98_68.jpg
                            [agents] => Array
                                    [0] => Array
                                            [full_name] => Zimkus, Breda
                                            [homepage] => 
                                            [role] => photographer
                                        )
                                    [1] => Array
                                            [full_name] => Zimkus, Breda
                                            [homepage] => 
                                            [role] => publisher
                                        )
                            [references] => Array
                                (
                                )
                        )
                )
        */
        if($rec = $o['dataObjects'][0]) {
            $mr = new \eol_schema\MediaResource();
            $mr->taxonID        = $taxon->taxonID;
            $mr->identifier     = $rec['dataObjectVersionID']; //$rec['identifier'];
            $mr->type           = $rec['dataType'];
            $mr->subtype        = $rec['dataSubtype'];
            $mr->Rating         = $rec['dataRating'];
            $mr->Owner          = @$rec['rightsHolder'];
            $mr->language       = $rec['language'];
            $mr->format         = $rec['mimeType'];
            $mr->furtherInformationURL = @$rec['source'];
            self::download_multimedia_object($rec);
            $mr->accessURI      = $rec['eolMediaURL'];
            $mr->thumbnailURL   = $rec['eolThumbnailURL'];
            $mr->title          = $rec['title'];
            $mr->UsageTerms     = $rec['license'];
            $mr->description    = $rec['description'];
            $mr->modified       = $rec['modified'];
            $mr->CreateDate     = $rec['created'];
            /*
            $mr->rights = '';
            $mr->CVterm = '';
            $mr->LocationCreated = '';
            $mr->bibliographicCitation = '';
            $mr->audience = 'Everyone';
            if($reference_ids = some_func() $mr->referenceID = implode("; ", $reference_ids);
            */
            if($agent_ids = self::create_agents(@$rec['agents'])) $mr->agentID = implode("; ", $agent_ids);
            if(!isset($this->object_ids[$mr->identifier])) {
                $this->archive_builder->write_object_to_file($mr);
                $this->object_ids[$mr->identifier] = '';
            }
        }
    }
    private function create_agents($agents)
    {   /* [agents] => Array
            [0] => Array
                    [full_name] => Zimkus, Breda
                    [homepage] => 
                    [role] => photographer
                )
            [1] => Array
                    [full_name] => Zimkus, Breda
                    [homepage] => 
                    [role] => publisher
                )
        */
        $agent_ids = array();
        foreach($agents as $a) {
            if(!$a['full_name']) continue;
            $r = new \eol_schema\Agent();
            $r->term_name       = $a['full_name'];
            $r->agentRole       = $a['role'];
            $r->identifier      = md5("$r->term_name|$r->agentRole");
            $r->term_homepage   = $a['homepage'];
            $agent_ids[] = $r->identifier;
            if(!isset($this->agent_ids[$r->identifier])) {
               $this->agent_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }
    private function get_obj_ids_from_collections_api($data_type) //this is kinda hack since param 'page' is not working in API. Just used max per_page 500 to get the first 500 records.
    {
        $do_ids = array();
        $url = $this->url["eol_collection"] . "&page=1&per_page=500";
        $url = str_replace('data_type', $data_type, $url);
        if($json = Functions::lookup_with_cache($url, $this->download_options))
        {
            $arr = json_decode($json);
            count($arr->collection_items);
            foreach($arr->collection_items as $r) $do_ids[$r->object_id] = '';
        }
        return array_keys($do_ids);
    }
    private function get_total_pages($data_type)
    {
        $page = 1; $per_page = 50;
        $url = $this->url["eol_collection"] . "&page=$page&per_page=$per_page";
        $url = str_replace("data_type", $data_type, $url);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $arr = json_decode($json);
            return ceil($arr->total_items/50);
        }
    }
    private function get_obj_ids_from_html($data_type)
    {
        $do_ids_sciname = array(); $do_ids = array();
        $total_pages = self::get_total_pages($data_type);
        echo("\n[$data_type] [$total_pages pages]\n");
        $final = array();
        for($page=1; $page<=$total_pages; $page++) {
            $url = $this->url["eol_collection_page"]."&page=$page";
            $url = str_replace('data_type', $data_type, $url);
            $html = Functions::lookup_with_cache($url, $this->download_options); {
                echo "\n$page. [$url]";
                // <a href="/data_objects/26326917"><img alt="84925_88_88" height="68" src="http://media.eol.org/content/2013/09/13/13/84925_88_88.jpg" width="68" /></a>
                if(preg_match_all("/<a href=\"\/data_objects\/(.*?)<\/a>/ims", $html, $arr)) {
                    $rows = $arr[1];
                    // print_r($rows); exit;
                    $total_rows = count($rows)/4; 
                    $k = 0;
                    foreach($rows as $row) {
                        $k++;
                        // echo "\n$page of $total_pages - $k of $total_rows";
                        $rec = array();
                        if(preg_match("/src=\"(.*?)\"/ims", "_xxx".$row, $arr)) {
                            $rec['media_url'] = $arr[1];
                            if(preg_match("/_xxx(.*?)\"/ims", "_xxx".$row, $arr)) {
                                $rec['do_id'] = $arr[1];
                                $do_ids[$arr[1]] = '';
                            }
                        }
                        /* this part is for the scientificname - only used if dataObject API dosn't give taxon information e.g. https://eol.org/api/data_objects/1.0?id=29246746&taxonomy=true&cache_ttl=&language=en&format=json
                        [12] => 30865705"><img alt="07567_88_88" height="68" src="http://media.eol.org/content/2011/12/18/03/07567_88_88.jpg" width="68" />
                        [13] => 30865705"><span class="icon" title="This item is an image"></span>
                        [14] => 30865705">Cossypha roberti - (partial) distribution map (only focused o...
                        [15] => 30865705">Image of Cossypha roberti
                        */
                        if(preg_match("/\">(.*?)_xxx/ims", $row."_xxx", $arr)) {
                            $temp = $arr[1];
                            if(stripos($temp, " of ") !== false) //string is found -- "taxon"
                            {
                                if(preg_match("/_xxx(.*?)\"/ims", "_xxx".$row, $arr)) {
                                    $do_id = $arr[1];
                                    $temp_arr = explode(" of ", $temp);
                                    $do_ids_sciname[$do_id] = trim($temp_arr[1]);
                                }
                                
                            }
                        }
                        //end for the scientificname
                        
                        if($rec) $final[] = $rec;
                    } //end foreach()
                }
                // if($page >= 3) break; //debug
            }
        }
        // print_r($final); echo "\n".count($final)."\n"; exit;
        // print_r($do_ids_sciname); echo "\n".count($do_ids_sciname)."\n"; exit;
        // return array_keys($do_ids);
        return $do_ids_sciname;
    }

}
?>
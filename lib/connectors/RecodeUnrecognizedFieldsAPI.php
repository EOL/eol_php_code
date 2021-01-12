<?php
namespace php_active_record;
/* connector: [recode_unrecognized_fields.php]
*/
class RecodeUnrecognizedFieldsAPI
{
    function __construct($resource_id = NULL, $dwca_file = NULL, $params = array())
    {
        if($resource_id) {
            $this->resource_id = $resource_id;
            $this->dwca_file = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '.tar.gz';
        }
        $this->download_options = array('timeout' => 172800, 'expire_seconds' => 60*60*24*1); //probably default expires in 1 day 60*60*24*1. Not false.
        $this->debug = array();
        
        $this->unrecognized_fields_report = CONTENT_RESOURCE_LOCAL_PATH.'/reports/unrecognized_fields.txt';
    }
    public function process_all_resources()
    {
        // /*
        self::sought_fields(); //initialize
        $dwca_files = self::get_all_tr_gz_files_in_resources_folder(); //print_r($dwca_files);
        foreach($dwca_files as $file) { echo "\nProcessing [$file]...\n";
            // $file = '24.tar.gz'; //debug only - forced value
            self::scan_dwca($file);
            // break; //debug only
        }
        // */
        echo "\n--Report--\n";
        self::print_report();
    }
    private function print_report()
    {
        foreach(new FileIterator($this->unrecognized_fields_report) as $line_number => $line) {
            print_r(json_decode($line, true));
        }
    }
    private function get_all_tr_gz_files_in_resources_folder()
    {
        $arr = array();
        foreach(glob(CONTENT_RESOURCE_LOCAL_PATH . "*.tar.gz") as $filename) {
            $pathinfo = pathinfo($filename, PATHINFO_BASENAME);
            $arr[$pathinfo] = '';
        }
        ksort($arr);
        return array_keys($arr);
    }
    public function scan_dwca($dwca_file = false) //utility to search meta.xml for certain fields
    {
        if($dwca_file) $dwca_file = CONTENT_RESOURCE_LOCAL_PATH . $dwca_file;
        else           $dwca_file = $this->dwca_file; //used if called elsewhere
        if($paths = self::extract_dwca($dwca_file)) {
            if(is_file($paths['temp_dir'].'meta.xml')) {
                $xml_info = self::parse_meta_xml($paths['temp_dir'].'meta.xml');
                if($found = self::search_sought_fields($xml_info, $dwca_file)) {
                    echo "\nFOUND: ";
                    // print_r($found);
                    //write
                    $WRITE = Functions::file_open($this->unrecognized_fields_report, "a");
                    fwrite($WRITE, json_encode($found) . "\n");
                    fclose($WRITE);
                }
            }
            else echo "\n- No meta.xml [$dwca_file]\n";
        }
        else echo "\nERROR: Cannot extract [$dwca_file]\n";
        
        // remove temp dir
        // /*
        recursive_rmdir($paths['temp_dir']);
        echo ("\n temporary directory removed: " . $paths['temp_dir']);
        // */
    }
    private function search_sought_fields($xml_info, $dwca_file)
    {
        $sought_rowType_names = array('MEDIA', 'OCCURRENCES');
        $rowTypes_info['MEDIA'] = array('http://eol.org/schema/media/Document'); //array bec. in the future it may be e.g. 'http://rs.gbif.org/terms/1.0/multimedia'
        $rowTypes_info['OCCURRENCES'] = array('http://rs.tdwg.org/dwc/terms/Occurrence');
        
        // print_r($xml_info);
        $final = array();
        foreach($sought_rowType_names as $name) { //e.g. 'MEDIA'
            foreach($this->sought[$name] as $uri) { // echo "\n$uri\n";
                foreach($xml_info as $rowType => $fields) {
                    if(in_array($rowType, $rowTypes_info[$name])) {
                        if(in_array($uri, $fields)) $final[$dwca_file][$rowType][] = $uri;
                    }
                }
            }
        }
        return $final;
    }
    private function parse_meta_xml($meta_xml)
    {
        echo "\n$meta_xml\n";
        $xml = simplexml_load_file($meta_xml);
        $final = array();
        foreach($xml->table as $tab) {
            // print_r($tab);
            /*SimpleXMLElement Object(
                [@attributes] => Array(
                        [encoding] => UTF-8
                        [fieldsTerminatedBy] => \t
                        [linesTerminatedBy] => \n
                        [ignoreHeaderLines] => 1
                        [rowType] => http://rs.tdwg.org/dwc/terms/Taxon
                    )
                [files] => SimpleXMLElement Object(
                        [location] => taxon.tab
                    )
                [field] => Array(
                        [0] => SimpleXMLElement Object(
                                [@attributes] => Array(
                                        [index] => 0
                                        [term] => http://rs.tdwg.org/dwc/terms/taxonID
                                    )
                            )
            */
            $rowType = (string) $tab{'rowType'}; //echo "\n$rowType";
            foreach($tab->field as $fld) { //echo "\n".$fld{'term'}."\n";
                $final[$rowType][] = (string) $fld{'term'};
            }
        }
        // print_r($final);
        return $final;
    }
    private function extract_dwca($dwca_file)
    {
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($dwca_file, "meta.xml", $this->download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        print_r($paths); //exit("\n-exit muna-\n");
        // */

        /* development only
        $paths = Array(
            // 'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_28647/',
            // 'temp_dir' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_28647/'
            'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_81560/',
            'temp_dir' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_81560/'
        );
        */
        return $paths;
    }
    private function sought_fields()
    {
        $WRITE = Functions::file_open($this->unrecognized_fields_report, "w"); //initialize report
        fclose($WRITE);
        
        /*REFERENCES
        FYI, but you can leave these as is. We're not using them yet, but when we get cleverer with references, we aught to:
            http://eol.org/schema/reference/publicationType
            http://purl.org/ontology/bibo/pageEnd
            http://purl.org/ontology/bibo/pageStart
            http://purl.org/dc/terms/language

        MEDIA
        These are important; I can't believe I never noticed they were missing. Oops. They can all be recoded via the agents file, 
        with Agent Role assigned accordingly: */
        $this->sought['MEDIA'] = array('http://purl.org/dc/terms/contributor', 'http://purl.org/dc/terms/creator', 'http://purl.org/dc/terms/publisher');
        
        /* Hi Jen,
        Yes, we have agent roles for 'creator' and 'publisher'. But none for 'contributor'.
        Maybe we can also just say 'creator' or 'author' in agents for 'contributor'?
        Thanks.
        */

        /* Discard. We make our own thumbnails now for all media, even if someone is trying to make them for us:
            http://eol.org/schema/media/thumbnailURL

        FYI, but you can leave these as is. They're either redundant or not super important, but let's not throw them away.
            http://ns.adobe.com/xap/1.0/CreateDate
            http://ns.adobe.com/xap/1.0/Rating
            http://purl.org/dc/terms/audience
            http://purl.org/dc/terms/modified
            http://purl.org/dc/terms/rights
            http://purl.org/dc/terms/spatial
            http://rs.tdwg.org/ac/terms/derivedFrom
            http://www.w3.org/2003/01/geo/wgs84_pos#alt
            http://www.w3.org/2003/01/geo/wgs84_pos#lat
            http://www.w3.org/2003/01/geo/wgs84_pos#long

        I'm not sure what this is. Can you get me an example of some content from this field? */
        $this->sought['MEDIA'][] = 'http://rs.tdwg.org/ac/terms/additionalInformation';
        
        /*
        OCCURRENCES
        Recode as MoF records of with MeasurementOfTaxon=false: */
        $this->sought['OCCURRENCES'] = array('http://rs.tdwg.org/dwc/terms/basisOfRecord', 'http://rs.tdwg.org/dwc/terms/catalogNumber', 'http://rs.tdwg.org/dwc/terms/collectionCode', 'http://rs.tdwg.org/dwc/terms/countryCode', 'http://rs.tdwg.org/dwc/terms/institutionCode');
        /*
        Discard. But alert me if you find a resource with an actual Events file. Its contents will need recoding too: */
        $this->sought['OCCURRENCES'][] = 'http://rs.tdwg.org/dwc/terms/eventID';
        /*
        GLOBI
        I think there's currently a typo in both of these fields (http:/eol.org...) but either way, they want recoding again. 
        I think this will work- MoF records, measurementOfTaxon=false, should attach these to their occurrences. But they should be mapped to eol terms:

            http://eol.org/globi/terms/bodyPart => http://eol.org/schema/terms/bodyPart
            http://eol.org/globi/terms/physiologicalState => http://eol.org/schema/terms/physiologicalState

        I think the Occurrences records and the GloBI records will have mostly plain text strings as values, rather than URIs, which is fine. They'll do for metadata values.
        */
    }
}
?>
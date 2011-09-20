<?php
namespace php_active_record;
/* connector: 81 */
/* Connector scrapes data from BOLDS website - for higher level taxa */

class BoldsAPI
{
    const SPECIES_SERVICE_URL = "http://www.boldsystems.org/views/taxbrowser.php?taxid=";
    
    private static $TEMP_FILE_PATH;
    private static $WORK_LIST;
    private static $WORK_IN_PROGRESS_LIST;
    private static $INITIAL_PROCESS_STATUS;

    private static $MASTER_LIST;

    function start_process($resource_id, $call_multiple_instance)
    {
        self::$TEMP_FILE_PATH         = DOC_ROOT . "/update_resources/connectors/files/BOLD/";
        self::$WORK_LIST              = DOC_ROOT . "/update_resources/connectors/files/BOLD/hl_work_list.txt"; //hl - higher level taxa
        self::$WORK_IN_PROGRESS_LIST  = DOC_ROOT . "/update_resources/connectors/files/BOLD/hl_work_in_progress_list.txt";
        self::$INITIAL_PROCESS_STATUS = DOC_ROOT . "/update_resources/connectors/files/BOLD/hl_initial_process_status.txt";

        self::$MASTER_LIST = DOC_ROOT . "/update_resources/connectors/files/BOLD/hl_master_list.txt";
        if(!trim(Functions::get_a_task(self::$WORK_IN_PROGRESS_LIST)))//don't do this if there are harvesting task(s) in progress
        {
            if(!trim(Functions::get_a_task(self::$INITIAL_PROCESS_STATUS)))//don't do this if initial process is still running
            {
                // Divide the big list of ids into small files
                Functions::add_a_task("Initial process start", self::$INITIAL_PROCESS_STATUS);
                Functions::create_work_list_from_master_file(self::$MASTER_LIST, 5000, self::$TEMP_FILE_PATH, "batch_", self::$WORK_LIST); //debug orig value 5000
                Functions::delete_a_task("Initial process start", self::$INITIAL_PROCESS_STATUS);
            }
        }

        // Run multiple instances, for Bolds ideally a total of 2
        while(true)
        {
            $task = Functions::get_a_task(self::$WORK_LIST);//get a task to work on
            if($task)
            {
                print "\n Process this: $task";
                Functions::delete_a_task($task, self::$WORK_LIST);
                Functions::add_a_task($task, self::$WORK_IN_PROGRESS_LIST);
                $task = str_ireplace("\n", "", $task);//remove carriage return got from text file
                if($call_multiple_instance)
                {
                    Functions::run_another_connector_instance($resource_id, 1); //call 1 other instance for a total of 2 instances running
                    $call_multiple_instance = 0;
                }
                self::get_all_taxa($task);
                print "Task $task is done. \n";
                Functions::delete_a_task("$task\n", self::$WORK_IN_PROGRESS_LIST); //remove a task from task list
            }
            else
            {
                print "\n\n [$task] Work list done --- " . date('Y-m-d h:i:s a', time()) . "\n";
                break;
            }
        }
        if(!$task = trim(Functions::get_a_task(self::$WORK_IN_PROGRESS_LIST))) //don't do this if there are task(s) in progress
        {
            // Combine all XML files.
            self::combine_all_xmls($resource_id);
            // Set to force harvest
            if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml")) $GLOBALS['db_connection']->update("UPDATE resources SET resource_status_id=" . ResourceStatus::force_harvest()->id . " WHERE id=" . $resource_id);
            // Delete temp files
            self::delete_temp_files(self::$TEMP_FILE_PATH . "batch_", "txt");
            self::delete_temp_files(self::$TEMP_FILE_PATH . "temp_Bolds_" . "batch_", "xml");
        }
    }

    private function get_all_taxa($task)
    {
        $all_taxa = array();
        $used_collection_ids = array();
        $filename = self::$TEMP_FILE_PATH . $task . ".txt";
        $FILE = fopen($filename, "r");
        $i = 0;
        $save_count = 0;
        $no_eol_page = 0;
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $split = explode("\t", trim($line));
                $taxon = array("sciname" => $split[1] , "id" => $split[0]);
                $i++;
                print "\n $i -- " . $taxon['sciname'] . "\n";
                $arr = self::get_Bolds_taxa($taxon, $used_collection_ids);
                $page_taxa              = $arr[0];
                $used_collection_ids    = $arr[1];
                if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
                unset($page_taxa);
            }
        }
        fclose($FILE);

        $xml = \SchemaDocument::get_taxon_xml($all_taxa);
        $resource_path = self::$TEMP_FILE_PATH . "temp_Bolds_" . $task . ".xml";
        $OUT = fopen($resource_path, "w"); 
        fwrite($OUT, $xml); 
        fclose($OUT);
        print "\n\n total = $i \n\n";
    }

    function get_Bolds_taxa($taxon, $used_collection_ids)
    {
        $response = self::prepare_object($taxon);//this will output the raw (but structured) array
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["identifier"]]) continue;
            $taxon = Functions::prepare_taxon_params($rec);
            if($taxon) $page_taxa[] = $taxon;
            @$used_collection_ids[$rec["identifier"]] = true;
        }
        return array($page_taxa, $used_collection_ids);
    }

    private function prepare_object($taxon_rec)
    {
        $taxon = $taxon_rec["sciname"];
        $source = self::SPECIES_SERVICE_URL . urlencode($taxon_rec["id"]);
        $taxon_id = $taxon_rec["id"];
        $arr_taxa = array();
        $arr_objects = array();

        $arr = self::get_taxon_details($taxon_rec["id"]);
        $taxa = @$arr[0];
        $bold_stats = @$arr[1];
        $species_level = @$arr[2];
        $with_dobjects = @$arr[3];
        $with_map = @$arr[4];

        if(!$taxa && !$bold_stats && !$species_level && !$with_dobjects) return array();

        // check if there is content
        $description = self::check_if_with_content($taxon_rec["id"], $source, 1, $species_level);
        if(!$description and !$taxa) return array();

        //start #########################################################################  
        //if(intval($main->public_barcodes > 0))
        //if(intval($main->barcodes) > 0)
        if(true)
        {
            if($with_dobjects)
            {     
                //same for all text objects
                $mimeType   = "text/html";
                $dataType   = "http://purl.org/dc/dcmitype/Text";
                $subject    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#MolecularBiology"; //debug MolecularBiology
                $agent = array();
                $agent[] = array("role" => "compiler", "homepage" => "http://www.boldsystems.org/", "fullName" => "Sujeevan Ratnasingham");
                $agent[] = array("role" => "compiler", "homepage" => "http://www.boldsystems.org/", "fullName" => "Paul D.N. Hebert");
                $license = "http://creativecommons.org/licenses/by/3.0/";
                $rightsHolder = "Barcode of Life Data Systems";

                //1st text object
                if($description)
                {
                    $identifier = $taxon_rec["id"] . "_barcode_data";
                    $title      = "Barcode data";
                    $mediaURL   = ""; 
                    $location   = "";
                    $refs       = array();
                    $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject);
                }

                //another text object
                if($bold_stats)
                {
                    $description = "Barcode of Life Data Systems (BOLD) Stats <br> $bold_stats";
                    $identifier = $taxon_rec["id"] . "_stats";
                    $title = "Statistics of barcoding coverage";
                    $mediaURL   = ""; 
                    $location   = "";
                    $refs       = array();
                    $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject);
                }

                //another text object
                if($with_map)
                {
                    $map_url = "http://www.boldsystems.org/lib/gis/mini_map_500w_taxonpage_occ.php?taxid=" . $taxon_rec["id"];
                    $description = "Collection Sites: world map showing specimen collection locations for <i>" . $taxon_rec["sciname"] . "</i><br><img border='0' src='$map_url'>";
                    $identifier  = $taxon_rec["id"] . "_map";
                    $title = "Locations of barcode samples";
                    $mediaURL   = "";
                    $location   = "";
                    $refs       = array();
                    $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject);
                    print "\n map exists: $map_url \n";
                }            
                else print "\n no map for $taxon_rec[id] \n";
            }//if($taxa)
        }//with public barcodes
        
        if(sizeof($arr_objects))
        {
            $arr_taxa[]=array(  "identifier"   => $taxon_rec["id"],
                                "source"       => self::SPECIES_SERVICE_URL . urlencode($taxon_rec["id"]),
                                "kingdom"      => Functions::import_decode(@$taxa["kingdom"]),
                                "phylum"       => Functions::import_decode(@$taxa["phylum"]),
                                "class"        => Functions::import_decode(@$taxa["class"]),
                                "order"        => Functions::import_decode(@$taxa["order"]),
                                "family"       => Functions::import_decode(@$taxa["family"]),
                                "genus"        => Functions::import_decode(@$taxa["genus"]),
                                "sciname"      => Functions::import_decode($taxon_rec["sciname"]),
                                "data_objects" => $arr_objects
                             );
        }
        return $arr_taxa;
    }

    private function get_taxon_details($taxid)
    {
        /* this function will get:
            taxonomy
            BOLD stats
            boolean if species-level taxa
            if id/url is resolvable
            boolean if taxon has map (collections sites)
        */

        /*
        <span class="taxon_name">Aphelocoma californica PS-1 {species}&nbsp;
            <a title="phylum"href="taxbrowser.php?taxid=18">Chordata</a>;
            <a title="class"href="taxbrowser.php?taxid=51">Aves</a>;
            <a title="order"href="taxbrowser.php?taxid=321">Passeriformes</a>;
            <a title="family"href="taxbrowser.php?taxid=1160">Corvidae</a>;
            <a title="genus"href="taxbrowser.php?taxid=4698">Aphelocoma</a>;
        </span>
        <span class="taxon_name">Gastrolepidia {genus}&nbsp;
            <a title="phylum"href="taxbrowser.php?taxid=2">Annelida</a>;
            <a title="class"href="taxbrowser.php?taxid=24489">Polychaeta</a>;
            <a title="order"href="taxbrowser.php?taxid=25265">Phyllodocida</a>;
            <a title="family"href="taxbrowser.php?taxid=28521">Polynoidae</a>;
        </span>
        */

        $arr = array();
        $file = self::SPECIES_SERVICE_URL . $taxid;
        $orig_str = Functions::get_remote_file($file);
        
        //check if there is map:
        $pos = stripos($orig_str, "lib/gis/mini_map_500w_taxonpage_occ.php?taxid=");
        if(is_numeric($pos)) $with_map = true;
        else                 $with_map = false;
        
        //side script - to check if id/url is even resolvable
        $pos = stripos($orig_str,"fatal error");    
        if(is_numeric($pos)){print" -fatal error found- "; return array(false, false, false, false);}

        $str = $orig_str;
        if(preg_match("/taxon_name\">(.*?)<\/span>/ims", $str, $matches)) $str = $matches[1]; 

        //side script to check if species level taxa
        $pos = stripos($str, "{species}");    
        if(is_numeric($pos)) $species_level = true;
        else                 $species_level = false;

        $str = str_ireplace('<a title=', 'xxx<a title=', $str);
        $str = str_ireplace('</a>', '</a>yyy', $str);
        $str = str_ireplace('xxx', "&arr[]=", $str);
        $arr = array();
        parse_str($str);
        $taxa = array();
        foreach ($arr as $a)
        {
            $index = self::get_title_from_anchor_tag($a);
            $taxa[$index] = self::get_str_from_anchor_tag($a);
        }

        //=========================================================================//start get BOLD stats
        if(preg_match("/<h2>BOLD Stats<\/h2>(.*?)<\/table>/ims", $orig_str, $matches)) $str = $matches[1];
        $str = strip_tags($str, "<tr><td><table>");
        $str = str_ireplace('width="100%"', "", $str);
        $pos = stripos($str, "Species List - Progress"); 
        $str = substr($str, 0, $pos) . "</td></tr></table>";
        //=========================================================================

        $arr = array($taxa, $str, $species_level, true, $with_map);
        return $arr;
    }


    private function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject)
    {
        return array( "identifier"   => $identifier,
                      "dataType"     => $dataType,
                      "mimeType"     => $mimeType,
                      "title"        => $title,
                      "source"       => $source,
                      "description"  => $description,
                      "mediaURL"     => $mediaURL,
                      "agent"        => $agent,
                      "license"      => $license,
                      "location"     => $location,
                      "rightsHolder" => $rightsHolder,
                      "object_refs"  => $refs,
                      "subject"      => $subject
                    );
    }

    private function combine_all_xmls($resource_id)
    {
        print "\n\n Start compiling all XML...\n";
        $old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id .".xml";
        $OUT = fopen($old_resource_path, "w");
        $str = "<?xml version='1.0' encoding='utf-8' ?>\n";
        $str .= "<response\n";
        $str .= "  xmlns='http://www.eol.org/transfer/content/0.3'\n";
        $str .= "  xmlns:xsd='http://www.w3.org/2001/XMLSchema'\n";
        $str .= "  xmlns:dc='http://purl.org/dc/elements/1.1/'\n";
        $str .= "  xmlns:dcterms='http://purl.org/dc/terms/'\n";
        $str .= "  xmlns:geo='http://www.w3.org/2003/01/geo/wgs84_pos#'\n";
        $str .= "  xmlns:dwc='http://rs.tdwg.org/dwc/dwcore/'\n";
        $str .= "  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n";
        $str .= "  xsi:schemaLocation='http://www.eol.org/transfer/content/0.3 http://services.eol.org/schema/content_0_3.xsd'>\n";
        fwrite($OUT, $str);
        $i = 0;
        while(true)
        {
            $i++;
            $i_str = Functions::format_number_with_leading_zeros($i, 3);
            $filename = self::$TEMP_FILE_PATH . "temp_Bolds_" . "batch_" . $i_str . ".xml";
            if(!is_file($filename))
            {
                print " -end compiling XML's- ";
                break;
            }
            print " $i ";
            $READ = fopen($filename, "r");
            $contents = fread($READ, filesize($filename));
            fclose($READ);
            if($contents)
            {
                $pos1 = stripos($contents, "<taxon>");
                $pos2 = stripos($contents, "</response>");
                $str  = substr($contents, $pos1, $pos2 - $pos1);
                fwrite($OUT, $str);
            }
        }
        fwrite($OUT, "</response>");
        fclose($OUT);
        print "\n All XML compiled\n\n";
    }

    private function delete_temp_files($file_path, $file_extension)
    {
        $i = 0;
        while(true)
        {
            $i++;
            $i_str = Functions::format_number_with_leading_zeros($i, 3);
            $filename = $file_path . $i_str . "." . $file_extension;
            if(file_exists($filename))
            {
                print "\n unlink: $filename";
                unlink($filename);
            }
            else return;
        }
    }

    private function get_str_from_anchor_tag($str)
    {
        if(preg_match("/\">(.*?)<\/a>/ims", $str, $matches)) return $matches[1];
    }
    private function get_href_from_anchor_tag($str)
    {
        if(preg_match("/href=\"(.*?)\"/ims", $str, $matches)) return $matches[1];
    }
    private function get_title_from_anchor_tag($str)
    {
        if(preg_match("/<a title=\"(.*?)\"/ims", $str, $matches)) return $matches[1];
    }

    public function check_if_with_content($taxid, $dc_source, $public_barcodes, $species_level)
    {
        /*            
        Ratnasingham S, Hebert PDN. Compilers. 2009. BOLD : Barcode of Life Data System.
        World Wide Web electronic publication. www.boldsystems.org, version (08/2009). 
        */

        //start get text dna sequece
        $src = "http://www.boldsystems.org/connect/REST/getBarcodeRepForSpecies.php?taxid=" . $taxid . "&iwidth=400";
        if($species_level)
        {
            if(self::barcode_image_available($src))
            {
                $description = "The following is a representative barcode sequence, the centroid of all available sequences for this species.
                <br><a target='barcode' href='$src'><img src='$src' height=''></a>";
            }
            else $description = "Barcode image not yet available.";
            $description .= "<br>&nbsp;<br>";
        }
        else $description = "";

        if($species_level)
        {
            if($public_barcodes > 0)
            {    
                $url = "http://www.boldsystems.org/pcontr.php?action=doPublicSequenceDownload&taxids=$taxid";
                $arr = self::get_text_dna_sequence($url);
                $count_sequence     = $arr[0];
                $text_dna_sequence  = $arr[1];
                $url_fasta_file     = $arr[2];
                print "\n [$public_barcodes]=[$count_sequence] \n ";
                $str = "";
                if($count_sequence > 0)
                {
                    if($count_sequence == 1)$str="There is 1 barcode sequence available from BOLD and GenBank. 
                                            Below is the sequence of the barcode region Cytochrome oxidase subunit 1 (COI or COX1) from a member of the species.
                                            See the <a target='BOLDSys' href='$dc_source'>BOLD taxonomy browser</a> for more complete information about this specimen.
                                            Other sequences that do not yet meet barcode criteria may also be available.";

                    else                    $str="There are $count_sequence barcode sequences available from BOLD and GenBank.
                                            Below is a sequence of the barcode region Cytochrome oxidase subunit 1 (COI or COX1) from a member of the species.
                                            See the <a target='BOLDSys' href='$dc_source'>BOLD taxonomy browser</a> for more complete information about this specimen and other sequences.";
                    $str .= "<br>&nbsp;<br>";
                    $text_dna_sequence .= "<br>-- end --<br>";
                }
            }
            else $text_dna_sequence = "";

            if(trim($text_dna_sequence) != "")
            {
                $temp = "$str ";
                $temp .= "<div style='font-size : x-small;overflow : scroll;'> $text_dna_sequence </div>";
                /* one-click         
                $url_fasta_file = "http://services.eol.org/eol_php_code/applications/barcode/get_text_dna_sequence.php?taxid=$taxid";
                */
                /* 2-click per PL advice */
                $url_fasta_file = "http://www.boldsystems.org/pcontr.php?action=doPublicSequenceDownload&taxids=$taxid";
                $temp .= "<br><a target='fasta' href='$url_fasta_file'>Download FASTA File</a>";
            }
            else
            {
                $temp = "No available public DNA sequences <br>";
                return false;
            }
        }//if($species_level)
        else
        {
            /* 2-click per PL advice */
            $url_fasta_file = "http://www.boldsystems.org/pcontr.php?action=doPublicSequenceDownload&taxids=$taxid";
            $temp = "<a target='fasta' href='$url_fasta_file'>Download FASTA File</a>";
        }
        $description .= $temp;
        //end get text dna sequence
        return $description;
    }

    private function barcode_image_available($src)
    {
        $str = Functions::get_remote_file($src);
        /*
        ERROR: Only species level taxids are accepted
        ERROR: Unable to retrieve sequence
        */
        $ans = stripos($str, "ERROR:");
        if(is_numeric($ans)) return false;
        else                 return true;
    }

    private function get_text_dna_sequence($url)
    {
        $str = Functions::get_remote_file($url);
        if(preg_match("/\.\.\/temp\/(.*?)fasta\.fas/ims", $str, $matches)) $folder = $matches[1];
        $str = "";
        if($folder != "")
        {
            $url="http://www.boldsystems.org/temp/" . $folder . "/fasta.fas";
            $str = Functions::get_remote_file($url);
        }

        //start get only 2 sequence 
        /* working but we will not get the first 2 sequence anymore
        if($str)
        {   $found=0;
            $str=trim($str);
            for ($i = 0; $i < strlen($str); $i++) 
            {
                if(substr($str,$i,1) == ">")$found++;
                if($found == 3)break;
            }
            $str = substr($str,0,$i-1);
        }
        */
        //end get only 2 sequence

        $count_sequence = substr_count($str, '>');    
        //start get the single sequence = longest, with least N char
        $best_sequence = self::get_best_sequence($str);    
        //end

        $arr = array();
        $arr[] = $count_sequence;
        $arr[] = $best_sequence;
        $arr[] = $url;
        return $arr;
    }

    private function get_best_sequence($str)
    {
        $str = str_ireplace('>', '&arr[]=', $str);
        $arr = array();
        parse_str($str);
        if(count($arr) > 0)
        {
            $biggest = 0;
            $index_with_longest_txt = 0;
            for ($i = 0; $i < count($arr); $i++) 
            {
                $dna = trim($arr[$i]);
                $pos = strrpos($dna, "|");
                $new_dna = trim(substr($dna, $pos+1, strlen($dna)));
                $new_dna = str_ireplace(array("-", " "), "", $new_dna);
                $len_new_dna = strlen($new_dna);
                if($biggest < $len_new_dna)
                {
                    $biggest = $len_new_dna;
                    $index_with_longest_txt = $i;
                }
            }
            return $arr[$index_with_longest_txt];
        }
        else return "";
    }

}
?>
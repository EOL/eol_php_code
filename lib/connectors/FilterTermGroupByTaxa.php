<?php
namespace php_active_record;
/* connector: called from DwCA_Utility.php, which is called from filter_term_group_by_taxa.php
from: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65425&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65425
*/
class FilterTermGroupByTaxa
{
    function __construct($archive_builder, $resource_id, $params)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->params = $params;
        // $this->download_options = array('resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
    }
    /*================================================================= STARTS HERE ======================================================================*/
    private function get_children_of_taxa_group($taxon_ids)
    {
        require_library('connectors/PaleoDBAPI_v2');
        $func = new PaleoDBAPI_v2("");
        $dwca_file = CONTENT_RESOURCE_LOCAL_PATH . $this->params['source'] . ".tar.gz"; //617_ENV.tar.gz
        $descendant_taxon_ids = $func->get_descendants_given_parent_ids($dwca_file, $taxon_ids);
        return $descendant_taxon_ids;
    }
    function start($info)
    {
        // print_r($this->params); exit("\n");
        /*Array(
            [source] => 617_ENV
            [target] => wikipedia_en_traits_FTG
            [taxonIDs] => Q1390, Q1357, Q10908
        )
        e.g. taxonIDs is insects, spiders, amphibians
        */
        //----------------------------------------------------------------------------------------------
        $this->children_of_IDs = array();
        $taxonIDs = explode(',', $this->params['taxonIDs']);
        $children = self::get_children_of_taxa_group($taxonIDs); //e.g. $taxonIDs is insects = Q1390 | spiders = Q1357 | amphibians = Q10908
        foreach($children as $child) $this->children_of_IDs[$child] = '';
        unset($children);
        // print_r($this->children_of_IDs);
        echo "\nChildren of IDs: ".count($this->children_of_IDs)."\n"; exit;
        //----------------------------------------------------------------------------------------------
        
        $tables = $info['harvester']->tables;
        
        self::process_generic_table_for_Ostracoda($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'occurrence');
        self::process_generic_table_for_Ostracoda($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'MoF');
        
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'taxon');
        self::process_generic_table($tables['http://rs.gbif.org/terms/1.0/vernacularname'][0], 'vernacular');
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'occurrence');
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'MoF');
    }
    private function process_generic_table_for_Ostracoda($meta, $what)
    {   //print_r($meta);
        echo "\nprocess $what...Ostracoda\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            if($what == 'occurrence') {
                if(isset($this->children_of_Ostracoda[$rec['http://rs.tdwg.org/dwc/terms/taxonID']])) {
                    $this->occurrence_id_Ostracoda[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = '';
                }
            }
            elseif($what == 'MoF') {
                if(isset($this->occurrence_id_Ostracoda[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']])) {
                    /* per: https://eol-jira.bibalex.org/browse/DATA-1831?focusedCommentId=64595&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64595
                    For all descendants of Ostracoda, (FurtherInformationURL=https://paleobiodb.org/classic/checkTaxonInfo?is_real_user=1&taxon_no=22826)
                    please remove all records with measurementType= http://purl.obolibrary.org/obo/RO_0002303
                    */
                    if($rec['http://rs.tdwg.org/dwc/terms/measurementType'] == 'http://purl.obolibrary.org/obo/RO_0002303') {
                        $this->Ostracoda_remove_occurrence_id[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = '';
                    }
                }
            }
        }
    }

    private function process_generic_table($meta, $what)
    {   //print_r($meta);
        echo "\nprocess $what...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /**/
            
            if($what == 'taxon') {
                if(isset($this->children_of_Aves[$rec['http://rs.tdwg.org/dwc/terms/taxonID']])) continue;
                // if(isset($this->children_of_Aves[$rec['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID']])) continue; --- COMMMENT THIS - VERY WRONG.
                if(isset($this->children_of_Aves[$rec['http://rs.tdwg.org/dwc/terms/parentNameUsageID']])) continue;
                $o = new \eol_schema\Taxon();
            }
            elseif($what == 'vernacular') {
                if(isset($this->children_of_Aves[$rec['http://rs.tdwg.org/dwc/terms/taxonID']])) continue;
                $o = new \eol_schema\VernacularName();
            }
            elseif($what == 'occurrence') {
                if(isset($this->children_of_Aves[$rec['http://rs.tdwg.org/dwc/terms/taxonID']])) {
                    $this->remove_occurrence_id[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']] = '';
                    continue;
                }
                if(isset($this->Ostracoda_remove_occurrence_id[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']])) continue;
                $o = new \eol_schema\Occurrence();
            }
            elseif($what == 'MoF') {
                if(isset($this->remove_occurrence_id[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']])) continue;
                if(isset($this->Ostracoda_remove_occurrence_id[$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']])) continue;
                $o = new \eol_schema\MeasurementOrFact_specific();
            }
            else exit("\nInvestigate [$what]\n");
            
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>

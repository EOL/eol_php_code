<?php
namespace php_active_record;
/* connector for EMBLreptiles
SPG bought their CD. BIG exports their data from the CD to a spreadsheet.
This connector processes the spreadsheet.
Estimated execution time: 6 minutes

bad char in <dc:description>:
<dc:identifier>rdb_Agama_finchi_BÖHME,_WAGNER,_MALONZA,_LÖTTERS_&amp;_KÖHLER_2005</dc:identifier>
<dc:identifier>rdb_Boiga_bengkuluensis_ORLOV,_KUDRYAVTZEV,_RYABOV_&amp;_SHUMAKOV_2003</dc:identifier>
<dc:identifier>rdb_Trioceros_harennae_LARGEN_1995</dc:identifier>

<taxon>
  <dc:identifier>rdb_Plestiodon_gilberti_VAN_DENBURGH_1896</dc:identifier>
  <dc:source>http://reptile-database.reptarium.cz/species?genus=Plestiodon&amp;species=gilberti</dc:source>
  <dwc:Family>Scincidae</dwc:Family>
  <dwc:Genus>Plestiodon</dwc:Genus>
  <dwc:ScientificName>Plestiodon gilberti VAN DENBURGH 1896</dwc:ScientificName>
<commonName xml:lang="en">arizonensis: Arizona Skink</commonName>
<commonName xml:lang="en">cancellosus: Variegated Skink</commonName>
<commonName xml:lang="en">gilberti: Greater Brown Skink</commonName>
<commonName xml:lang="en">placerensis: Northern Brown Skink</commonName>
<commonName xml:lang="en">rubricaudatus: Western Redtail Skink</commonName> -- this common name has bad char

Open the 306.xml with TextEdit and copy the bad char, use str_replace() to remove them.

*/

include_once(dirname(__FILE__) . "/../../config/environment.php");

// /*
$timestart = time_elapsed();
require_library('connectors/EMBLreptiles');
$resource_id = 306;
$func = new EMBLreptiles();
$taxa = $func->get_all_taxa($resource_id);
$xml = \SchemaDocument::get_taxon_xml($taxa);

$xml = str_replace(" ", "", $xml);
$xml = str_replace("", "", $xml);
$xml = str_replace("", "", $xml);
$xml = str_replace("", "", $xml);

$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
if(!($OUT = Functions::file_open($resource_path, "w"))) return;
fwrite($OUT, $xml);
fclose($OUT);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec . " seconds   \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
echo "\n\n Done processing.";
// */

//now convert XML to DWC-A
Functions::gzip_resource_xml($resource_id);
$params["eol_xml_file"] = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml.gz";
$params["filename"]     = $resource_id . ".xml";
$params["dataset"]      = "Reptile DB";
$params["resource_id"]  = $resource_id;
require_library('connectors/ConvertEOLtoDWCaAPI');
$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params);
Functions::finalize_dwca_resource($resource_id);

// list_all_vernaculars(); //a utility; works OK
unlink(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml"); //unlink 306.xml; comment if you want to debug XML.
unlink(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml.gz"); //unlink 306.xml.gz; comment if you want to debug XML.

function list_all_vernaculars()
{
    $arr = array();
    $xml = simplexml_load_file(CONTENT_RESOURCE_LOCAL_PATH . "306.xml");
    foreach($xml->taxon as $t)
    {
        $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
        echo "\n" . $t_dwc->ScientificName;
        foreach($t->commonName as $name)
        {
            $name = (string) $name;
            echo "\n - " . $name;
            $arr[$name] = '';
        }
    }
    //write to text file
    $OUT = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . "306_vernaculars.txt", "w");
    foreach(array_keys($arr) as $name) fwrite($OUT, $name . "\n");
    fclose($OUT);
    echo "\nText file created.\n";
}

function fix_latin1_mangled_with_utf8_maybe_hopefully_most_of_the_time($str)
{
    /*
    to do:
    also a good source of char table: http://lwp.interglacial.com/appf_01.htm
    iconv -f ASCII -t utf-8//IGNORE < 306.xml > /306new.xml
    iconv -f utf-8 -t utf-8//IGNORE < 306.xml > /306new.xml
    */
}

?>

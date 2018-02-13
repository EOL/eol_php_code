<?php
namespace php_active_record;
/* 
http://www.eol.org/content_partners/33/resources/40 - https://eol-jira.bibalex.org/browse/DATA-1735
This is a generic script that will convert EOL XML to EOL DWC-A
*/

echo "\nUsed [/connectors/collections_generic.php] instead\n"; //since the numbers from XML is lower than what is published in EOL - 
// https://eol-jira.bibalex.org/browse/DATA-1735?focusedCommentId=62087&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62087
return;

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConvertEOLtoDWCaAPI');
$timestart = time_elapsed();

$resource_id = 40;
$params["eol_xml_file"] = "";
$params["eol_xml_file"] = "https://opendata.eol.org/dataset/cf4c5598-3a7c-464d-be87-d72bc98b066e/resource/888c78b3-04df-4e3b-97f3-f71b52b38239/download/anagetext.xml";
$params["filename"]     = "no need to mention here.xml";
$params["dataset"]      = "EOL XML files";
$params["resource_id"]  = $resource_id;

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, true, 60*60*24*25); // true => means it is an XML file, not an archive file nor a zip file. Expires in 25 days.
Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
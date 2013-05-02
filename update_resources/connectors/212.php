<?php
namespace php_active_record;
/* connector for BOLD Systems -- species-level taxa
estimated execution time 1.5 | 7.2 hours
Partner provides XML service and a big XML file
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/BOLDSysAPI');
$resource_id = 212;

$folder = DOC_ROOT . "update_resources/connectors/files/BOLD";
if(!file_exists($folder)) mkdir($folder , 0777);

$bolds = new BOLDSysAPI();

// /* This will store DNA sequence on a json file, un-comment if u want this re-created everytime you run 212.php. Will last around 57 mins. excluding the time downloading the big dump file from BOLDS
$bolds->save_dna_sequence_from_big_xml(); 
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done save_dna_sequence_from_big_xml() \n\n";
// */

$bolds->initialize_text_files(); // not commented on regular operation
$bolds->start_process($resource_id, false);

Functions::set_resource_status_to_force_harvest($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing. \n";
?>
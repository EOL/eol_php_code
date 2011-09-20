<?php
namespace php_active_record;
/* connector for BOLDS
estimated execution time:
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/BoldsAPI');
$resource_id = 81; // orig 81;

if(isset($argv[1])) $call_multiple_instance = false;
else $call_multiple_instance = true;

BoldsAPI::start_process($resource_id, $call_multiple_instance);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes   \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
exit("\n\n Done processing.");
?>
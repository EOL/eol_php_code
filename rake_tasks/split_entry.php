<?php

include_once(dirname(__FILE__) . "/../config/environment.php");

include_once(dirname(__FILE__) . "/../config/environment.php");
include_once(dirname(__FILE__) . "/../lib/SplitEntryHandler.php");

SplitEntryHandler::split_entry(array('hierarchy_entry_id'     => @$argv[1],
                                     'bad_hierarchy_entry_id' => @$argv[2],
                                     'confirmed'              => @$argv[3],
                                     'reindex'                => @trim($argv[4])));


?>


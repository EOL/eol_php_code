<?php

//exit;

$path = "";
define('DEBUG', true);
//define('MYSQL_DEBUG', true);
//define('DEBUG_TO_FILE', true);
if(preg_match("/^(.*\/)[^\/]+/", $_SERVER["_"], $arr)) $path = $arr[1];
include_once($path."../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];




$mysqli->begin_transaction();

$i = 0;
$result = $mysqli->query("SELECT id, object_cache_url FROM data_objects WHERE object_cache_url IS NOT NULL AND object_cache_url!=0");
while($result && $row=$result->fetch_assoc())
{
    if($i % 50000 == 0) Functions::debug($i);
    $i++;
    
    $id = $row["id"];
    $object_cache_url = $row["object_cache_url"];
    
    if(!file_exists(CONTENT_LOCAL_PATH . get_path($object_cache_url) ."_large.jpg"))
    {
        Functions::debug("$id - $object_cache_url");
        //DataObject::delete($id);
    }
}

$result = $mysqli->query("SELECT id FROM data_objects WHERE data_type_id=1 AND (object_cache_url IS NULL OR object_cache_url=0)");
while($result && $row=$result->fetch_assoc())
{
    $id = $row["id"];
    
    Functions::debug("$id");
    //DataObject::delete($id);
}


function get_path($object_cache_url)
{
    return substr($object_cache_url, 0, 4)."/".substr($object_cache_url, 4, 2)."/".substr($object_cache_url, 6, 2)."/".substr($object_cache_url, 8, 2)."/".substr($object_cache_url, 10, 5);
}


//$mysqli->end_transaction();

?>
<?php
$postfix = "_getHC";
$str = "Copy 'Build status' and send to <i>eagbayani@eol.org</i>. &nbsp; Or you can try it again when system resources are free. &nbsp; <a href='index.php'>&lt;&lt; Back to main</a>";
// the $build_status should come from the status for uuid in question not just the currently last_build
$build_status = $ctrler->get_last_build_console_text($task, $params['uuid'].$postfix);
if($ctrler->did_build_fail($build_status)) {
    $ctrler->display_message(array('type' => "error", 'msg' => "Process failed. &nbsp; $str"));
}
elseif($ctrler->is_build_currently_running($build_status)) {
    $ctrler->display_message(array('type' => "highlight", 'msg' => "Processing..."));
    $ctrler->display_message(array('type' => "highlight", 'msg' => "Please check back later. &nbsp; You can use this <a href='task_status.php?task=$task&uuid=$params[uuid]&destination=".urlencode($params['destination'])."'>link to check status</a> anytime."));
    // return;
}
else {
    if(file_exists($params['destination']) && filesize($params['destination'])) $ctrler->display_message(array('type' => "highlight", 'msg' => "Job completed OK."));
    else $ctrler->display_message(array('type' => "highlight", 'msg' => "Build is in unknown state. &nbsp; $str"));
}
if($build_status) echo "<hr><b>Build status:</b><pre>".$build_status."</pre><hr>";
else
{
    if($ctrler->is_task_in_queue("genHigherClass_job", $params['uuid'].$postfix)) {
        echo "<hr><b>Build status:</b><pre>This job is now in queue...</pre><hr>";
    }
    else echo "<hr><b>Build status:</b><pre>Preparing files...</pre><hr>";
}

if($build_status) {
    if($ctrler->is_build_aborted($build_status)) echo "<p>Process aborted. &nbsp; <a href='index.php'>&lt;&lt; Back to main</a>";
}

?>

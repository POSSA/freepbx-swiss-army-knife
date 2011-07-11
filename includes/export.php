<?PHP
$sql = 'SELECT `name` FROM `outbound_routes` WHERE `route_id` = '.$_REQUEST['orid'];
$name = $db->getOne($sql);

header("Content-type: text/csv");
header('Content-Disposition: attachment; filename="freepbx_outboundroutes_'.$name.'.csv"');

$outstream = fopen("php://output",'w');

//prepend,prefix,match pattern,callerid
$sql = 'SELECT `prepend_digits`, `match_pattern_prefix` , `match_pattern_pass` , `match_cid`  FROM `outbound_route_patterns` WHERE `route_id` = '. $_REQUEST['orid'];

$result = $db->getAll($sql,array(),DB_FETCHMODE_ASSOC);

foreach($result as $row) {
	fputcsv($outstream, $row);
}
fclose($outstream);
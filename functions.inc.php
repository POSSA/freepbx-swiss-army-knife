<?php

function sak_hook_core($viewing_itemid, $target_menuid) {
	global $db;
	$sak_settings =& $db->getAssoc("SELECT var_name, value FROM sak_settings");
	$html = '';
	
	
	if ($target_menuid == 'routing') {
		$html .= '<tr><td colspan="2">&nbsp;</td></tr>';
		if($sak_settings['dial_plan']) {
			//$html .= '<tr><td colspan="2"><h5>';
			//$html .= _("Bulk Dial Patterns");
			//$html .= '<hr></h5></td></tr>';
			$html .= '<tr><td colspan="2">This Effectively Disables the \'Dial Plan Wizard\' Below. <br/>Entering Anything in the \'Dial Plan Wizard\' will be ignored</td></tr>';
			$html .= '<tr>';
			$html .= '<td><a href="#" class="info">';
			$html .= _("Source").'<span>'._("Each Pattern Should Be Entered On A New Line").'.</span></a>:</td>';
			$html .= '<td><textarea name="bulk_patterns" rows="10" cols="40">';
			if(isset($_REQUEST['extdisplay']) && $_REQUEST['extdisplay'] != '') {
				$dial_patterns = core_routing_getroutepatternsbyid($_REQUEST['extdisplay']);
				foreach ($dial_patterns as $row) {
					$prepend = ($row['prepend_digits'] != '') ? $row['prepend_digits'].'+' : '';
					$match_pattern_prefix = ($row['match_pattern_prefix'] != '') ? $row['match_pattern_prefix'].'|' : '';
					$match_cid = ($row['match_cid'] != '') ? '/'.$row['match_cid'] : '';

					$html .= $prepend . $match_pattern_prefix . $row['match_pattern_pass'] . $match_cid . "\n";
				}
			}
			$html .= '</textarea></td></tr>';
			$html .= '<tr><td colspan="2">&nbsp;</td></tr>';
		}
		if(($sak_settings['dial_plan_exp']) && ($viewing_itemid != '')) {
			//$html .= '<tr><td colspan="2"><h5>';
			//$html .= _("Export Dial Patterns");
			//$html .= '<hr></h5></td></tr>';
			$html .= '<tr><td colspan="2"><a href="config.php?type=tool&amp;display=sak_advanced_settings&amp;quietmode=1&amp;orid='.$viewing_itemid.'" target="_blank">Click Here to Export All Dial Patterns for this Route</a>';
			$html .= '</td></tr>';
			$html .= '<tr><td colspan="2">&nbsp;</td></tr>';
		}
	}
	return $html;
}

function sak_hookProcess_core($viewing_itemid, $request) {
	global $db;
	$sak_settings =& $db->getAssoc("SELECT var_name, value FROM sak_settings");
	if (($request['display'] == 'routing') && ($sak_settings['dial_plan']) && (isset($request['bulk_patterns'])))	{		
		$_POST['pattern_pass'] = "";
		$data = explode("\n",$request['bulk_patterns']);
		
		$prepend = '/^([^+]*)\+/';
		$prefix = '/^([^|]*)\|/';
		$match_pattern = '/([^/]*)/';
		$callerid = '/\/(.*)$/';

		$i = 0;

		foreach($data as $list) {
			if (preg_match('/^\s*$/', $list)) {
				continue;
			}

			$pp[$i] = $pf[$i] = $cid[$i] = '';

			if (preg_match($prepend, $list, $matches)) {
				$pp[$i] = $matches[1];
				$list = preg_replace($prepend, '', $list);
			}
			
			if (preg_match($prefix, $list, $matches)) {
				$pf[$i] = $matches[1];
				$list = preg_replace($prefix, '', $list);
			}
			
			if (preg_match($callerid, $list, $matches)) {
				$cid[$i] = $matches[1];
				$list = preg_replace($callerid, '', $list);
			}
			
			$mp[$i] = $list;

			$i++;
		}

		$_POST['prepend_digit'] = $pp;
		$_POST['pattern_prefix'] = $pf;
		$_POST['pattern_pass'] = $mp;
		$_POST['match_cid'] = $cid;

		/*
		$sql = 'DELETE FROM `outbound_route_patterns` WHERE `outbound_route_patterns`.`route_id` = '.$_REQUEST['extdisplay'];
		$db->query($sql);
		
		foreach($data as $value){
			$sql = "INSERT INTO outbound_route_patterns (route_id, match_pattern_pass) VALUES ('".$_REQUEST['extdisplay']."','".$value."')";
			$db->query($sql);
		}
				*/
	}
}

function sak_blacklist_list() {
	global $amp_conf;
	global $astman;

$ast_ge_16 =  version_compare($amp_conf['ASTVERSION'], "1.6", "ge");
        if ($astman) {
		$list = $astman->database_show('blacklist');
		if($ast_ge_16) {
		    foreach ($list as $k => $v) {
			$numbers = substr($k, 11);
			$blacklisted[] = array('number' => $numbers, 'description' => $v);
			}
		    if (isset($blacklisted) && is_array($blacklisted))
			// Why this sorting? When used it does not yield the result I want
			//    natsort($blacklisted);
		    return isset($blacklisted)?$blacklisted:null;
		} else {
		    foreach ($list as $k => $v) {
			$numbers[substr($k, 11)] = substr($k, 11);
			}
			if (isset($numbers) && is_array($numbers))
			    natcasesort($numbers);
			return isset($numbers)?$numbers:null;
			}
        } else {
                fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
        }
}

function sak_blacklist_del($number){
	global $amp_conf;
	global $astman;
	if ($astman) {
		$astman->database_del("blacklist",$number);
	} else {
		fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}
}

function sak_blacklist_add($post){
	global $amp_conf;
	global $astman;

$ast_ge_16 =  version_compare($amp_conf['ASTVERSION'], "1.6", "ge");

	if(!sak_blacklist_chk($post))
		return false;

	extract($post);
	if ($astman) {
		if ($ast_ge_16) {
		$post['description']==""?$post['description'] = '1':$post['description'];
		$astman->database_put("blacklist",$post['number'], '"'.$post['description'].'"');
		    } else {
		    	    $astman->database_put("blacklist",$number, '1');
		    	    }
		// Remove filtering for blocked/unknown cid
		$astman->database_del("blacklist","blocked");
		// Add it back if it's checked
		if($post['blocked'] == "1")  {
			$astman->database_put("blacklist","blocked", "1");
			needreload();
		}
	} else {
		fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}
}


// ensures post vars is valid
function sak_blacklist_chk($post){
	return true;
}

?>

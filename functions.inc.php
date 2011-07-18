<?php


function sak_hook_core($viewing_itemid, $target_menuid) {
	global $db;
	$sak_settings =& $db->getAssoc("SELECT var_name, value FROM sak_settings");
	$html = '';
	$new_dial_patterns_section = '';
	$extdisplay=isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:'';
	$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';

	if ($target_menuid == 'trunks') {

	}

	if ($target_menuid == 'routing') {
		$html .= '<tr><td colspan="2">&nbsp;</td></tr>';
		if($sak_settings['dial_plan']) {
			//$html .= '<tr><td colspan="2"><h5>';
			//$html .= _("Bulk Dial Patterns");
			//$html .= '<hr></h5></td></tr>';
			$dp_html .= '<tr>';
			$dp_html .= '<td><a href="#" class="info">';
			$dp_html .= _("Source").'<span>'._("Each Pattern Should Be Entered On A New Line").'.</span></a>:</td>';
			$dp_html .= '<td><textarea name="bulk_patterns" id="bulk_patterns" rows="10" cols="40">';

			$dialpattern_list = array();
			if ($_REQUEST['bulk_patterns']) {
				$dialpattern_list = split("\n", trim($_REQUEST['bulk_patterns']));
			} else if($extdisplay != '') {
				$dial_patterns = core_routing_getroutepatternsbyid($_REQUEST['extdisplay']);
				foreach ($dial_patterns as $row) {
					$prepend = ($row['prepend_digits'] != '') ? $row['prepend_digits'].'+' : '';
					$match_pattern_prefix = ($row['match_pattern_prefix'] != '') ? $row['match_pattern_prefix'].'|' : '';
					$match_cid = ($row['match_cid'] != '') ? '/'.$row['match_cid'] : '';

					$dialpattern_list[] = $prepend . $match_pattern_prefix . $row['match_pattern_pass'] . $match_cid;
				}
			}

			// Duplicated from core/page.routing.php
			// Unfortunately those values are not obtainable here
			if ($action == 'populatenpanxx') {
                if (preg_match("/^([2-9]\d\d)-?([2-9]\d\d)$/", $_REQUEST["npanxx"], $matches)) {
                    // first thing we do is grab the exch:
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_URL, "http://www.localcallingguide.com/xmllocalprefix.php?npa=".$matches[1]."&nxx=".$matches[2]);
                    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; FreePBX Local Trunks Configuration)");
                    $str = curl_exec($ch);
                    curl_close($ch);

                    // quick 'n dirty - nabbed from PEAR
                    require_once($GLOBALS['amp_conf']['AMPWEBROOT'] . '/admin/modules/core/XML_Parser.php');
                    require_once($GLOBALS['amp_conf']['AMPWEBROOT'] . '/admin/modules/core/XML_Unserializer.php');

                    $xml = new xml_unserializer;
                    $xml->unserialize($str);
                    $xmldata = $xml->getUnserializedData();

                    $hash_filter = array(); //avoid duplicates
                    if (isset($xmldata['lca-data']['prefix'])) {
                        // we do the loops separately so patterns are grouped together
                        
                        // match 1+NPA+NXX (dropping 1)
                        foreach ($xmldata['lca-data']['prefix'] as $prefix) {
                          if (isset($hash_filter['1'.$prefix['npa'].$prefix['nxx']])) {
                            continue;
                          } else {
                            $hash_filter['1'.$prefix['npa'].$prefix['nxx']] = true;
                          }
                          $dialpattern_list[] = '1'.htmlspecialchars($prefix['npa'].$prefix['nxx']).'XXXX';
                        }
                        // match NPA+NXX
                        foreach ($xmldata['lca-data']['prefix'] as $prefix) {
                          if (isset($hash_filter[$prefix['npa'].$prefix['nxx']])) {
                            continue;
                          } else {
                            $hash_filter[$prefix['npa'].$prefix['nxx']] = true;
                          }
                          $dialpattern_list[] = htmlspecialchars($prefix['npa'].$prefix['nxx']).'XXXX';
                        }
                        // match 7-digits
                        foreach ($xmldata['lca-data']['prefix'] as $prefix) {
                          if (isset($hash_filter[$prefix['nxx']])) {
                            continue;
                          } else {
                            $hash_filter[$prefix['nxx']] = true;
                          }
                          $dialpattern_list[] = htmlspecialchars($prefix['nxx']).'XXXX';
                        }
                        unset($hash_filter);
                    } else {
                        //$errormsg = _("Error fetching prefix list for: "). $_REQUEST["npanxx"];
                    }
                } else {
                    // Will get caught the second time loaded
                    // what a horrible error message... :p
                    //$errormsg = _("Invalid format for NPA-NXX code (must be format: NXXNXX)");
                }
        
                // Will get caught the second time loaded
                /*if (isset($errormsg)) {
                    echo "<script language=\"javascript\">alert('".addslashes($errormsg)."');</script>";
                    unset($errormsg);
                }*/
			}

			$dp_html .= implode("\n", $dialpattern_list)."\n";
			$dp_html .= '</textarea></td></tr>';
			$dp_html .= '<tr><td colspan="2">&nbsp;</td></tr>';

			$pat_local = _("NXXXXXX");
			$pat_local10 = _("NXXXXXX,NXXNXXXXXX");
			$pat_tollfree = _("1800NXXXXXX,1888NXXXXXX,1877NXXXXXX,1866NXXXXXX,1855NXXXXXX");
			$pat_ld = _("1NXXNXXXXXX");
			$pat_int = _("011.");
			$pat_info = _("411,311");
			$pat_emerg = _("911");
			//$html .= $dp_html;
			$new_dial_patterns_section .= '<tr><td><a href="#" class="info">Source<span>Each Pattern Should Be Entered On A New Line.</span></a>:</td><td><textarea name="bulk_patterns" id="bulk_patterns" rows="10" cols="40">'.implode("\\n", $dialpattern_list).'</textarea></td></tr><tr><td colspan="2">&nbsp;</td></tr>';
			$html .= <<<xENDx
<script type="text/javascript">
	function insertIntoBulkPatterns() {
		// Mostly copied from core/page.routing.php
		code = document.getElementById('inscode').value;
		insert = '';
		switch(code) {
			case "local":
				insert = '{$pat_local}';
			break;
			case "local10":
				insert = '{$pat_local10}';
			break;
			case 'tollfree':
				insert = '{$pat_tollfree}';
			break;
			case "ld":
				insert = '{$pat_ld}';
			break;
			case "int":
				insert = '{$pat_int}';
			break;
			case 'info':
				insert = '{$pat_info}';
			break;
			case 'emerg':
				insert = '{$pat_emerg}';
			break;
			case 'lookup':
				populateLookup();
				insert = '';
			break;
			case 'csv':
				$('#pattern_file').show().click();
				return true;
			break;
		}

		$('#bulk_patterns').val($('#bulk_patterns').val()+insert.split(',').join("\\n")+"\\n");
	}
	
	

	$(document).ready(function(){
		addCustomField('X','X','X','X');
		$('#dial-pattern-add').hide();
		$('#inscode').attr('onChange', '');
		$('#inscode').bind('change', function(){insertIntoBulkPatterns();});
	});
	
	//Hijack into submit to combine the old dial patterns with the new in the future. Could still technically do both with server-side
	$('#routeEdit').submit(function() {
	  return true;
	});
</script>
xENDx;
		}
		if(($sak_settings['dial_plan_exp']) && ($viewing_itemid != '')) {
			$new_dial_patterns_section .= '<tr><td colspan="2">&nbsp;</td></tr><tr><td colspan="2"><a href="config.php?type=tool&amp;display=sak_advanced_settings&amp;quietmode=1&amp;orid='.$viewing_itemid.'" target="_blank">Click Here to Export All Dial Patterns for this Route</a></td></tr>';
		}
	}
	if($new_dial_patterns_section != '') {
		$type = $sak_settings['dial_plan'] ? 'replaceWith' : 'append';
		$html .= "<script type=\"text/javascript\">
			$(document).ready(function(){
				$('.dialpatterns').".$type."('".$new_dial_patterns_section."');
			});
		</script>";		
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

function sak_hookGet_config($engine) {
	// TODO: integrating with direct extension <-> DID association
	// TODO: add option to avoid callerid lookup if the telco already supply a callerid name (GosubIf)
	global $ext;  // is this the best way to pass this?

	switch($engine) {	
		case "asterisk":
			// Code from modules/core/functions.inc.php core_get_config inbound routes
			$didlist = core_did_list();
			if (is_array($didlist)) {
				foreach ($didlist as $item) {

					$exten = trim($item['extension']);
					$cidnum = trim($item['cidnum']);

					if ($cidnum != '' && $exten == '') {
						$exten = 's';
						$pricid = ($item['pricid']) ? true:false;
					} else if (($cidnum != '' && $exten != '') || ($cidnum == '' && $exten == '')) {
						$pricid = true;
					} else {
						$pricid = false;
					}
					$context = ($pricid) ? "ext-did-0001":"ext-did-0002";

					$exten = (empty($exten)?"s":$exten);
					$exten = $exten.(empty($cidnum)?"":"/".$cidnum); //if a CID num is defined, add it

					$ext->splice($context, $exten, 2, new ext_agi(dirname(__FILE__).'/agi/bwlist.agi'));				}
			} // else no DID's defined. Not even a catchall.
			break;
	}
}
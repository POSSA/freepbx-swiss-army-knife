<?php
global $amp_conf;
define("LOCAL_PATH", $amp_conf['AMPWEBROOT'].'/admin/modules/sak/');

if (! function_exists("out")) {
    function out($text) {
        echo $text."<br />";
    }
}

if (! function_exists("outn")) {
    function outn($text) {
        echo $text;
    }
}

$epm_module_xml = sak_install_xml2array(LOCAL_PATH."module.xml");

$version = $epm_module_xml['module']['version'];

$sql = 'SELECT `version` FROM `modules` WHERE `modulename` = CONVERT(_utf8 \'sak\' USING latin1) COLLATE latin1_swedish_ci';

$db_version = $db->getOne($sql);

$new_install = FALSE;

if($db_version == '') {
	$new_install = TRUE;
} else {
	$ver = $db_version;
}

if($new_install) {
    out('New Installation Detected!');
	out("Creating Settings Table");
	$sql = "CREATE TABLE IF NOT EXISTS `sak_settings` (
	              `idnum` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Index',
	              `var_name` varchar(25) NOT NULL COMMENT 'Variable Name',
	              `value` text NOT NULL COMMENT 'Data',
	              PRIMARY KEY (`idnum`),
	              UNIQUE KEY `var_name` (`var_name`)
	            ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=17";
	$db->query($sql);

	out("Inserting data into the Settings Table");
	$sql = "INSERT INTO `sak_settings` (`idnum`, `var_name`, `value`) VALUES
	        (1, 'dial_plan', '0'),
	        (2, 'dial_plan_exp', '0')";
	$db->query($sql);
	
	out('Creating New Black/White List Table');
	$sql = "CREATE TABLE IF NOT EXISTS `sak_bwlist` (
	  `id` int(11) NOT NULL auto_increment,
	  `nn` varchar(50) NOT NULL,
	  `permit` int(1) NOT NULL,
	  `sort` int(11) NOT NULL,
	  `count` int(11) NOT NULL default '0',
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10";
	$db->query($sql);

    out('Inserting Data Into Table');
	$sql = "INSERT INTO `sak_bwlist` (`id`, `nn`, `permit`, `sort`, `count`) VALUES (0, '.*', 1, 0, 0)";
	$db->query($sql);
	
	$sql = 'UPDATE `asterisk`.`sak_bwlist` SET `id` = \'0\' WHERE `sak_bwlist`.`nn` = \'.*\' LIMIT 1;';
	$db->query($sql);
} elseif($ver < '2.0') {
	out('Version Identified as '. $ver);
    
    out('Creating New Black/White List Table');
	$sql = "CREATE TABLE IF NOT EXISTS `sak_bwlist` (
	  `id` int(11) NOT NULL auto_increment,
	  `nn` varchar(50) NOT NULL,
	  `permit` int(1) NOT NULL,
	  `sort` int(11) NOT NULL,
	  `count` int(11) NOT NULL default '0',
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10";
	$db->query($sql);

    out('Inserting Data Into Table');
	$sql = "INSERT INTO `sak_bwlist` (`id`, `nn`, `permit`, `sort`, `count`) VALUES (0, '.*', 1, 0, 0)";
	$db->query($sql);
	
	$sql = 'UPDATE `asterisk`.`sak_bwlist` SET `id` = \'0\' WHERE `sak_bwlist`.`nn` = \'.*\' LIMIT 1;';
	$db->query($sql);
} else {
    out('Version Identified as '. $ver);
}

/*
CREATE TABLE  `asterisk`.`sak_bwlist` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`nn` VARCHAR( 50 ) NOT NULL ,
`type` VARCHAR( 10 ) NOT NULL ,
`sort` INT NOT NULL
) ENGINE = MYISAM
*/


function sak_install_xml2array($url, $get_attributes = 1, $priority = 'tag') {
    $contents = "";
    if (!function_exists('xml_parser_create')) {
        return array ();
    }
    $parser = xml_parser_create('');
    if(!($fp = @ fopen($url, 'rb'))) {
        return array ();
    }
    while(!feof($fp)) {
        $contents .= fread($fp, 8192);
    }
    fclose($fp);
    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, trim($contents), $xml_values);
    xml_parser_free($parser);
    if(!$xml_values) {
        return; //Hmm...
    }
    $xml_array = array ();
    $parents = array ();
    $opened_tags = array ();
    $arr = array ();
    $current = & $xml_array;
    $repeated_tag_index = array ();
    foreach ($xml_values as $data) {
        unset ($attributes, $value);
        extract($data);
        $result = array ();
        $attributes_data = array ();
        if (isset ($value)) {
            if($priority == 'tag') {
                $result = $value;
            }
            else {
                $result['value'] = $value;
            }
        }
        if(isset($attributes) and $get_attributes) {
            foreach($attributes as $attr => $val) {
                if($priority == 'tag') {
                    $attributes_data[$attr] = $val;
                }
                else {
                    $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                }
            }
        }
        if ($type == "open") {
            $parent[$level -1] = & $current;
            if(!is_array($current) or (!in_array($tag, array_keys($current)))) {
                $current[$tag] = $result;
                if($attributes_data) {
                    $current[$tag . '_attr'] = $attributes_data;
                }
                $repeated_tag_index[$tag . '_' . $level] = 1;
                $current = & $current[$tag];
            }
            else {
                if (isset ($current[$tag][0])) {
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    $repeated_tag_index[$tag . '_' . $level]++;
                }
                else {
                    $current[$tag] = array($current[$tag],$result);
                    $repeated_tag_index[$tag . '_' . $level] = 2;
                    if(isset($current[$tag . '_attr'])) {
                        $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                        unset ($current[$tag . '_attr']);
                    }
                }
                $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                $current = & $current[$tag][$last_item_index];
            }
        }
        else if($type == "complete") {
            if(!isset ($current[$tag])) {
                $current[$tag] = $result;
                $repeated_tag_index[$tag . '_' . $level] = 1;
                if($priority == 'tag' and $attributes_data) {
                    $current[$tag . '_attr'] = $attributes_data;
                }
            }
            else {
                if (isset ($current[$tag][0]) and is_array($current[$tag])) {
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    if ($priority == 'tag' and $get_attributes and $attributes_data) {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag . '_' . $level]++;
                }
                else {
                    $current[$tag] = array($current[$tag],$result);
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $get_attributes) {
                        if (isset ($current[$tag . '_attr'])) {
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset ($current[$tag . '_attr']);
                        }
                        if ($attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                    }
                    $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
                }
            }
        }
        else if($type == 'close') {
            $current = & $parent[$level -1];
        }
    }
    return ($xml_array);
}
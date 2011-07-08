<?php

if(isset($_REQUEST['dial_plan'])) {
	$sql = "UPDATE sak_settings SET value = '1' WHERE var_name='dial_plan'";
} else {
	$sql = "UPDATE sak_settings SET value = '0' WHERE var_name='dial_plan'";
}
$db->query($sql);

if(isset($_REQUEST['dial_plan_exp'])) {
	$sql = "UPDATE sak_settings SET value = '1' WHERE var_name='dial_plan_exp'";
} else {
	$sql = "UPDATE sak_settings SET value = '0' WHERE var_name='dial_plan_exp'";
}
$db->query($sql);

$sak_settings =& $db->getAssoc("SELECT var_name, value FROM sak_settings");
?>
<h3>FreePBX Swiss Army Knife Settings</h3>
<form name="form1" method="post" action="">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="26%">Turn On Old (Pre 2.8) Dial Plan Textbox</td>
    <td width="74%">
      <input type="checkbox" name="dial_plan" id="dial_plan" <?=$sak_settings['dial_plan'] ? 'checked' : ''?>>
    </td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td width="26%">Allow Exporting of Dial Plans (into CSV)</td>
    <td width="74%">
      <input type="checkbox" name="dial_plan_exp" id="dial_plan_exp" <?=$sak_settings['dial_plan_exp'] ? 'checked' : ''?>>
    </td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
</table>
<input type="submit" name="button" id="button" value="Submit">
</form>
<?php
if((isset($_REQUEST['submit'])) && (!empty($_REQUEST['nn']))) {
	$permit = ($_REQUEST['permit']) ? '1' : '0';

	$db->query("INSERT INTO sak_bwlist (nn, permit, sort) VALUES ('".$_REQUEST['nn']."', '".$permit."', 1)");
}

if(isset($_REQUEST['delete'])) {
	$db->query("DELETE FROM sak_bwlist WHERE id = ". $_REQUEST['delete']);
}

if(isset($_REQUEST['sortup'])) {
	$order = $db->getOne('SELECT sort FROM sak_bwlist WHERE id = '.$_REQUEST['sortup']);
	$order--;
	$db->query("UPDATE sak_bwlist SET sort = '".$order."' WHERE id = ".$_REQUEST['sortup']);	
}

if(isset($_REQUEST['sortdown'])) {
	$order = $db->getOne('SELECT sort FROM sak_bwlist WHERE id = '.$_REQUEST['sortup']);
	$order++;
	$db->query("UPDATE sak_bwlist SET sort = '".$order."' WHERE id = ".$_REQUEST['sortup']);
}

$list = $db->getAll('SELECT * FROM sak_bwlist ORDER BY sort ASC',array(), DB_FETCHMODE_ASSOC);
?>
<script language="javascript"> 
function sak_change_option(select,id) 
{ 
	alert(select + id);
} 
</script>
<form method="post" action="">
<table CELLPADDING="5" CELLSPACING="5">
<thead>
<td><strong><u>Name/Number</strong></u></td><td><strong><u>Permit/Deny</strong></u></td><td><strong><u>Order</strong></u></td><td><strong><u>Delete</strong></u></td>
</thead>
<?php 
$i = 0;
$end = count($list);
foreach($list as $data) {
	$permit = ($data['permit']) ? 'selected' : '';
	$deny = ($data['permit']) ? '' : 'selected';

	?>
<tr>
<td align="center"><?=$data['nn']?></td>
<td align="center">	
	<select onchange="sak_change_option(this.options[this.selectedIndex].value,1)">
		  <option value="permit" <?=$permit?>>Permit</option>
		  <option value="deny" <?=$deny?>>Deny</option>
	</select>
	</td>
<td align="center"><a href="config.php?type=tool&amp;display=sak_blacklist_adv&amp;sortdown=<?=$data['id']?>"><img src="images/scrolldown.gif"></a><a href="config.php?type=tool&amp;display=sak_blacklist_adv&amp;sortup=<?=$data['id']?>"><img src="images/scrollup.gif"></a></td>
<td align="center"><a href="config.php?type=tool&amp;display=sak_blacklist_adv&amp;delete=<?=$data['id']?>"><img src="images/delete.gif"></a></td>
</tr>
<?php $i++;} ?>
</table>
<br />
<br />
	Name/Number: <input type="text" name="nn" />
	<select name="permit">
		  <option value="1">Permit</option>
		  <option value="0">Deny</option>
	</select>
	<br />
	<input type="submit" name="submit" value="Submit" />
  
</form>
<?php
if((isset($_REQUEST['submit'])) && ((!empty($_REQUEST['nn'])) OR (isset($_REQUEST['edit'])))) {
	$permit = ($_REQUEST['permit']) ? '1' : '0';

	if(isset($_REQUEST['edit'])) {
		$_REQUEST['nn'] = (isset($_REQUEST['nn'])) ? $_REQUEST['nn'] : '.*';
		$db->query("UPDATE sak_bwlist SET nn = '".$_REQUEST['nn']."', permit = '".$permit."'  WHERE id = ".$_REQUEST['edit']);	
	} else {
		$db->query("INSERT INTO sak_bwlist (nn, permit, sort) VALUES ('".addslashes($_REQUEST['nn'])."', '".$permit."', 1)");
	}
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
	$order = $db->getOne('SELECT sort FROM sak_bwlist WHERE id = '.$_REQUEST['sortdown']);
	$order++;
	$db->query("UPDATE sak_bwlist SET sort = '".$order."' WHERE id = ".$_REQUEST['sortdown']);
}

if(isset($_REQUEST['edit'])) {
	$data = $db->getRow('SELECT * FROM sak_bwlist WHERE id = '.$_REQUEST['edit'], array(), DB_FETCHMODE_ASSOC);
	$permit = ($data['permit']) ? 'selected' : '';
	$deny = ($data['permit']) ? '' : 'selected';
	$nn = $data['nn'];
	$id = $data['id'];
} else {
	$permit = $deny = $nn = '';
	$id = 0;
}

$list = $db->getAll('SELECT * FROM sak_bwlist ORDER BY sort ASC',array(), DB_FETCHMODE_ASSOC);
?>
<script language="javascript"> 
function sak_change_option(select,id) 
{ 	
	$('form:first').submit();
} 
</script>
<h3>Regular Expression White/Black List</h3>
<form id="all" method="post" action="">
<table CELLPADDING="5" CELLSPACING="5">
<thead>
<td><strong><u>Name/Number</strong></u></td><td><strong><u>Permit/Deny</strong></u></td><td><strong><u>Order</strong></u></td><td><strong><u>Edit</strong></u></td><td><strong><u>Delete</strong></u></td><td><strong><u>Times Matched</strong></u></td>
</thead>
<?php 
$i = 0;
$end = count($list);
foreach($list as $data) {
	$permit = ($data['permit']) ? 'Permit' : 'Deny';
	?>
<tr>
<td align="center"><?=$data['nn']?></td>
<td align="center"><?=$permit?></td>
<td align="center"><a href="config.php?type=tool&amp;display=sak_blacklist_adv&amp;sortdown=<?=$data['id']?>"><img src="images/scrolldown.gif"></a><a href="config.php?type=tool&amp;display=sak_blacklist_adv&amp;sortup=<?=$data['id']?>"><img src="images/scrollup.gif"></a></td>
<td align="center"><a href="config.php?type=tool&amp;display=sak_blacklist_adv&amp;edit=<?=$data['id']?>"><img src="images/edit.png"></a></td>
<td align="center"><?php if($data['id']) {?><a href="config.php?type=tool&amp;display=sak_blacklist_adv&amp;delete=<?=$data['id']?>"><img src="images/delete.gif"></a><?php } ?></td>
<td align="center"><?=$data['count']?></td>
</tr>
<?php $i++;} ?>
</table>
<br />
<br />
	Name/Number: <input type="text" name="nn" value="<?=$nn?>" <?php if((isset($_REQUEST['edit'])) && (!$id)) { echo 'disabled'; }?>/>
	<select name="permit">
		  <option value="1" <?=$permit?>>Permit</option>
		  <option value="0" <?=$deny?>>Deny</option>
	</select>
	<br />
	<input type="hidden" id="id" name="id" value="<?=$id?>" />
	<input type="submit" name="submit" value="Submit" />
</form>
<br />
Need help building Regular Expressions? Check Out <a href="http://regexpal.com/" target="_blank">http://regexpal.com/</a>
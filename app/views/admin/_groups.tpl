<div class="bb_dash">
	<div class="titlebar">
		<h2>Groups</h2>
	</div>
	<div class="content">
		<p style="padding-left:10px;padding-top:10px;">This text needs to explain the way in which groups work - for each table in the database the privilege of browse, update, insert, and delete can be granted.</p>
		<p style="padding-left:10px;padding-top:10px;"><input type="button" value="+ Add Group" onclick="window.location='<?= BASE ?>record/add/<?= BLACKBIRD_TABLE_PREFIX ?>groups';" />
			&nbsp;&nbsp;<a href="<?= BASE ?>table/browse/<?= BLACKBIRD_TABLE_PREFIX ?>groups">Browse Groups</a>
		</p>
		<table>
			<thead>
				<tr>
					<th>Name</th>
					<th>Users</th>
					<th>Admin</th>
				</tr>
			</thead>
			<tbody>
			<?php $i=0; ?>
			<?php if(is_array($data)): ?>
			<?php foreach($data as $row): ?>
			<tr class="<?= $i++%2 ? 'even' : 'odd' ?>">
				<td><a href="<?= BASE ?>record/edit/<?= BLACKBIRD_TABLE_PREFIX ?>groups/<?= $row['id'] ?>"><?= $row['name'] ?></a></td>
				<td><?= $row['members'] . ' Users' ?></td>
				<td><?= _ControllerFront::formatCol('admin',$row['admin'],'') ?></td>
			</tr>
			<?php endforeach ?>
			<?php endif ?>
			</tbody>
		</table>	
	</div>
</div>

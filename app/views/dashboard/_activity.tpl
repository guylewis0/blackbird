<div class="bb_dash">
	<div class="titlebar">
		<h2>Recent Activity</h2>
	</div>
	<div class="content">
		<?php if(is_array($data)): ?>
		<table>
			<thead>
				<tr>
					<th>Action</th>
					<th>Table</th>
					<th>Record</th>
					<th>User</th>
					<th>Time</th>
				</tr>
			</thead>
			<tbody>
				<?php $i=0; foreach($data as $row): ?>
				<tr class="<?= $i++%2 ? 'even' : 'odd' ?>">
					<td class="<?= $row['action'] ?>"><?= ucfirst($row['action']) ?></td>
					<td><a href="<? BASE ?>table/browse/<?= $row['table_name'] ?>"><?= _ControllerFront::getTableName($row['table_name']) ?></a></td>
					<?php if($row['action'] != 'delete'): ?>
					<td><a href="<? BASE ?>record/edit/<?= $row['table_name'] ?>/<?= $row['record_id'] ?>"><?= $row['record_id'] ?></a></td>
					<?php else: ?>
					<td><?= $row['record_id'] ?></td>
					<?php endif ?>
					<td><a href="<?= BASE ?>user/profile/<?= $row['user_id'] ?>"><?= $row['user'] ?></a></td>
					<?php $diff = Utils::getTimeDifference($row['modtime'],Utils::now()) ?>
					<?php if($diff['days'] > 30): ?>
					<td>A long time ago</td>
					<?php elseif($diff['days'] >= 1): ?>
					<td><?= $diff['days'] ?> days ago</td>
					<?php elseif($diff['hours'] >= 1): ?>
					<td><?= $diff['hours'] ?> hours ago</td>
					<?php elseif($diff['minutes'] >= 1): ?>
					<td><?= $diff['minutes'] ?> minutes ago</td>
					<?php else: ?>
					<td>moments ago</td>
					<?php endif ?>		
				</tr>
				<?php endforeach ?>
			</tbody>
		</table>
		<?php else: ?>
		<p class="message">There is no activity yet…</p>
		<?php endif ?>
	</div>
</div>
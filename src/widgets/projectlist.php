<?php
class project_list extends widget {
	protected function create($parent, $settings) {
		doc::getdoc()->includeCSS('!index.css');
		$projects = $settings['data'];
		$sortable = $settings['sortable'];
		$page = $_REQUEST['pl_page'] ? $_REQUEST['pl_page'] : 1;
		$lastpage = $settings['lastpage'];
		$now = new DateTime();

		$table = new table($parent, 'projects');
		$trow = new row($table, 'trow');
		if (!$sortable) $trow->addCell("Target", 'date');
		else $trow->addSortable("Target", 'target', 'date');
		if (!$sortable) $trow->addCell("Project", 'project');
		else $trow->addSortable("Project", 'name', 'project');
		if (!$sortable) $trow->addCell("Portfolio", 'portfolio');
		else $trow->addSortable("Portfolio", 'master_name', 'portfolio');
		if (!$sortable) $trow->addCell("Level", 'area');
		else $trow->addSortable("Level", 'unit_abbr', 'area');
		if (!$sortable) $trow->addCell("Type", 'type');
		else $trow->addSortable("Type", 'classification_name', 'type');
		if (!$sortable) $trow->addCell("Phase", 'phase');
		else $trow->addSortable("Phase", 'phase', 'phase');
		if (!$sortable) $trow->addCell("Lead", 'pm');
		else $trow->addSortable("Lead", 'manager_name', 'pm');
		if (!$sortable) $trow->addCell("Modified", 'modified');
		else $trow->addSortable("Modified", 'modified', 'date');
		if (!$sortable) $trow->addCell("Health", 'status');
		else $trow->addSortable("Health", 'overall', 'status');
		$trow->addCell("Status Update", 'comment');

		foreach ($projects as $p) {
			$rowclass = ($rowclass == 'odd' ? 'even' : 'odd');
			$row = new row($table, $rowclass);

			$target = new DateTime($p['target']);
			$row->addCell($target->format('m/d/y').' ('.generic_date_difference($target, $now).')', 'date');

			// some extra work on the link, notifying user of various conditions
			$cell = $row->addCell($lnk = new link(0, 'project.php', $p['name'], array('id'=>$p['id'])), 'project');
			if (checkperm('completeproject', $p['id']) && $p['complete'] == 'pending') {
				$lnk->addclass('actionrequired');
				$cell->addText("\n(Completion Request)", 'extrainfo');
			}

			$row->addCell($p['master_name'], 'portfolio');
			$row->addCell($p['unit_abbr'], 'area');
			$row->addCell($p['classification_name'], 'type');
			$row->addCell($p['phase'], 'phase');
			$row->addCell($p['current_manager'], 'pm');
			$published = date_from_database($p['modified']);
			$row->addCell(relative_date($published, TRUE, TRUE), 'modified');
			$row->addCell('  ', 'status'.strToLower($p['overall']['status_name']));
			$row->addCell($p['comment']);
		}

		if (empty($projects)) {
			$row = new row($table);
			$cell = $row->addCell('No projects to display.');
			$cell->setwidth($trow->childCount());
		}

		if ($lastpage > 1) {
			$grp = new linkgroup($parent);
			for ($i = 1; $i <= $lastpage; $i++) {
				if ($i == $page) { $grp->addText('[b]'.$i.'[/b]'); }
				else { new link($grp, '', $i, array('pl_page'=>$i)+doc::create_mimic()); }
			}
		}

	}
}
?>

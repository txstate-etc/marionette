<?php
class project_list extends widget {
	protected function create($parent, $settings) {
		doc::getdoc()->includeCSS('!index.css');
		$projects = $settings['data'];
		$sortable = $settings['sortable'];
		$page = $_REQUEST['pl_page'] ? $_REQUEST['pl_page'] : 1;
		$lastpage = $settings['lastpage'];
		
		$table = new table($parent, 'projects');
		$trow = new row($table, 'trow');
		if (checkperm('viewcurrent')) {
			$trow->addCell("Pub", 'publish');
		}
		if (!$sortable) $trow->addCell("Target", 'date');
		else $trow->addSortable("Target", 'target', 'date');
		$trow->addCell("ID", 'id');
		if (!$sortable) $trow->addCell("Pri", 'priority');
		else $trow->addSortable("Pri", 'priority', 'priority');
		if (!$sortable) $trow->addCell("Project", 'project');
		else $trow->addSortable("Project", 'name', 'project');
		if (!$sortable) $trow->addCell("Area", 'area');
		else $trow->addSortable("Area", 'unit_abbr', 'area');
		if (!$sortable) $trow->addCell("Type", 'type');
		else $trow->addSortable("Type", 'classification_name', 'type');
		if (!$sortable) $trow->addCell("PM", 'pm');
		else $trow->addSortable("PM", 'manager_name', 'pm');
		if (!$sortable) $trow->addCell("Phase", 'phase');
		else $trow->addSortable("Phase", 'phase', 'phase');
		$trow->addCell("Current Activity", 'activity');
		if (!$sortable) $trow->addCell("Scope", 'status');
		else $trow->addSortable("Scope", 'scope', 'status');
		if (!$sortable) $trow->addCell("Schedule", 'status');
		else $trow->addSortable("Schedule", 'schedule', 'status');
		if (!$sortable) $trow->addCell("Resource", 'status');
		else $trow->addSortable("Resource", 'resource', 'status');
		if (!$sortable) $trow->addCell("Quality", 'status');
		else $trow->addSortable("Quality", 'quality', 'status');
		if (!$sortable) $trow->addCell("Overall Health", 'status');
		else $trow->addSortable("Overall Health", 'overall', 'status');
		$trow->addCell("Comment", 'comment');
		
		foreach ($projects as $p) {
			$rowclass = ($rowclass == 'odd' ? 'even' : 'odd');
			$row = new row($table, $rowclass);
			if (checkperm('viewcurrent')) {
				if (db_layer::project_haspublishes($p['id'])) { $text = 'Yes'; $class = 'published'; }
				else { $text = 'No'; $class = 'notpublished'; }
				$row->addCell($text, $class);
			}
			$target = new DateTime($p['target']);
			$row->addCell($target->format('m/d/y'), 'date');
			$row->addCell($p['identify'], 'id');
			$row->addCell($p['priority'], 'priority');
			
			// some extra work on the link, notifying user of various conditions
			$cell = $row->addCell($lnk = new link(0, 'project.php', $p['name'], array('id'=>$p['id'])), 'project');
			if (checkperm('completeproject', $p['id']) && $p['complete'] == 'pending') {
				$lnk->addclass('actionrequired');
				$cell->addText("\n(Completion Request)", 'extrainfo');
			}
			
			$row->addCell($p['unit_abbr'], 'area');
			$row->addCell($p['classification_name'], 'type');
			$row->addCell($p['current_manager'], 'pm');
			$row->addCell($p['phase'], 'phase');
			$row->addCell($p['activity'], 'activity');
			$row->addCell('  ', 'status'.strToLower($p['scope']['status_name']));
			$row->addCell('  ', 'status'.strToLower($p['schedule']['status_name']));
			$row->addCell('  ', 'status'.strToLower($p['resource']['status_name']));
			$row->addCell('  ', 'status'.strToLower($p['quality']['status_name']));
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
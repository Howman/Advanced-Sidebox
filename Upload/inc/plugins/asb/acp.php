<?php
/*
 * Plug-in Name: Advanced Sidebox for MyBB 1.6.x
 * Copyright 2013 WildcardSearch
 * http://www.wildcardsworld.com
 *
 * this file contains the ACP functionality and depends upon install.php for plug-in info and installation routines
 */

// disallow direct access to this file for security reasons
if(!defined("IN_MYBB") || !defined("IN_ASB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}
define('ASB_URL', 'index.php?module=config-asb');
require_once MYBB_ROOT . 'inc/plugins/asb/functions_acp.php';
require_once MYBB_ROOT . "inc/plugins/asb/install.php";

/*
 * asb_admin()
 *
 * the ACP page router
 */
$plugins->add_hook('admin_load', 'asb_admin');
function asb_admin()
{
	// globalize as needed to save wasted work
	global $page;
	if($page->active_action != 'asb')
	{
		// not our turn
		return false;
	}

	// now load up, this is our time
	global $mybb, $lang, $html, $scripts, $all_scripts;
	if(!$lang->asb)
	{
		$lang->load('asb');
	}

	// a few general functions and classes for the ACP side
	require_once MYBB_ROOT . 'inc/plugins/asb/classes/acp.php';

	// URL, link and image markup generator
	$html = new HTMLGenerator(ASB_URL, array('addon', 'pos', 'topic', 'ajax'));

	$scripts = asb_get_all_scripts();
	if(is_array($scripts) && !empty($scripts))
	{
		foreach($scripts as $filename => $script)
		{
			$all_scripts[$filename] = $script['title'];
		}
	}
	else
	{
		$scripts = $all_scripts = array();
	}

	// if there is an existing function for the action
	$page_function = 'asb_admin_' . $mybb->input['action'];
	if(function_exists($page_function))
	{
		// run it
		$page_function();
	}
	else
	{
		// default to the main page
		asb_admin_manage_sideboxes();
	}
	// get out
	exit();
}

/*
 * asb_admin_manage_sideboxes()
 *
 * main side box management page - drag and drop and standard controls for side boxes
 */
function asb_admin_manage_sideboxes()
{
	global $mybb, $db, $page, $lang, $html, $scripts, $all_scripts;

	$addons = asb_get_all_modules();

	// if there are add-on modules
	if(is_array($addons))
	{
		// display them
		foreach($addons as $module)
		{
			$id = $box_type = $module->get('base_name');
			$title = $module->get('title');
			$title_url = $html->url(array("action" => 'edit_box', "addon" => $box_type));
			$title_link = $html->link($title_url, $title, array("class" => 'add_box_link', "title" => $lang->asb_add_new_sidebox));

			// add the HTML
			$modules .= <<<EOF
			<div id="{$id}" class="draggable box_type">
				{$title_link}
			</div>

EOF;

			// build the JS to enable dragging
			$module_script .= <<<EOF
		new Draggable('{$id}', { revert: true });

EOF;
		}
	}

	$custom = asb_get_all_custom();

	// if there are custom boxes
	if(is_array($custom))
	{
		// display them
		foreach($custom as $module)
		{
			$id = $box_type = $module->get('base_name');
			$title = $module->get('title');
			$title_url = $html->url(array("action" => 'edit_box', "addon" => $box_type));
			$title_link = $html->link($title_url, $title, array("class" => 'add_box_link', "title" => $lang->asb_add_new_sidebox));

			// add the HTML
			$custom_boxes .= <<<EOF
			<div id="{$id}" class="draggable custom_type">
				{$title_link}
			</div>

EOF;

			// build the js to enable dragging
			$module_script .= <<<EOF
		new Draggable('{$id}', { revert: true });

EOF;
		}
	}

	$sideboxes = asb_get_all_sideboxes($mybb->input['page']);

	// if there are side boxes
	if(is_array($sideboxes))
	{
		// display them
		foreach($sideboxes as $sidebox)
		{
			// build the side box
			$box = asb_build_sidebox_info($sidebox);

			// and sort it by position
			if($sidebox->get('position'))
			{
				$right_boxes .= $box;
			}
			else
			{
				$left_boxes .= $box;
			}
		}
	}

	$page->add_breadcrumb_item($lang->asb_manage_sideboxes);

	// set up the page header
	$page->extra_header .= <<<EOF
<script type="text/javascript">
		<!--
			columns = ['left_column', 'right_column', 'trash_column'];
		// -->
		</script>
		<link rel="stylesheet" type="text/css" href="styles/asb_acp.css" media="screen" />
		<script src="../jscripts/scriptaculous.js?load=effects,dragdrop,controls" type="text/javascript"></script>
		<script src="jscripts/imodal.js" type="text/javascript"></script>
		<link rel="stylesheet" type="text/css" href="styles/default/imodal.css"/>
		<script src="jscripts/asb.js" type="text/javascript"></script>
EOF;

	asb_output_header("{$lang->asb} - {$lang->asb_manage_sideboxes}");
	asb_output_tabs('asb');

	$filter_text = '';
	if($mybb->input['page'])
	{
		$filter_text = $lang->sprintf($lang->asb_filter_label, $all_scripts[$mybb->input['page']]);
	}

	// build the display
	$markup = <<<EOF
	<div id="droppable_container">{$filter_text}
		<table width="100%" class="back_drop">
			<tr>
				<td width="18%" class="column_head">{$lang->asb_addon_modules}</td>
				<td width="18%" class="column_head">{$lang->asb_custom}</td>
				<td width="30%" class="column_head">{$lang->asb_position_left}</td>
				<td width="30%" class="column_head">{$lang->asb_position_right}</td>
			</tr>
			<tr>
				<td id="addon_menu" valign="top" rowspan="2">
					{$modules}
				</td>
				<td id="custom_menu" valign="top" rowspan="2">
					{$custom_boxes}
				</td>
				<td id="left_column" valign="top" class="column forum_column">
					{$left_boxes}
				</td>
				<td id="right_column" valign="top" class="column forum_column">
					{$right_boxes}
				</td>
			</tr>
			<tr height="45px;">
				<td id="trash_column" class="column trashcan" colspan="2"></td>
			</tr>
		</table>
	</div>
	<script type="text/javascript">
	<!--
		build_sortable('left_column');
		build_sortable('right_column');
		build_sortable('trash_column');
		$$("a[id^='edit_sidebox_']").invoke
		(
			'observe',
			'click',
			function(event)
			{
				Event.stop(event);
			}
		);
		$$('.del_icon').each
		(
			function(e)
			{
				e.remove();
			}
		);
		$$('.add_box_link').each
		(
			function(e)
			{
				e.replace(e.innerHTML);
			}
		);

{$module_script}
	// -->
	</script>
EOF;
	// and display it
	echo($markup);

	// output the link menu and MyBB footer
	asb_output_footer('manage_sideboxes');
}

/*
 * asb_admin_edit_box()
 *
 * handles the modal/JavaScript edit box and also (as a backup) displays a standard form for those with JavaScript disabled
 */
function asb_admin_edit_box()
{
	global $page, $lang, $mybb, $db, $html, $scripts, $all_scripts;

	$box_types = asb_compile_box_types();

	// saving?
	if($mybb->request_method == 'post')
	{
		// start with a new box
		$this_sidebox = new Sidebox();

		// position
		$pos_key = 'box_position';
		// if called by JS
		if($mybb->input['ajax'] == 1)
		{
			// the position will be stored in a hidden field
			$pos_key = 'pos';
		}
		$position = (int) $mybb->input[$pos_key];
		$this_sidebox->set('position', $position);

		// display order
		if(!isset($mybb->input['display_order']) || (int) $mybb->input['display_order'] == 0)
		{
			// get a total number of side boxes on the same side and put it at the bottom
			$query = $db->simple_select('asb_sideboxes', 'display_order', "position='{$position}'");
			$display_order = (int) (($db->num_rows($query) + 1) * 10);
		}
		else
		{
			// or back off if they entered a value
			$display_order = (int) $mybb->input['display_order'];
		}
		$this_sidebox->set('display_order', $display_order);

		// if we are handling an AJAX request
		if($mybb->input['ajax'] == 1)
		{
			// then we need to convert the input to an array
			$script_list = explode(",", $mybb->input['script_select_box'][0]);
			$group_list = explode(",", $mybb->input['group_select_box'][0]);
		}
		else
		{
			$script_list = $mybb->input['script_select_box'];
			$group_list = $mybb->input['group_select_box'];
		}

		if($group_list[0] == 'all')
		{
			$group_list = array();
		}
		if($script_list[0] == 'all_scripts' || (count($script_list) >= count($all_scripts)))
		{
			$script_list = array();
		}

		// store them
		$this_sidebox->set('scripts', $script_list);
		$this_sidebox->set('groups', $group_list);

		// box type
		$module = trim($mybb->input['addon']);
		$this_sidebox->set('box_type', $module);

		// id
		$this_sidebox->set('id', $mybb->input['id']);

		// is this side box created by an add-on module?
		$test = new Addon_type($module);
		if($test->is_valid())
		{
			$this_sidebox->set('wrap_content', $test->get('wrap_content'));
			$addon_settings = $test->get('settings');

			// if the parent module has settings . . .
			if(is_array($addon_settings))
			{
				$settings = array();

				// loop through them
				foreach($addon_settings as $setting)
				{
					// and if the setting has a value
					if(isset($mybb->input[$setting['name']]))
					{
						// store it
						$setting['value'] = $mybb->input[$setting['name']];
						$settings[$setting['name']] = $setting;
					}
				}
				$this_sidebox->set('settings', $settings);
			}
		}
		else
		{
			// did this box come from a custom static box?
			$test = new Custom_type($module);
			if($test->is_valid())
			{
				// then use its wrap_content property
				$this_sidebox->set('wrap_content', $test->get('wrap_content'));
			}
			else
			{
				// otherwise wrap the box
				$this_sidebox->set('wrap_content', true);
			}
		}

		// if the text field isn't empty . . .
		if(isset($mybb->input['box_title']) && $mybb->input['box_title'])
		{
			// use it
			$this_sidebox->set('title', $mybb->input['box_title']);
		}
		else
		{
			// otherwise, check the hidden field (original title)
			if(isset($mybb->input['current_title']) && $mybb->input['current_title'])
			{
				// if it exists, use it
				$this_sidebox->set('title', $mybb->input['current_title']);
			}
			else
			{
				// otherwise use the default title
				$this_sidebox->set('title', $box_types[$this_sidebox->get('box_type')]);
			}
		}

		// save the side box
		$status = $this_sidebox->save();
		asb_cache_has_changed();

		// AJAX?
		if($mybb->input['ajax'] == 1)
		{
			// get some info
			$id = (int) $this_sidebox->get('id');
			$column_id = 'left_column';
			if($position)
			{
				$column_id = 'right_column';
			}

			// creating a new box?
			if($mybb->input['id'] == '' || $mybb->input['id'] == 0)
			{
				// then escape the title
				$box_title = addcslashes($this_sidebox->get('title'), "'");

				// and create the new <div> representation of the side box (title only it will be filled in later by the updater)
				$script = <<<EOF
<script type="text/javascript">$('{$column_id}').highlight(); var new_box=document.createElement('div'); new_box.innerHTML='{$box_title}'; new_box.id='sidebox_{$id}'; new_box.setAttribute('class','sidebox'); new_box.style.position='relative'; $('{$column_id}').appendChild(new_box); build_sortable('{$column_id}'); build_droppable('{$column_id}'); new Ajax.Updater('sidebox_{$id}', "index.php?module=config-asb&action=xmlhttp&mode=build_info&id={$id}",{ method:"get", evalScripts: true });</script>
EOF;
			}
			else
			{
				// if the box exists just update it
				$script = <<<EOF
<script type="text/javascript">new Ajax.Updater('sidebox_{$id}', "index.php?module=config-asb&action=xmlhttp&mode=build_info&id={$id}",{ method:"get", evalScripts: true });</script>"
EOF;
			}
			// the modal box will eval() any scripts passed as output (that are valid).
			echo($script);
			exit;
		}
		else
		{
			// if in the standard form handle it with a redirect
			flash_message($lang->asb_save_success, "success");
			admin_redirect('index.php?module=config-asb');
		}
	}

	// attempt to load the specified box
	$this_sidebox = new Sidebox((int) $mybb->input['id']);
	$box_id = (int) $this_sidebox->get('id');
	$module = $mybb->input['addon'];
	$pos = (int) $mybb->input['pos'];
	$is_custom = $is_module = false;
	$custom_title = 0;

	$page_title = $lang->asb_add_a_sidebox;
	if($box_id)
	{
		$page_title = $lang->asb_edit_a_sidebox;
	}

	// AJAX?
	if($mybb->input['ajax'] == 1)
	{
		// the content is much different
		echo "<div id=\"ModalContentContainer\"><div class=\"ModalTitle\">{$page_title}<a href=\"javascript:;\" id=\"modalClose\" class=\"float_right modalClose\">&nbsp;</a></div><div class=\"ModalContent\">";
		$form = new Form("", "post", "modal_form");
	}
	else
	{
		// standard form stuff
		$page->add_breadcrumb_item($lang->asb);
		$page->add_breadcrumb_item($page_title);

		// add a little CSS
		$page->extra_header .= '<link rel="stylesheet" type="text/css" href="styles/asb_acp.css" media="screen" />';
		asb_output_header("{$lang->asb} - {$page_title}");
		$form = new Form($html->url(array("action" => 'edit_box', "id" => $box_id, "addon" => $module)), "post", "modal_form");
	}

	// if $this_sidebox exists it will have a non-zero id property . . .
	if($box_id == 0)
	{
		// if it doesn't then this is a new box, check the page view filter to try to predict which script the user will want
		if(isset($mybb->input['page']) && $mybb->input['page'])
		{
			// start them out with the script they are viewing for Which Scripts
			$selected_scripts[] = $mybb->input['page'];
		}
		else
		{
			// if page isn't set at all then just start out with all scripts
			$selected_scripts = 'all_scripts';
		}

		$custom_title = 0;
		$current_title = '';

		$test = new Addon_type($module);

		if(!$test->is_valid())
		{
			$test = new Custom_type($module);

			if($test->is_valid())
			{
				$is_custom = true;
			}
		}
		else
		{
			$is_module = true;
		}
	}
	else
	{
		// . . . otherwise we are editing so pull the actual info from the side box
		$selected_scripts = $this_sidebox->get('scripts');
		if(empty($selected_scripts))
		{
			$selected_scripts = 'all_scripts';
		}
		elseif(isset($selected_scripts[0]) && strlen($selected_scripts[0]) == 0)
		{
			$script_warning = <<<EOF
<span style="color: red;">{$lang->asb_all_scripts_deactivated}</span><br />
EOF;
		}

		$module = $this_sidebox->get('box_type');

		$test = new Addon_type($module);

		// is this side box from an add-on?
		if($test->is_valid() == true)
		{
			$is_module = true;

			// check the name of the add-on against the display name of the sidebox, if they differ . . .
			if($this_sidebox->get('title') != $test->get('title'))
			{
				// then this box has a custom title
				$custom_title = 1;
			}
		}
		// is this side box from a custom static box?
		else
		{
			$test = new Custom_type($module);

			if($test->is_valid())
			{
				$is_custom = true;

				// if so, then is the title different than the original?
				if($this_sidebox->get('title') != $test->get('title'))
				{
					// custom title
					$custom_title = 1;
				}
			}
			else
			{
				// default title
				$custom_title = 0;
			}
		}
	}

	$tabs = array
	(
		"general" => $lang->asb_modal_tab_general,
		"permissions" => $lang->asb_modal_tab_permissions,
		"pages" => $lang->asb_modal_tab_pages,
		"settings" => $lang->asb_modal_tab_settings
	);

	// we only need a 'Settings' tab if the current module type has settings
	$do_settings = true;
	if(!$this_sidebox->has_settings)
	{
		if($is_module && !$test->has_settings)
		{
			unset($tabs["settings"]);
			$do_settings = false;
		}
	}
	reset($tabs);

	$observe_onload = false;
	if($mybb->input['ajax'] != 1)
	{
		$observe_onload = true;
	}
	$page->output_tab_control($tabs, $observe_onload);

	// custom title?
	if($custom_title == 1)
	{
		// alter the descrption
		$current_title = '<br /><em>' . $lang->asb_current_title . '</em><br /><br /><strong>' . $this_sidebox->get('title') . '</strong><br />' . $lang->asb_current_title_info;
	}
	else
	{
		// default description
		$current_title = '<br />' . $lang->asb_default_title_info;
	}

	// current editing text
	if($is_module || $is_custom)
	{
		$currently_editing = '"' . $test->get('title') . '"';
	}

	$box_action = $lang->asb_creating;
	if(isset($mybb->input['id']))
	{
		$box_action = $lang->asb_editing;
	}

	echo "<div id=\"tab_general\">\n";
	$form_container = new FormContainer('<h3>' . $lang->sprintf($lang->asb_new_sidebox_action, $box_action, $currently_editing) . '</h3>');

	if($mybb->input['ajax'] != 1)
	{
		// box title
		$form_container->output_row($lang->asb_custom_title, $current_title, $form->generate_text_box('box_title'), 'box_title', array("id" => 'box_title'));

		// position
		$form_container->output_row($lang->asb_position, '', $form->generate_radio_button('box_position', 0, $lang->asb_position_left, array("checked" => ($this_sidebox->get('position') == 0))) . '&nbsp;&nbsp;' . $form->generate_radio_button('box_position', 1, $lang->asb_position_right, array("checked" => ($this_sidebox->get('position') != 0))));

		// display order
		$form_container->output_row($lang->asb_display_order, '', $form->generate_text_box('display_order', $this_sidebox->get('display_order')));
	}
	else
	{
		// box title
		$form_container->output_row('', '', $form->generate_text_box('box_title') . '<br />' . $current_title . $form->generate_hidden_field('display_order', $this_sidebox->get('display_order')), 'box_title', array("id" => 'box_title'));
	}

	// hidden forms to pass info to post
	$form_container->output_row('', '', $form->generate_hidden_field('current_title', $this_sidebox->get('title')) . $form->generate_hidden_field('pos', $pos));
	$form_container->end();

	echo "</div><div id=\"tab_permissions\">\n";
	$form_container = new FormContainer($lang->asb_which_groups);

	// prepare options for which groups
	$options = array();
	$groups = array();
	$options['all'] = $lang->asb_all_groups;
	$options[0] = $lang->asb_guests;

	// look for all groups except Super Admins
	$query = $db->simple_select("usergroups", "gid, title", "gid != '1'", array('order_by' => 'gid'));
	while($usergroup = $db->fetch_array($query))
	{
		// store them their titles by groud id
		$options[(int)$usergroup['gid']] = $usergroup['title'];
	}

	// do we have groups stored?
	$groups = $this_sidebox->get('groups');
	if(empty($groups))
	{
		$groups = 'all';
	}

	// which groups
	$form_container->output_row('', $script_warning, $form->generate_select_box('group_select_box[]', $options, $groups, array('id' => 'group_select_box', 'multiple' => true, 'size' => 5)));
	$form_container->output_row('', '', $form->generate_hidden_field('this_group_count', count($options)));

	$form_container->end();

	echo "</div><div id=\"tab_pages\">\n";
	$form_container = new FormContainer($lang->asb_which_scripts);

	// prepare for which scripts
	$choices = array();
	$choices["all_scripts"] = $lang->asb_all;

	// are there active scripts?
	if(is_array($all_scripts))
	{
		// loop through them
		foreach($all_scripts as $filename => $title)
		{
			// store the script as a choice
			$choices[$filename] = $title;
		}
	}

	// if there are few scripts to choose from, alter the layout and/or wording of choices
	switch(count($choices))
	{
		case 3:
			$choices['all_scripts'] = $lang->asb_both_scripts;
			break;
		case 2:
			unset($choices['all_scripts']);
			$selected_scripts = array_flip($choices);
			break;
		case 1:
			$choices['all_scripts'] = $lang->asb_all_scripts_disabled;
			break;
	}

	// which scripts
	$form_container->output_row('', $script_warning, $form->generate_select_box('script_select_box[]', $choices, $selected_scripts, array("id" => 'script_select_box', "multiple" => true)));
	$form_container->end();

	if($do_settings)
	{
		if($mybb->input['ajax'] == 1)
		{
			$settings_style = " style=\"max-height: 400px; overflow: auto;\"";
		}
		echo "</div><div id=\"tab_settings\"{$settings_style}>\n";

		$form_container = new FormContainer($lang->asb_modal_tab_settings_desc);

		if($box_id)
		{
			$sidebox_settings = $this_sidebox->get('settings');
		}
		elseif($is_module)
		{
			$sidebox_settings = $test->get('settings');
		}

		if(is_array($sidebox_settings))
		{
			foreach($sidebox_settings as $setting)
			{
				// allow the handler to build module settings
				asb_build_setting($form, $form_container, $setting, $box_id, $module);
			}
		}
		$form_container->end();
	}

	// AJAX gets a little different wrap-up
	if($mybb->input['ajax'] == 1)
	{
		echo "</div><div class=\"ModalButtonRow\">";

		$buttons[] = $form->generate_submit_button($lang->asb_cancel, array('id' => 'modalCancel'));
		$buttons[] = $form->generate_submit_button($lang->asb_save, array('id' => 'modalSubmit'));
		$form->output_submit_wrapper($buttons);
		echo "</div>";
		$form->end();
		echo "</div>";
	}
	else
	{
		echo "</div>";
		// finish form and page
		$buttons[] = $form->generate_submit_button('Save', array('name' => 'save_box_submit'));
		$form->output_submit_wrapper($buttons);
		$form->end();

		// output the link menu and MyBB footer
		asb_output_footer('edit_box');
	}
}

/*
 * asb_admin_custom_boxes()
 *
 * handle user-defined box types
 */
function asb_admin_custom_boxes()
{
	global $lang, $mybb, $db, $page, $html;

	if($mybb->input['mode'] == 'export')
	{
		if((int) $mybb->input['id'] > 0)
		{
			$this_custom = new Custom_type($mybb->input['id']);

			if(!$this_custom->is_valid())
			{
				flash_message($lang->asb_custom_export_error, 'error');
				admin_redirect($html->url(array("action" => 'custom_boxes')));
			}

			$this_custom->export();
			exit();
		}
	}

	if($mybb->input['mode'] == 'delete')
	{
		// info good?
		if((int) $mybb->input['id'] > 0)
		{
			// nuke it
			$this_custom = new Custom_type($mybb->input['id']);

			// success?
			if($this_custom->remove())
			{
				// :)
				flash_message($lang->asb_add_custom_box_delete_success, "success");
				asb_cache_has_changed();
				admin_redirect($html->url(array("action" => 'custom_boxes')));
			}
		}

		// :(
		flash_message($lang->asb_add_custom_box_delete_failure, "error");
		admin_redirect($html->url(array("action" => 'custom_boxes')));
	}

	// POSTing?
	if($mybb->request_method == "post")
	{
		if($mybb->input['mode'] == 'import')
		{
			if($_FILES['file'] && $_FILES['file']['error'] != 4)
			{
				if(!$_FILES['file']['error'])
				{
					if(is_uploaded_file($_FILES['file']['tmp_name']))
					{
						$contents = @file_get_contents($_FILES['file']['tmp_name']);
						@unlink($_FILES['file']['tmp_name']);

						if(!trim($contents))
						{
							$error = $lang->asb_custom_import_file_empty;
						}
					}
					else
					{
						$error = $lang->asb_custom_import_file_upload_error;
					}
				}
				else
				{
					$error = $lang->sprintf($lang->asb_custom_import_file_error, $_FILES['file']['error']);
				}
			}
			else
			{
				$error = $lang->asb_custom_import_no_file;
			}

			if(!$error)
			{
				require_once MYBB_ROOT . 'inc/class_xml.php';
				$parser = new XMLParser($contents);
				$tree = $parser->get_tree();

				if(is_array($tree))
				{
					if(is_array($tree['asb_custom_sideboxes']))
					{
						$custom = new Custom_type;

						if($custom->import($contents))
						{
							$custom->save();
						}
						else
						{
							$error = 'boo';
						}
					}
					else
					{
						if(is_array($tree['adv_sidebox']) && is_array($tree['adv_sidebox']['custom_sidebox']))
						{
							$results = asb_legacy_custom_import($tree);

							if(is_array($results))
							{
								$custom = new Custom_type($results);
								if(!$custom->save())
								{
									$error = $lang->asb_custom_import_save_fail;
								}
							}
							else
							{
								$error = $results;
							}
						}
						else
						{
							$error = $lang->asb_custom_import_file_empty;
						}
					}
				}
				else
				{
					$error = $lang->asb_custom_import_file_empty;
				}
			}

			if($error)
			{
				flash_message($error, 'error');
				admin_redirect($html->url(array("action" => 'custom_boxes')));
			}
			else
			{
				flash_message($lang->asb_custom_import_save_success, 'success');
				admin_redirect($html->url(array("action" => 'custom_boxes', "id" => $custom->get('id'))));
			}
		}
		else
		{
			// saving?
			if($mybb->input['save_box_submit'] == 'Save')
			{
				if(!$mybb->input['box_name'] || !$mybb->input['box_content'])
				{
					flash_message($lang->asb_custom_box_save_failure_no_content, "error");
					admin_redirect($html->url(array("action" => 'custom_boxes')));
				}
				$this_custom = new Custom_type((int) $mybb->input['id']);

				// get the info
				$this_custom->set('title', $mybb->input['box_name']);
				$this_custom->set('description', $mybb->input['box_description']);
				$this_custom->set('content', $mybb->input['box_content']);

				if($mybb->input['wrap_content'] == 'yes')
				{
					$this_custom->set('wrap_content', true);
				}

				$status = $this_custom->save();

				// success?
				if($status)
				{
					// :)
					flash_message($lang->asb_custom_box_save_success, "success");
					asb_cache_has_changed();
				}
				else
				{
					// :(
					flash_message($lang->asb_custom_box_save_failure, "error");
				}
				admin_redirect($html->url(array("action" => 'custom_boxes', "id" => $this_custom->get('id'))));
			}
		}
	}

	$page->add_breadcrumb_item($lang->asb, $html->url());

	if($mybb->input['mode'] == 'edit')
	{
		$queryadmin = $db->simple_select('adminoptions', '*', "uid='{$mybb->user['uid']}'");
		$admin_options = $db->fetch_array($queryadmin);

		if($admin_options['codepress'] != 0)
		{
			$page->extra_header .= <<<EOF
	<link type="text/css" href="./jscripts/codepress/languages/codepress-mybb.css" rel="stylesheet" id="cp-lang-style"/>
	<script type="text/javascript" src="./jscripts/codepress/codepress.js"></script>
	<script type="text/javascript">
	<!--
		CodePress.language = 'mybb';
	// -->
	</script>'
EOF;
		}

		$this_box = new Custom_type((int) $mybb->input['id']);

		$action = $lang->asb_add_custom;
		if($this_box->get('id'))
		{
			$specify_box = "&amp;id=" . $this_box->get('id');
			$currently_editing = $lang->asb_editing . ': <strong>' . $this_box->get('title') . '</strong>';
			$action = $lang->asb_edit . ' ' . $this_box->get('title');
		}
		else
		{
			// new box
			$specify_box = '';
			$this_box->set('content', "<tr>
		<td class=\"trow1\">{$lang->asb_sample_content_line1} (HTML)</td>
	</tr>
	<tr>
		<td class=\"trow2\">{$lang->asb_sample_content_line2}</td>
	</tr>
	<tr>
		<td class=\"trow1\"><strong>{$lang->asb_sample_content_line3}</td>
	</tr>");
			$this_box->set('wrap_content', true);
		}

		$page->add_breadcrumb_item($lang->asb_custom_boxes, $html->url(array("action" => 'custom_boxes')));
		$page->add_breadcrumb_item($lang->asb_add_custom);
		$page->output_header("{$lang->asb_name} - {$action}");
		asb_output_tabs('asb_add_custom');

		$form = new Form($html->url(array("action" => 'custom_boxes')) . $specify_box, "post", "edit_box");
		$form_container = new FormContainer($currently_editing);

		$form_container->output_cell($lang->asb_name);
		$form_container->output_cell($lang->asb_description);
		$form_container->output_cell($lang->asb_custom_box_wrap_content);
		$form_container->output_row('');

		//name
		$form_container->output_cell($form->generate_text_box('box_name', $this_box->get('title'), array("id" => 'box_name')));

		// description
		$form_container->output_cell($form->generate_text_box('box_description', $this_box->get('description')));

		// wrap content?
		$form_container->output_cell($form->generate_check_box('wrap_content', 'yes', $lang->asb_custom_box_wrap_content_desc, array("checked" => $this_box->get('wrap_content'))));
		$form_container->output_row('');

		$form_container->output_cell('Content:', array("colspan" => 3));
		$form_container->output_row('');

		// content
		$form_container->output_cell($form->generate_text_area('box_content', $this_box->get('content'), array("id" => 'box_content', 'class' => 'codepress mybb', 'style' => 'width: 100%; height: 240px;')), array("colspan" => 3));
		$form_container->output_row('');

		// finish form
		$form_container->end();
		$buttons[] = $form->generate_submit_button('Save', array('name' => 'save_box_submit'));
		$form->output_submit_wrapper($buttons);
		$form->end();

		if($admin_options['codepress'] != 0)
		{
			echo <<<EOF
		<script type="text/javascript">
		<!--
			Event.observe
			(
				'edit_box',
				'submit',
				function()
				{
					if($('box_content_cp'))
					{
						var area = $('box_content_cp');
						area.id = 'box_content';
						area.value = box_content.getCode();
						area.disabled = false;
					}
				}
			);
		// -->
		</script>
EOF;

			// build link bar and ACP footer
			asb_output_footer('edit_custom');
		}
	}

	$page->add_breadcrumb_item($lang->asb_custom_boxes);
	$page->output_header("{$lang->asb_name} - {$lang->asb_custom_boxes}");
	asb_output_tabs('asb_custom');

	$new_box_url = $html->url(array("action" => 'custom_boxes', "mode" => 'edit'));
	$new_box_link = $html->link($new_box_url, $lang->asb_add_custom_box_types, array("style" => 'font-weight: bold;', "title" => $lang->asb_add_custom_box_types, "icon" => "{$mybb->settings['bburl']}/inc/plugins/asb/images/add.png"), array("alt" => '+', "style" => 'margin-bottom: -3px;', "title" => $lang->asb_add_custom_box_types));
	echo($new_box_link . '<br /><br />');

	$table = new Table;
	$table->construct_header($lang->asb_name);
	$table->construct_header($lang->asb_custom_box_desc);
	$table->construct_header($lang->asb_controls, array("colspan" => 2));

	$custom = asb_get_all_custom();

	// if there are saved types . . .
	if(is_array($custom) && !empty($custom))
	{
		// display them
		foreach($custom as $this_custom)
		{
			$data = $this_custom->get('data');
			// name (edit link)
			$edit_url = $html->url(array("action" => 'custom_boxes', "mode" => 'edit', "id" => $data['id']));
			$edit_link = $html->link($edit_url, $data['title'], array("title" => $lang->asb_edit, "style" => 'font-weight: bold;'));

			$table->construct_cell($edit_link, array("width" => '30%'));

			// description
			if($data['description'])
			{
				$description = $data['description'];
			}
			else
			{
				$description = "<em>{$lang->asb_no_description}</em>";
			}
			$table->construct_cell($description, array("width" => '60%'));

			// options popup
			$popup = new PopupMenu('box_' . $data['id'], $lang->asb_options);

			// edit
			$popup->add_item($lang->asb_edit, $edit_url);

			// delete
			$popup->add_item($lang->asb_delete, $html->url(array("action" => 'custom_boxes', "mode" => 'delete', "id" => $data['id'])), "return confirm('{$lang->asb_custom_del_warning}');");

			// export
			$popup->add_item($lang->asb_custom_export, $html->url(array("action" => 'custom_boxes', "mode" => 'export', "id" => $data['id'])));

			// popup cell
			$table->construct_cell($popup->fetch(), array("width" => '10%'));

			// finish the table
			$table->construct_row();
		}
	}
	else
	{
		// no saved types
		$table->construct_cell($lang->asb_no_custom_boxes, array("colspan" => 4));
		$table->construct_row();
	}
	$table->output($lang->asb_custom_box_types);

	echo('<br /><br />');

	$import_form = new Form($html->url(array("action" => 'custom_boxes', "mode" => 'import')), 'post', '', 1);
	$import_form_container = new FormContainer($lang->asb_custom_import);
	$import_form_container->output_row($lang->asb_custom_import_select_file, '', $import_form->generate_file_upload_box('file'));
	$import_form_container->end();
	$import_buttons[] = $import_form->generate_submit_button($lang->asb_custom_import, array('name' => 'import'));
	$import_form->output_submit_wrapper($import_buttons);
	$import_form->end();

	// build link bar and ACP footer
	asb_output_footer('custom');
}

/*
 * asb_admin_manage_scripts()
 *
 * add/edit/delete script info
 */
function asb_admin_manage_scripts()
{
	global $mybb, $db, $page, $lang, $html;

	require_once MYBB_ROOT . 'inc/plugins/asb/classes/script_info.php';

	$page->add_breadcrumb_item($lang->asb, $html->url());

	if($mybb->request_method == 'post')
	{
		if($mybb->input['mode'] == 'edit')
		{
			$mybb->input['action'] = $mybb->input['script_action'];
			$script_info = new ScriptInfo($mybb->input);

			if($script_info->save())
			{
				flash_message($lang->asb_script_save_success, 'success');
				asb_cache_has_changed();
				$mybb->input['id'] = 0;
			}
			else
			{
				flash_message($lang->asb_script_save_fail, 'error');
			}
			unset($mybb->input['mode']);
		}
		elseif($mybb->input['mode'] == 'import')
		{
			if($_FILES['file'] && $_FILES['file']['error'] != 4)
			{
				if(!$_FILES['file']['error'])
				{
					if(is_uploaded_file($_FILES['file']['tmp_name']))
					{
						$contents = @file_get_contents($_FILES['file']['tmp_name']);
						@unlink($_FILES['file']['tmp_name']);

						if(trim($contents))
						{
							$this_script = new ScriptInfo;

							if($this_script->import($contents))
							{
								if($this_script->save())
								{
									flash_message($lang->asb_script_import_success, 'success');
									asb_cache_has_changed();
								}
								else
								{
									$error = $lang->asb_script_import_fail;
								}
							}
							else
							{
								$error = $lang->asb_script_import_fail;
							}
						}
						else
						{
							$error = $lang->asb_custom_import_file_empty;
						}
					}
					else
					{
						$error = $lang->asb_custom_import_file_upload_error;
					}
				}
				else
				{
					$error = $lang->sprintf($lang->asb_custom_import_file_error, $_FILES['file']['error']);
				}
			}
			else
			{
				$error = $lang->asb_custom_import_no_file;
			}

			if($error)
			{
				flash_message($error, 'error');
			}
		}
	}

	if($mybb->input['mode'] == 'delete' && $mybb->input['id'])
	{
		$this_script = new ScriptInfo((int) $mybb->input['id']);

		if($this_script->remove())
		{
			flash_message($lang->asb_script_delete_success, 'success');
			asb_cache_has_changed();
		}
		else
		{
			flash_message($lang->asb_script_delete_fail, 'error');
		}
	}
	elseif($mybb->input['mode'] == 'export' && $mybb->input['id'])
	{
		$this_script = new ScriptInfo((int) $mybb->input['id']);

		if($this_script->export())
		{
			flash_message($lang->asb_script_export_success, 'success');
		}
		else
		{
			flash_message($lang->asb_script_export_fail, 'error');
		}
	}
	elseif(($mybb->input['mode'] == 'activate' || $mybb->input['mode'] == 'deactivate') && $mybb->input['id'])
	{
		$this_script = new ScriptInfo((int) $mybb->input['id']);
		$this_script->set('active', ($mybb->input['mode'] == 'activate'));

		if($this_script->save())
		{
			$action = ($mybb->input['mode'] == 'activate') ? $lang->asb_script_activate_success : $lang->asb_script_deactivate_success;
			flash_message($action, 'success');
			asb_cache_has_changed();
		}
		else
		{
			$action = ($mybb->input['mode'] == 'activate') ? $lang->asb_script_activate_fail : $lang->asb_script_deactivate_fail;
			flash_message($action, 'error');
		}
	}

	$data = array
	(
		"active" => 'false',
		"find_top" => '{$header}',
		"find_bottom" => '{$footer}',
		"replace_all" => 0,
		"eval" => 0,
		"width_left" => 160,
		"width_right" => 160
	);
	if($mybb->input['mode'] == 'edit')
	{
		$this_script = new ScriptInfo((int) $mybb->input['id']);

		$detected_show = " style=\"display: none;\"";
		$button_text = $lang->asb_add;
		$filename = '';

		$action = $lang->asb_edit_script;
		if($this_script->is_valid())
		{
			$data = $this_script->get('data');

			$detected_info = asb_detect_script_info($data['filename']);
			$detected_show = '';
			$button_text = $lang->asb_update;
			$filename = $data['filename'];
			$action = "{$lang->asb_edit} {$data['title']}";
		}

		$queryadmin = $db->simple_select('adminoptions', '*', "uid='{$mybb->user['uid']}'");
		$admin_options = $db->fetch_array($queryadmin);

		if($admin_options['codepress'] != 0)
		{
			$page->extra_header .= <<<EOF
	<link type="text/css" href="./jscripts/codepress/languages/codepress-mybb.css" rel="stylesheet" id="cp-lang-style"/>
	<script type="text/javascript" src="./jscripts/codepress/codepress.js"></script>
	<script type="text/javascript">
	<!--
		CodePress.language = 'mybb';
	// -->
	</script>'
EOF;
		}

		$page->extra_header .= <<<EOF
	<script type="text/javascript" src="./jscripts/peeker.js"></script>
	<script type="text/javascript">
	<!--
		var edit_script = '{$filename}';
	// -->
	</script>
	<script type="text/javascript" src="jscripts/asb_scripts.js"></script>
EOF;
		$page->add_breadcrumb_item($lang->asb_manage_scripts, $html->url(array("action" => 'manage_scripts')));
		$page->add_breadcrumb_item($lang->asb_edit_script);
		$page->output_header("{$lang->asb} - {$lang->asb_manage_scripts} - {$action}");
		asb_output_tabs('asb_edit_script');

		$spinner = <<<EOF
<div class="ajax_spinners" style="display: none;">
	<img src="../images/spinner.gif" alt="{$lang->asb_detecting} . . ." title="{$lang->asb_detecting} . . ."/><br /><br />
</div>
EOF;

		$form = new Form($html->url(array("action" => 'manage_scripts', "mode" => 'edit')), 'post', 'edit_script');
		$form_container = new FormContainer("{$button_text} <em>{$data['title']}</em>");

		$form_container->output_row("{$lang->asb_title}:", $lang->asb_title_desc, $form->generate_text_box('title', $data['title']));

		$form_container->output_row("{$lang->asb_filename}:", $lang->asb_filename_desc, $form->generate_text_box('filename', $data['filename'], array("id" => 'filename')));
		$form_container->output_row("{$lang->asb_action}:", $lang->sprintf($lang->asb_scriptvar_generic_desc, strtolower($lang->asb_action)), "{$spinner}<div id=\"action_list\"{$detected_show}>{$detected_info['actions']}</div>" . $form->generate_text_box('script_action', $data['action'], array("id" => 'action')));
		$form_container->output_row($lang->asb_page, $lang->sprintf($lang->asb_scriptvar_generic_desc, strtolower($lang->asb_page)), $form->generate_text_box('page', $data['page']));

		$form_container->output_row($lang->asb_width_left, $lang->asb_width_left_desc, $form->generate_text_box('width_left', $data['width_left']));
		$form_container->output_row($lang->asb_width_right, $lang->asb_width_right_desc, $form->generate_text_box('width_right', $data['width_right']));

		$form_container->output_row("{$lang->asb_output_to_vars}?", $lang->sprintf($lang->asb_output_to_vars_desc, '<span style="font-family: courier; font-weight: bold; font-size: 1.2em;">$asb_left</span> and <span style="font-family: courier; font-weight: bold; font-size: 1.2em;";>$asb_right</span>'), $form->generate_yes_no_radio('eval', $data['eval'], true, array("id" => 'eval_yes', "class" => 'eval'), array("id" => 'eval_no', "class" => 'eval')), '', '', array("id" => 'var_output'));

		$form_container->output_row("{$lang->asb_template}:", $lang->asb_template_desc, "{$spinner}<div id=\"template_list\"{$detected_show}>{$detected_info['templates']}</div>" . $form->generate_text_box('template_name', $data['template_name'], array("id" => 'template_name')), '', '', array("id" => 'template_row'));
		$form_container->output_row("{$lang->asb_hook}:", $lang->asb_hook_desc, "{$spinner}<div id=\"hook_list\"{$detected_show}>{$detected_info['hooks']}</div>" . $form->generate_text_box('hook', $data['hook'], array("id" => 'hook')), '', '', array("id" => 'hook_row'));

		$form_container->output_row($lang->asb_header_search_text, $lang->asb_header_search_text_desc, $form->generate_text_area('find_top', $data['find_top'], array("id" => 'find_top', 'class' => 'codepress mybb', 'style' => 'width: 100%; height: 100px;')), '', '', array("id" => 'header_search'));
		$form_container->output_row($lang->asb_footer_search_text, $lang->asb_footer_search_text_desc, $form->generate_text_area('find_bottom', $data['find_bottom'], array("id" => 'find_bottom', 'class' => 'codepress mybb', 'style' => 'width: 100%; height: 100px;')) . $form->generate_hidden_field('id', $data['id']) . $form->generate_hidden_field('active', $data['active']) . $form->generate_hidden_field('action', 'manage_scripts') . $form->generate_hidden_field('mode', 'edit'), '', '', array("id" => 'footer_search'));

		$form_container->output_row($lang->asb_replace_template, $lang->asb_replace_template_desc, $form->generate_yes_no_radio('replace_all', $data['replace_all'], true, array("id" => 'replace_all_yes', "class" => 'replace_all'), array("id" => 'replace_all_no', "class" => 'replace_all')), '', '', array("id" => 'replace_all'));

		$form_container->output_row($lang->asb_replacement_content, $lang->asb_replacement_content_desc, $form->generate_text_area('replacement', $data['replacement'], array("id" => 'replacement', 'class' => 'codepress mybb', 'style' => 'width: 100%; height: 240px;')), '', '', array("id" => 'replace_content'));
		//$form_container->output_row("{$lang->asb_replacement_template}:", $lang->asb_replacement_template_desc, $form->generate_text_box('replacement_template', $data['replacement_template']), '', '', array("id" => 'replace_template'));

		$form_container->end();

		$buttons = array($form->generate_submit_button($button_text, array('name' => 'add')));
		$form->output_submit_wrapper($buttons);
		$form->end();

		// output CodePress scripts if necessary
		if($admin_options['codepress'] != 0)
		{
			echo <<<EOF
		<script type="text/javascript">
		<!--
			Event.observe
			(
				'edit_script',
				'submit',
				function()
				{
					if($('find_top_cp'))
					{
						var area = $('find_top_cp');
						area.id = 'find_top';
						area.value = find_top.getCode();
						area.disabled = false;
					}
				}
			);
			Event.observe
			(
				'edit_script',
				'submit',
				function()
				{
					if($('find_bottom_cp'))
					{
						var area = $('find_bottom_cp');
						area.id = 'find_bottom';
						area.value = find_bottom.getCode();
						area.disabled = false;
					}
				}
			);
			Event.observe
			(
				'edit_script',
				'submit',
				function()
				{
					if($('replacement_cp'))
					{
						var area = $('replacement_cp');
						area.id = 'replacement';
						area.value = replacement.getCode();
						area.disabled = false;
					}
				}
			);
		// -->
		</script>
EOF;
		}

		// output the link menu and MyBB footer
		asb_output_footer('edit_scripts');
	}
	else
	{
		$page->add_breadcrumb_item($lang->asb_manage_scripts);
		$page->output_header("{$lang->asb_name} - {$lang->asb_manage_scripts}");
		asb_output_tabs('asb_scripts');

		$new_script_url = $html->url(array("action" => 'manage_scripts', "mode" => 'edit'));
		$new_script_link = $html->link($new_script_url, $lang->asb_add_new_script, array("style" => 'font-weight: bold;', "title" => $lang->asb_add_new_script, "icon" => "{$mybb->settings['bburl']}/inc/plugins/asb/images/add.png"), array("alt" => '+', "title" => $lang->asb_add_new_script, "style" => 'margin-bottom: -3px;'));
		echo($new_script_link . '<br /><br />');

		$table = new Table;
		$table->construct_header($lang->asb_title, array("width" => '16%'));
		$table->construct_header($lang->asb_filename, array("width" => '16%'));
		$table->construct_header($lang->asb_action, array("width" => '7%'));
		$table->construct_header($lang->asb_page, array("width" => '7%'));
		$table->construct_header($lang->asb_template, array("width" => '18%'));
		$table->construct_header($lang->asb_hook, array("width" => '20%'));
		$table->construct_header($lang->asb_status, array("width" => '7%'));
		$table->construct_header($lang->asb_controls, array("width" => '8%'));

		$query = $db->simple_select('asb_script_info');
		if($db->num_rows($query) > 0)
		{
			while($data = $db->fetch_array($query))
			{
				$edit_url = $html->url(array("action" => 'manage_scripts', "mode" => 'edit', "id" => $data['id']));
				$activate_url = $html->url(array("action" => 'manage_scripts', "mode" => 'activate', "id" => $data['id']));
				$deactivate_url = $html->url(array("action" => 'manage_scripts', "mode" => 'deactivate', "id" => $data['id']));
				$activate_link = $html->link($activate_url, $lang->asb_inactive, array("style" => 'font-weight: bold; color: red;', "title" => $lang->asb_inactive_desc));
				$deactivate_link = $html->link($deactivate_url, $lang->asb_active, array("style" => 'font-weight: bold; color: green', "title" => $lang->asb_active_desc));
				$none = <<<EOF
<span style="color: gray;"><em>{$lang->asb_none}</em></span>
EOF;

				$table->construct_cell($html->link($edit_url, $data['title'], array("style" => 'font-weight: bold;')));
				$table->construct_cell($data['filename']);
				$table->construct_cell($data['action'] ? $data['action'] : $none);
				$table->construct_cell($data['page'] ? $data['page'] : $none);
				$table->construct_cell($data['template_name']);
				$table->construct_cell($data['hook']);
				$table->construct_cell($data['active'] ? $deactivate_link : $activate_link);

				// options popup
				$popup = new PopupMenu("script_{$data['id']}", $lang->asb_options);

				// edit
				$popup->add_item($lang->asb_edit, $edit_url);

				// export
				$popup->add_item($lang->asb_custom_export, $html->url(array("action" => 'manage_scripts', "mode" => 'export', "id" => $data['id'])));

				// delete
				$popup->add_item($lang->asb_delete, $html->url(array("action" => 'manage_scripts', "mode" => 'delete', "id" => $data['id'])), "return confirm('{$lang->asb_script_del_warning}');");

				// popup cell
				$table->construct_cell($popup->fetch());
				$table->construct_row();
			}
		}
		else
		{
			$table->construct_cell("<span style=\"color: gray;\"><em>{$lang->asb_no_scripts}</em></span>", array("colspan" => 8));
			$table->construct_row();
		}
		$table->output($lang->asb_script_info);

		$form = new Form($html->url(array("action" => 'manage_scripts', "mode" => 'import')), 'post', '', 1);
		$form_container = new FormContainer($lang->asb_custom_import);
		$form_container->output_row($lang->asb_custom_import_select_file, '', $form->generate_file_upload_box('file'));
		$form_container->end();
		$import_buttons[] = $form->generate_submit_button($lang->asb_custom_import, array('name' => 'import'));
		$form->output_submit_wrapper($import_buttons);
		$form->end();

		// output the link menu and MyBB footer
		asb_output_footer('manage_scripts');
	}
}

/*
 * asb_admin_manage_modules()
 *
 * view and delete add-ons
 */
function asb_admin_manage_modules()
{
	global $lang, $mybb, $db, $page, $html;

	$page->add_breadcrumb_item($lang->asb, $html->url());
	$page->add_breadcrumb_item($lang->asb_manage_modules);

	$page->output_header("{$lang->asb} - {$lang->asb_manage_modules}");
	asb_output_tabs('asb_modules');

	$table = new Table;
	$table->construct_header($lang->asb_name);
	$table->construct_header($lang->asb_description);
	$table->construct_header($lang->asb_controls);

	$addons = asb_get_all_modules();

	// if there are installed modules display them
	if(!empty($addons) && is_array($addons))
	{
		foreach($addons as $this_module)
		{
			$data = $this_module->get(array('title', 'description', 'base_name'));

			// title
			$table->construct_cell($data['title']);

			// description
			$table->construct_cell($data['description']);

			// options pop-up
			$popup = new PopupMenu('module_' . $data['base_name'], $lang->asb_options);

			// delete
			$popup->add_item($lang->asb_delete, $html->url(array("action" => 'delete_addon', "addon" => $data['base_name'])), "return confirm('{$lang->asb_modules_del_warning}');");

			// pop-up cell
			$table->construct_cell($popup->fetch(), array("width" => '10%'));

			// finish row
			$table->construct_row();
		}
	}
	else
	{
		$table->construct_cell("<span style=\"color: gray;\">{$lang->asb_no_modules_detected}</span>", array("colspan" => 3));
		$table->construct_row();
	}
	$table->output($lang->asb_addon_modules);

	// build link bar and ACP footer
	asb_output_footer('addons');
}

/*
 * asb_admin_xmlhttp()
 *
 * handler for AJAX side box routines
 */
function asb_admin_xmlhttp()
{
	global $db, $mybb;

	// if ordering (or trashing)
	if($mybb->input['mode'] == 'order')
	{
		$left_column = '';
		$right_column = '';
		parse_str($mybb->input['data']);

		if($mybb->input['pos'] == 'trash_column')
		{
			// if there is anything in the side box
			if(is_array($trash_column) && !empty($trash_column))
			{
				// loop through them all
				foreach($trash_column as $id)
				{
					$sidebox = new Sidebox($id);

					// and if they are valid side boxes
					if($sidebox->is_valid())
					{
						// remove them
						$sidebox->remove();
						asb_cache_has_changed();

						// return the removed side boxes id to the AJAX object (so that the div can be destroyed as well)
						echo($id);
					}
				}
			}
		}
		elseif($mybb->input['pos'] == 'right_column')
		{
			$pos = 1;
			$this_column = $right_column;
		}
		elseif($mybb->input['pos'] == 'left_column')
		{
			$pos = 0;
			$this_column = $left_column;
		}

		// if there are side boxes in this column after the move (this function is called by onUpdate)
		if(is_array($this_column) && !empty($this_column))
		{
			$disp_order = 1;

			// loop through all the side boxes in this column
			foreach($this_column as $id)
			{
				$has_changed = false;

				$sidebox = new Sidebox($id);

				// get some info
				$this_order = (int) ($disp_order * 10);
				++$disp_order;
				$current_order = $sidebox->get('display_order');
				$original_pos = $sidebox->get('position');

				// if the order has been edited
				if($current_order != $this_order)
				{
					// handle it
					$sidebox->set('display_order', $this_order);
					$has_changed = true;
				}

				// if the position has changed
				if($original_pos != $pos)
				{
					// alter it
					$sidebox->set('position', $pos);
					$has_changed = true;
				}

				// if the side box has been modified
				if($has_changed != false)
				{
					// save it
					$sidebox->save();
					asb_cache_has_changed();
				}
			}
		}
	}
	// this routine allows the side box's visibility tool tip and links to be handled by JS after the side box is created
	elseif($mybb->input['mode'] == 'build_info' && (int) $mybb->input['id'] > 0)
	{
		$id = (int) $mybb->input['id'];
		$sidebox = new Sidebox($id);

		// we have to reaffirm our observance of the edit link when it is added/updated
		$script = <<<EOF
<script type="text/javascript">
Event.observe
(
'edit_sidebox_{$id}',
'click',
function(event)
{
	// stop the link from redirecting the user-- set up this way so that if JS is disabled the user goes to a standard form rather than a modal edit form
	Event.stop(event);

	// create the modal edit box dialogue
	new MyModal
	(
		{
			type: 'ajax',
			url: this.readAttribute('href') + '&ajax=1'
		}
	);
}
);
</script>
EOF;
		// this HTML output will be directly stored in the side box's representative <div>
		echo(asb_build_sidebox_info($sidebox, false, true) . $script);
	}
	/*
	 * searches for hooks, templates and actions and returns an
	 * array of JSON encoded select box HTML for any that are found
	 */
	elseif($mybb->input['mode'] == 'analyze_script' && trim($mybb->input['filename']))
	{
		echo(json_encode(asb_detect_script_info($mybb->input['filename'])));
	}
}

/*
 * asb_admin_delete_box()
 *
 * remove a side box (only still around for those without JS . . . like who, idk)
 */
function asb_admin_delete_box()
{
	global $mybb, $lang, $html;

	if((int) $mybb->input['id'] > 0)
	{
		$sidebox = new Sidebox($mybb->input['id']);

		if($sidebox->is_valid())
		{
			if($status = $sidebox->remove())
			{
				flash_message($lang->asb_delete_box_success, "success");
				asb_cache_has_changed();
			}
		}
		else
		{
			flash_message($lang->asb_delete_box_failure, "error");
		}
	}
	else
	{
		flash_message($lang->asb_delete_box_failure, "error");
	}
	admin_redirect($html->url());
}

/*
 * asb_admin_delete_addon()
 *
 * completely remove an add-on module
 */
function asb_admin_delete_addon()
{
	global $mybb, $html;

	// info goof?
	if(isset($mybb->input['addon']))
	{
		$this_module = new Addon_type($mybb->input['addon']);

		if($this_module->is_valid())
		{
			if($this_module->remove())
			{
				// yay
				asb_cache_has_changed();
				flash_message($lang->asb_delete_addon_success, "success");
				admin_redirect($html->url(array("action" => 'manage_modules')));
			}
		}
	}

	// why me?
	flash_message($lang->asb_delete_addon_failure, "error");
	admin_redirect($html->url(array("action" => 'manage_modules')));
}

/*
 * asb_admin_update_theme_select()
 *
 * rebuild the theme exclude list.
 */
function asb_admin_update_theme_select()
{
	global $mybb, $db, $lang;

	if(!$lang->asb)
	{
		$lang->load('asb');
	}

	$gid = asb_get_settingsgroup();

	// is the group installed?
	if($gid)
	{
		$query = $db->simple_select('settings', '*', "name='asb_exclude_theme'");

		// is the setting created?
		if($db->num_rows($query) == 1)
		{
			// update the setting
			require_once MYBB_ROOT . 'inc/plugins/asb/functions_install.php';
			$update_array = $db->fetch_array($query);
			$update_array['optionscode'] = $db->escape_string(asb_build_theme_exclude_select());
			$status = $db->update_query('settings', $update_array, "sid='{$update_array['sid']}'");

			// success?
			if($status)
			{
				// tell them :)
				flash_message($lang->asb_theme_exclude_select_update_success, "success");
				admin_redirect(asb_build_settings_url($gid));
			}
		}
	}

	// settings group doesn't exist
	flash_message($lang->asb_theme_exclude_select_update_fail, "error");
	admin_redirect('index.php?module=config-settings');
}

/*
 * asb_serialize()
 *
 * serialize the theme exclusion list selector
 */
$plugins->add_hook("admin_config_settings_change", "asb_admin_config_settings_change");
function asb_admin_config_settings_change()
{
    global $mybb;

    // only serialize our setting if it is being saved (thanks to Tanweth for helping me find this)
	if(isset($mybb->input['upsetting']['asb_exclude_theme']))
	{
		$mybb->input['upsetting']['asb_exclude_theme'] = serialize($mybb->input['upsetting']['asb_exclude_theme']);
	}
}

/*
 * asb_admin_action(&$action)
 *
 * @param - &$action is an array containing the list of selectable items on the config tab
 */
$plugins->add_hook('admin_config_action_handler', 'asb_admin_config_action_handler');
function asb_admin_config_action_handler(&$action)
{
	$action['asb'] = array('active' => 'asb');
}

/*
 * asb_admin_menu()
 *
 * Add an entry to the ACP Config page menu
 *
 * @param - &$sub_menu is the menu array we will add a member to
 */
$plugins->add_hook('admin_config_menu', 'asb_admin_config_menu');
function asb_admin_config_menu(&$sub_menu)
{
	global $lang;
	if(!$lang->asb)
	{
		$lang->load('asb');
	}
	$asb_menuitem = array
		(
			'id' 		=> 'asb',
			'title' 	=> $lang->asb,
			'link' 		=> ASB_URL
		);

	end($sub_menu);
	$key = (key($sub_menu)) + 10;
	$sub_menu[$key] = $asb_menuitem;

}

/*
 * asb_admin_permissions()
 *
 * Add an entry to admin permissions list
 *
 * @param - &$admin_permissions is the array of permission types we are adding an element to
 */
$plugins->add_hook('admin_config_permissions', 'asb_admin_config_permissions');
function asb_admin_config_permissions(&$admin_permissions)
{
	global $lang;

	if(!$lang->asb)
	{
		$lang->load('asb');
	}
	$admin_permissions['asb'] = $lang->asb_admin_permissions_desc;
}

?>

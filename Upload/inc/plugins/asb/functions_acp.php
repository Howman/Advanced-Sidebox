<?php
/*
 * Plug-in Name: Advanced Sidebox for MyBB 1.6.x
 * Copyright 2013 WildcardSearch
 * http://www.wildcardsworld.com
 *
 * this file contains the functions used in ACP and depends upon html_generator.php
 */

/*
 * asb_build_help_link()
 *
 * produces a link to a particular page in the plug-in help system (with icon) specified by topic
 *
 * @param - $topic is the intended page's topic keyword
 */
function asb_build_help_link($topic = '')
{
	global $mybb, $lang, $html;

	if(!$topic)
	{
		$topic = 'manage_sideboxes';
	}

	$help_url = $html->url(array("topic" => $topic), "{$mybb->settings['bburl']}/inc/plugins/asb/help/index.php");
	$help_link = $html->link($help_url, $lang->asb_help, array("style" => 'font-weight: bold;', "icon" => "{$mybb->settings['bburl']}/images/toplinks/help.gif", "title" => $lang->asb_help, "onclick" => "window.open('{$help_url}', 'mywindowtitle', 'width=840, height=520, scrollbars=yes'); return false;"), array("alt" => '?', "title" => $lang->asb_help, "style" => 'margin-bottom: -3px;'));
	return $help_link;
}

/*
 * asb_build_settings_menu_link()
 *
 * produces a link to the plug-in settings with icon
 */
function asb_build_settings_menu_link()
{
	global $lang, $html;

	$settings_url = asb_build_settings_url(asb_get_settingsgroup());
	$settings_link = $html->link($settings_url, $lang->asb_plugin_settings, array("icon" => 'styles/default/images/icons/custom.gif', "style" => 'font-weight: bold;', "title" => $lang->asb_plugin_settings), array("alt" => 'S', "style" => 'margin-bottom: -3px;'));
	return $settings_link;
}

/*
 * asb_output_header()
 *
 * Output ACP headers for our main page
 */
function asb_output_header($title)
{
    global $mybb, $admin_session, $lang, $plugins, $lang, $page;

	$plugins->run_hooks("admin_page_output_header");

	$rtl = "";
	if($lang->settings['rtl'] == 1)
	{
		$rtl = " dir=\"rtl\"";
	}

	echo <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"{$rtl}>
	<head profile="http://gmpg.org/xfn/1">
		<title>{$title}</title>
		<meta name="author" content="MyBB Group"/>

EOF;

	echo("		<meta name=\"copyright\" content=\"Copyright " . COPY_YEAR . " MyBB Group.\"/>\n");

	echo <<<EOF
		<link rel="stylesheet" href="styles/{$page->style}/main.css" type="text/css" />

EOF;

	// Load style sheet for this module if it has one
	if(file_exists(MYBB_ADMIN_DIR . "styles/{$page->style}/{$page->active_module}.css"))
	{
		echo <<<EOF
		<link rel="stylesheet" href="styles/{$page->style}/{$page->active_module}.css" type="text/css" />

EOF;
	}

	echo <<<EOF
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/prototype/1.7.0.0/prototype.js"></script>
		<script type="text/javascript" src="../jscripts/general.js"></script>
		<script type="text/javascript" src="../jscripts/popup_menu.js"></script>
		<script type="text/javascript" src="./jscripts/admincp.js"></script>
		<script type="text/javascript" src="./jscripts/tabs.js"></script>

EOF;

	// Stop JS elements showing while page is loading (JS supported browsers only)
	echo <<<EOF
		<style type="text/css">
			.popup_button { display: none; }
		</style>
		<script type="text/javascript">
			//<![CDATA[
				document.write('<style type="text/css">.popup_button { display: inline; } .popup_menu { display: none; }<\/style>');
			//]]>
		</script>
		<script type="text/javascript">
			//<![CDATA[
			var loading_text = '{$lang->loading_text}';
			var cookieDomain = '{$mybb->settings['cookiedomain']}';
			var cookiePath = '{$mybb->settings['cookiepath']}';
			var cookiePrefix = '{$mybb->settings['cookieprefix']}';
			var imagepath = '../images';
			//]]>
		</script>

		{$page->extra_header}
	</head>
	<body>
		<div id="container">
		<div id="logo"><h1><span class="invisible">{$lang->mybb_admin_cp}</span></h1></div>
		<div id="welcome">
			<span class="logged_in_as">{$lang->logged_in_as} <a href="index.php?module=user-users&amp;action=edit&amp;uid={$mybb->user['uid']}" class="username">{$mybb->user['username']}</a></span> | <a href="{$mybb->settings['bburl']}" target="_blank" class="forum">{$lang->view_board}</a> | <a href="index.php?action=logout&amp;my_post_key={$mybb->post_code}" class="logout">{$lang->logout}</a>
		</div>

EOF;

	echo $page->_build_menu();

	echo <<<EOF
	<div id="page">
		<div id="left_menu">
EOF;
	echo $page->submenu;
	echo $page->sidebar;
	echo <<<EOF
	</div>
		<div id="content">
			<div class="breadcrumb">
EOF;
	echo $page->_generate_breadcrumb();
	echo <<<EOF
	</div>
		<div id="inner">
EOF;

	if(isset($admin_session['data']['flash_message']) && $admin_session['data']['flash_message'])
	{
		$message = $admin_session['data']['flash_message']['message'];
		$type = $admin_session['data']['flash_message']['type'];
		echo <<<EOF
	<div id="flash_message" class="{$type}">
		{$message}
		</div>
EOF;
		update_admin_session('flash_message', '');
	}
	if($page->show_post_verify_error == true)
	{
		$page->output_error($lang->invalid_post_verify_key);
	}
}

/*
 * asb_output_tabs()
 *
 * Output ACP tabs for our pages
 *
 * @param - $current is the tab currently being viewed
 */
function asb_output_tabs($current)
{
	global $page, $lang, $mybb, $html;

	// set up tabs
	$sub_tabs['asb'] = array
	(
		'title' 				=> $lang->asb_manage_sideboxes,
		'link' 					=> $html->url(),
		'description' 		=> $lang->asb_manage_sideboxes_desc
	);
	$sub_tabs['asb_custom'] = array
	(
		'title'					=> $lang->asb_custom_boxes,
		'link'					=> $html->url(array("action" => 'custom_boxes')),
		'description'		=> $lang->asb_custom_boxes_desc
	);
	if(in_array($current, array('asb_add_custom', 'asb_custom')))
	{
		$sub_tabs['asb_add_custom'] = array
		(
			'title'					=> $lang->asb_add_custom,
			'link'					=> $html->url(array("action" => 'custom_boxes', "mode" => 'edit')),
			'description'		=> $lang->asb_add_custom_desc
		);
	}
	$sub_tabs['asb_scripts'] = array
	(
		'title'					=> $lang->asb_manage_scripts,
		'link'					=> $html->url(array("action" => 'manage_scripts')),
		'description'		=> $lang->asb_manage_scripts_desc
	);
	if(in_array($current, array('asb_edit_script', 'asb_scripts')))
	{
		$sub_tabs['asb_edit_script'] = array
		(
			'title'					=> $lang->asb_edit_script,
			'link'					=> $html->url(array("action" => 'manage_scripts', "mode" => 'edit')),
			'description'		=> $lang->asb_edit_script_desc
		);
	}
	$sub_tabs['asb_modules'] = array
	(
		'title'					=> $lang->asb_manage_modules,
		'link'					=> $html->url(array("action" => 'manage_modules')),
		'description'		=> $lang->asb_manage_modules_desc
	);
	$page->output_nav_tabs($sub_tabs, $current);
}

/*
 * asb_output_footer()
 *
 * Output ACP footers for our pages
 */
function asb_output_footer($page_key)
{
    global $page;

	echo(asb_build_footer_menu($page_key));
	$page->output_footer();
}

/*
 * asb_build_footer_menu()
 *
 * @param - $page_key is the topic key name for the current page
 */
function asb_build_footer_menu($page_key = '')
{
	global $mybb, $lang;

	if(!$page_key)
	{
		$page_key = 'manage_sideboxes';
	}

	$help_link = '&nbsp;' . asb_build_help_link($page_key);
	$settings_link = '&nbsp;' . asb_build_settings_menu_link();

	switch($page_key)
	{
		case "manage_sideboxes":
			$filter_links = asb_build_filter_selector($mybb->input['page']);
			break;
	}

	return <<<EOF

<div class="asb_label">
{$filter_links}
	{$module_info}
	{$settings_link}
	{$help_link}
</div>

EOF;
}

/*
 * asb_build_permissions_table()
 *
 * @param - $id is the numeric id of the sidebox
 */
function asb_build_permissions_table($id)
{
	if($id)
	{
		global $db, $lang, $all_scripts;

		$sidebox = new Sidebox($id);
		
		if(!$sidebox->is_valid())
		{
			return;
		}
		
		// prepare options for which groups
		$options = array('Guests');
		$groups = array();

		// look for all groups except Super Admins
		$query = $db->simple_select("usergroups", "gid, title", "gid != '1'", array('order_by' => 'gid'));
		while($usergroup = $db->fetch_array($query))
		{
			// store the titles by group id
			$options[(int)$usergroup['gid']] = $usergroup['title'];
		}

		$groups = $sidebox->get('groups');
		$scripts = $sidebox->get('scripts');

		if(empty($scripts))
		{
			if(empty($groups))
			{
				return $lang->asb_globally_visible;
			}
			elseif(isset($groups[0]) && strlen($groups[0]) == 0)
			{
				return $lang->asb_all_scripts_deactivated;
			}
			else
			{
				$scripts = $all_scripts;
			}
		}
		elseif(isset($scripts[0]) && strlen($scripts[0]) == 0)
		{
			return $lang->asb_all_scripts_deactivated;
		}

		if(is_array($all_scripts))
		{
			$all_group_count = count($options);
			$info = <<<EOF

<table width="100%" class="box_info">
	<tr>
		<td class="group_header"><strong>{$lang->asb_visibility}</strong></td>

EOF;

			foreach($options as $gid => $title)
			{
				$info .= <<<EOF
		<td title="{$title}" class="group_header">{$gid}</td>

EOF;
			}

			$info .= '	</tr>
';

			foreach($all_scripts as $script => $script_title)
			{
				$script_title_full = '';
				if(strlen($script_title) > 8)
				{
					$script_title_full = $script_title;
					$script_title = substr($script_title, 0, 8) . '. . .';
				}
				$info .= <<<EOF
	<tr>
		<td class="script_header" title="{$script_title_full}">{$script_title}</td>

EOF;
				if(empty($scripts) || array_key_exists($script, $scripts) || in_array($script, $scripts))
				{
					if(empty($groups))
					{
						$x = 1;
						while($x <= $all_group_count)
						{
							$info .= <<<EOF
		<td class="info_cell on"></td>

EOF;
							++$x;
						}
					}
					else
					{
						foreach($options as $gid => $title)
						{
							if(in_array($gid, $groups))
							{
								$info .= <<<EOF
	<td class="info_cell on"></td>

EOF;
							}
							else
							{
								$info .= <<<EOF
	<td class="info_cell off"></td>

EOF;
							}
						}
					}
				}
				else
				{
					$x = 1;
					while($x <= $all_group_count)
					{
						$info .= <<<EOF
		<td class="info_cell off"></td>

EOF;
						++$x;
					}
				}

				$info .= '	</tr>
';
			}

			$info .= '</table>';
		}
		return $info;
	}
}

/*
 * asb_build_sidebox_info()
 *
 * @param - $sidebox Sidebox type object xD
 * @param - $wrap specifies whether to produce the <div> or just the contents
 * @param - $ajax specifies whether to produce the delete link or not
 */
function asb_build_sidebox_info($sidebox, $wrap = true, $ajax = false)
{
	global $html, $scripts, $all_scripts, $lang;

	// must be a valid object
	if($sidebox instanceof Sidebox)
	{
		$title = $sidebox->get('title');
		$id = $sidebox->get('id');
		$pos = $sidebox->get('position');
		$module = $sidebox->get('box_type');

		// visibility table
		$visibility = '<span class="custom info">' . asb_build_permissions_table($id) . '</span>';

		// edit link
		$edit_link = $html->url(array("action" => 'edit_box', "id" => $id, "addon" => $module, "pos" => $pos));
		$edit_icon = <<<EOF
<a href="{$edit_link}" class="info_icon" id="edit_sidebox_{$id}" title="{$lang->asb_edit}"><img src="../inc/plugins/asb/images/edit.png" height="18" width="18" alt="{$lang->asb_edit}"/></a>
EOF;

		// delete link (only used if JS is disabled)
		if(!$ajax)
		{
			$delete_link = $html->url(array("action" => 'delete_box', "id" => $id));
			$delete_icon = "<a href=\"{$delete_link}\" class=\"del_icon\" title=\"{$lang->asb_delete}\"><img src=\"../inc/plugins/asb/images/delete.png\" height=\"18\" width=\"18\" alt=\"{$lang->asb_delete}\"/></a>";
		}

		// the content
		$box = <<<EOF
<span class="tooltip"><img class="info_icon" src="../inc/plugins/asb/images/visibility.png" alt="Information" height="18" width="18"/>{$visibility}</span>{$edit_icon}{$delete_icon}{$title}
EOF;

		// the <div> (if applicable)
		if($wrap)
		{
			$box = <<<EOF
<div id="sidebox_{$id}" class="sidebox">{$box}</div>

EOF;
		}

		// return the content (which will either be stored in a string and displayed by asb_main() or will be stored directly in the <div> when called from AJAX
		return $box;
	}
}

/*
 * asb_cache_has_changed()
 *
 *
 */
function asb_cache_has_changed()
{
	global $cache;

	$asb = $cache->read('asb');
	$asb['has_changed'] = true;
	$cache->update('asb', $asb);
}

/*
 * asb_detect_script_info($filename)
 *
 * searches for hooks, templates and actions and returns a
 * keyed array of select box HTML for any that are found
 */
function asb_detect_script_info($filename)
{
	global $lang;

	// check all the info
	if(strlen(trim($filename)) == 0)
	{
		return false;
	}

	$full_path = '../' . trim($filename);
	if(!file_exists($full_path))
	{
		return false;
	}

	$contents = @file_get_contents($full_path);
	if(!$contents)
	{
		return false;
	}

	// build the object info
	$info = array
	(
		"hook" => array
		(
			"pattern" => "#\\\$plugins->run_hooks\([\"|'|&quot;]([\w|_]*)[\"|'|&quot;](.*?)\)#i",
			"filter" => '_do_',
			"plural" => $lang->asb_hooks
		),
		"template" => array
		(
			"pattern" => "#\\\$templates->get\([\"|'|&quot;]([\w|_]*)[\"|'|&quot;](.*?)\)#i",
			"filter" => '',
			"plural" => $lang->asb_templates
		),
		"action" => array
		(
			"pattern" => "#\\\$mybb->input\[[\"|'|&quot;]action[\"|'|&quot;]\] == [\"|'|&quot;]([\w|_]*)[\"|'|&quot;]#i",
			"filter" => '',
			"plural" => $lang->asb_actions
		)
	);

	$form = new Form('', '', '', 0, '', true);
	foreach(array('hook', 'template', 'action') as $key)
	{
		$array_name = "{$key}s";
		$$array_name = array();

		// find any references to the current object
		preg_match_all($info[$key]['pattern'], $contents, $matches, PREG_SET_ORDER);
		foreach($matches as $match)
		{
			// no duplicates and if there is a filter check it
			if(!in_array($match[1], $$array_name) && (strlen(${$array_name}['filter'] == 0 || strpos($match[1], ${$array_name}['filter']) === false)))
			{
				${$array_name}[$match[1]] = $match[1];
			}
		}

		// anything to show?
		if(!empty($$array_name))
		{
			// sort the results, preserving keys
			ksort($$array_name);

			// make none = '' the first entry
			$$array_name = array_reverse($$array_name);
			${$array_name}[] = 'none';
			$$array_name = array_reverse($$array_name);

			// store the HTML select box
			$return_array[$array_name] = '<span style="font-weight: bold;">' . $lang->asb_detected . ' ' . $info[$key]['plural'] . ':</span><br />' . $form->generate_select_box("{$array_name}_options", $$array_name, '', array("id" => "{$key}_selector")) . '<br /><br />';
		}
	}
	return $return_array;
}

/*
 * asb_legacy_custom_import($tree)
 *
 * imports XML files created with ASB 1.x series
 */
function asb_legacy_custom_import($tree)
{
	if(is_array($tree['adv_sidebox']['custom_sidebox']) && !empty($tree['adv_sidebox']['custom_sidebox']))
	{
		global $lang;

		foreach($tree['adv_sidebox']['custom_sidebox'] as $property => $value)
		{
			if($property == 'tag' || $property == 'value')
			{
				continue;
			}
			$input_array[$property] = $value['value'];
		}

		if($input_array['content'] && $input_array['checksum'] && my_strtolower(md5(base64_decode($input_array['content']))) == my_strtolower($input_array['checksum']))
		{
			$input_array['content'] = trim(base64_decode($input_array['content']));
			$input_array['title'] = $input_array['name'];
			return $input_array;
		}
		else
		{
			if($input_array['content'])
			{
				$error = $lang->asb_custom_import_file_corrupted;
			}
			else
			{
				$error = $lang->asb_custom_import_file_empty;
			}
		}
	}
	else
	{
		$error = $lang->asb_custom_import_file_corrupted;
	}
	return $error;
}

/*
 * asb_build_filter_selector()
 *
 * build links for ACP Manage Side Boxes screen
 *
 * @param - $filter is a string containing the script to show or 'all_scripts' to avoid filtering altogether
 */
function asb_build_filter_selector($filter)
{
	global $lang, $html, $all_scripts;

	// if there are active scripts . . .
	if(is_array($asb->all_scripts))
	{
		$options = array_merge(array("" => 'no filter'), $all_scripts);
		$form = new Form($html->url(), 'post', 'script_filter', 0, 'script_filter');
		echo($form->generate_select_box('page', $options, $filter));
		echo($form->generate_submit_button('Filter', array('name' => 'filter')));
		return $form->end();
	}
}

/*
 * asb_build_setting()
 *
 * creates a single setting from an associative array
 *
 * @param - $this_form is a valid object of class DefaultForm
 * @param - $this_form_container is a valid object of class DefaultFormContainer
 * @param - $setting is an associative array for the settings properties
 * @param - $sidebox is an integer representing the currently loaded box (edit) or 0 if adding a new side box
 * @param - $module is a valid Addon_type object (add-on module)
 */
function asb_build_setting($this_form, $this_form_container, $setting, $sidebox, $module)
{
	// create each element with unique id and name properties
	$options = "";
	$type = explode("\n", $setting['optionscode']);
	$type[0] = trim($type[0]);
	$element_name = "{$setting['name']}";
	$element_id = "setting_{$setting['name']}";

	// prepare labels
	$this_label = '<strong>' . $setting['title'] . '</strong>';
	$this_desc = '<i>' . $setting['description'] . '</i>';

	// sort by type
	if($type[0] == "text" || $type[0] == "")
	{
		$this_form_container->output_row($this_label, $this_desc, $this_form->generate_text_box($element_name, $setting['value'], array('id' => $element_id)), $element_name, array("id" => $element_id));
	}
	else if($type[0] == "textarea")
	{
		$this_form_container->output_row($this_label, $this_desc, $this_form->generate_text_area($element_name, $setting['value'], array('id' => $element_id)), $element_name, array('id' => $element_id));
	}
	else if($type[0] == "yesno")
	{
		$this_form_container->output_row($this_label, $this_desc, $this_form->generate_yes_no_radio($element_name, $setting['value'], true, array('id' => $element_id.'_yes', 'class' => $element_id), array('id' => $element_id.'_no', 'class' => $element_id)), $element_name, array('id' => $element_id));
	}
	else if($type[0] == "onoff")
	{
		$this_form_container->output_row($this_label, $this_desc, $this_form->generate_on_off_radio($element_name, $setting['value'], true, array('id' => $element_id.'_on', 'class' => $element_id), array('id' => $element_id.'_off', 'class' => $element_id)), $element_name, array('id' => $element_id));
	}
	else if($type[0] == "language")
	{
		$languages = $lang->get_languages();
		$this_form_container->output_row($this_label, $this_desc, $this_form->generate_select_box($element_name, $languages, $setting['value'], array('id' => $element_id)), $element_name, array('id' => $element_id));
	}
	else if($type[0] == "adminlanguage")
	{
		$languages = $lang->get_languages(1);
		$this_form_container->output_row($this_label, $this_desc, $this_form->generate_select_box($element_name, $languages, $setting['value'], array('id' => $element_id)), $element_name, array('id' => $element_id));
	}
	else if($type[0] == "passwordbox")
	{
		$this_form_container->output_row($this_label, $this_desc, $this_form->generate_password_box($element_name, $setting['value'], array('id' => $element_id)), $element_name, array('id' => $element_id));
	}
	else if($type[0] == "php")
	{
		$setting['optionscode'] = substr($setting['optionscode'], 3);
		eval("\$setting_code = \"" . $setting['optionscode'] . "\";");
	}
	else
	{
		for($i=0; $i < count($type); $i++)
		{
			$optionsexp = explode("=", $type[$i]);
			if(!$optionsexp[1])
			{
				continue;
			}
			$title_lang = "setting_{$setting['name']}_{$optionsexp[0]}";
			if($lang->$title_lang)
			{
				$optionsexp[1] = $lang->$title_lang;
			}

			if($type[0] == "select")
			{
				$option_list[$optionsexp[0]] = htmlspecialchars_uni($optionsexp[1]);
			}
			else if($type[0] == "radio")
			{
				if($setting['value'] == $optionsexp[0])
				{
					$option_list[$i] = $this_form->generate_radio_button($element_name, $optionsexp[0], htmlspecialchars_uni($optionsexp[1]), array('id' => $element_id.'_'.$i, "checked" => 1, 'class' => $element_id));
				}
				else
				{
					$option_list[$i] = $this_form->generate_radio_button($element_name, $optionsexp[0], htmlspecialchars_uni($optionsexp[1]), array('id' => $element_id.'_'.$i, 'class' => $element_id));
				}
			}
			else if($type[0] == "checkbox")
			{
				if($setting['value'] == $optionsexp[0])
				{
					$option_list[$i] = $this_form->generate_check_box($element_name, $optionsexp[0], htmlspecialchars_uni($optionsexp[1]), array('id' => $element_id.'_'.$i, "checked" => 1, 'class' => $element_id));
				}
				else
				{
					$option_list[$i] = $this_form->generate_check_box($element_name, $optionsexp[0], htmlspecialchars_uni($optionsexp[1]), array('id' => $element_id.'_'.$i, 'class' => $element_id));
				}
			}
		}
		if($type[0] == "select")
		{
			$this_form_container->output_row($this_label, $this_desc, $this_form->generate_select_box($element_name, $option_list, $setting['value'], array('id' => $element_id)), $element_name, array('id' => $element_id));
		}
		else
		{
			$setting_code = implode("<br />", $option_list);
		}
		$option_list = array();
	}
}

?>

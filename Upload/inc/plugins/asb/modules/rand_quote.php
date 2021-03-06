<?php
/*
 * Plug-in Name: Advanced Sidebox for MyBB 1.6.x
 * Copyright 2013 WildcardSearch
 * http://www.wildcardsworld.com
 *
 * ASB default module
 */

// Include a check for Advanced Sidebox
if(!defined("IN_MYBB") || !defined("IN_ASB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

/*
 * asb_rand_quote_info()
 *
 * gives the handler all the info it needs to handle the side box module and track its version and upgrades status
 */
function asb_rand_quote_info()
{
	global $lang;

	if(!$lang->asb_addon)
	{
		$lang->load('asb_addon');
	}

	return array
	(
		"title" => $lang->asb_random_quotes,
		"description" => $lang->asb_random_quotes_desc,
		"wrap_content" => true,
		"xmlhttp" => true,
		"version" => "1.4.3",
		"settings" => array
		(
			"forum_id" => array
			(
				"sid" => "NULL",
				"name" => "forum_id",
				"title" => $lang->asb_random_quotes_forums_title,
				"description" => $lang->asb_random_quotes_forums_desc,
				"optionscode" => "text",
				"value" => ''
			),
			"thread_id" => array
			(
				"sid" => "NULL",
				"name" => "thread_id",
				"title" => $lang->asb_thread_id_title,
				"description" => $lang->asb_thread_id_desc,
				"optionscode" => "text",
				"value" => ''
			),
			"min_length" => array
			(
				"sid" => "NULL",
				"name" => "min_length",
				"title" => $lang->asb_random_quotes_min_quote_length_title,
				"description" => $lang->asb_random_quotes_min_quote_length_desc,
				"optionscode" => "text",
				"value" => '20'
			),
			"max_length" => array
			(
				"sid" => "NULL",
				"name" => "max_length",
				"title" => $lang->asb_random_quotes_max_quote_length_title,
				"description" => $lang->asb_random_quotes_max_quote_length,
				"optionscode" => "text",
				"value" => '160'
			),
			"default_text" => array
			(
				"sid" => "NULL",
				"name" => "default_text",
				"title" => $lang->asb_random_quotes_default_text_title,
				"description" => $lang->asb_random_quotes_default_text_desc,
				"optionscode" => "text",
				"value" => ''
			),
			"xmlhttp_on" => array
			(
				"sid" => "NULL",
				"name" => "xmlhttp_on",
				"title" => $lang->asb_xmlhttp_on_title,
				"description" => $lang->asb_xmlhttp_on_description,
				"optionscode" => "text",
				"value" => '0'
			)
		),
		"templates" => array
		(
			array
			(
				"title" => "rand_quote_sidebox",
				"template" => <<<EOF
				<tr>
					<td class="tcat">
						{\$thread_title_link}
					</td>
				</tr>
				<tr>
					<td class="trow1">
						{\$rand_quote_avatar}&nbsp;{\$rand_quote_author}
					</td>
				</tr>
				<tr>
					<td class="trow2">
						{\$rand_quote_text}
					</td>
				</tr>{\$read_more}
EOF
				,
				"sid" => -1
			)
		)
	);
}

/*
 * asb_rand_quote_build_template()
 *
 * @param - (array) $settings
					passed from core, side box settings
 * @param - (string) $template_var
					the encoded unique name of the side box requested for
 * @param - (int) $width
					the width of the column the calling side box is positioned in
 */
function asb_rand_quote_build_template($args)
{
	foreach(array('settings', 'template_var', 'width') as $key)
	{
		$$key = $args[$key];
	}

	global $$template_var, $lang;

	if(!$lang->asb_addon)
	{
		$lang->load('asb_addon');
	}

	$this_quote = asb_rand_quote_get_quote($settings, $width);
	if($this_quote)
	{
		$$template_var = $this_quote;
		return true;
	}
	else
	{
		// show the table only if there are posts
		$$template_var = <<<EOF
		<tr>
					<td class="trow1">
						{$lang->asb_random_quotes_no_posts}
					</td>
				</tr>
EOF;
		return false;
	}
}

/*
 * asb_rand_quote_xmlhttp()
 *
 * @param - (int) $dateline
					UNIX time stamp
 * @param - (array) $settings
					array of side box settings
 * @param - (int) $width
					width of column side box lives in
 */
function asb_rand_quote_xmlhttp($args)
{
	foreach(array('settings', 'dateline', 'width') as $key)
	{
		$$key = $args[$key];
	}

	// get a quote and return it
	$this_quote = asb_rand_quote_get_quote($settings, $width);
	if($this_quote)
	{
		return $this_quote;
	}
	return 'nochange';
}

/*
 * asb_rand_quote_get_quote()
 *
 * @param - (array) $settings
					passed from asb_xmlhttp.php, the requesting side box's settings array
 * @param - (int) $width
					the width of the column
 */
function asb_rand_quote_get_quote($settings, $width)
{
	global $db, $mybb, $templates, $lang, $theme;

	if(!$lang->asb_addon)
	{
		$lang->load('asb_addon');
	}

	// build the where statement
	$where = array();
	if((int) $settings['thread_id']['value'] > 0)
	{
		$where['view_only'] = "tid IN ({$settings['thread_id']['value']})";
	}
	else if((int) $settings['forum_id']['value'] > 0)
	{
		$where['view_only'] = "fid IN ({$settings['forum_id']['value']})";
	}
	// get forums user cannot view
	$unviewable = get_unviewable_forums(true);
	if($unviewable)
	{
		$where['unview'] = "fid NOT IN ($unviewable)";
	}
	$where = implode(' AND ', $where);
	if(strlen($where) == 0)
	{
		$where = '1=1';
	}

	// get a random post
	$query_string = 
"SELECT
	p.pid, p.message, p.fid, p.tid, p.subject, p.uid,
	u.username, u.usergroup, u.displaygroup, u.avatar
FROM " .
	TABLE_PREFIX . "posts p 
LEFT JOIN " .
	TABLE_PREFIX . "users u ON (u.uid=p.uid)
WHERE 
	{$where}
ORDER BY
	RAND()
LIMIT 1;";
	$post_query = $db->query($query_string);

	// if there was 1 . . .
	if($db->num_rows($post_query))
	{
		$rand_post = $db->fetch_array($post_query);

		// build a post parser
		require_once MYBB_ROOT."inc/class_parser.php";
		$parser = new postParser;

		// we just need the text and smilies (we'll parse them after we check length)
		$pattern = "|[[\/\!]*?[^\[\]]*?]|si";
		$new_message = asb_strip_url(preg_replace($pattern, '$1', $rand_post['message']));
		
		// get some dimensions that make sense in relation to column width
		$asb_width = (int) $width;
		$asb_inner_size = $asb_width * .83;
		$avatar_size = (int) ($asb_inner_size / 5);
		$font_size = $asb_width / 4.5;

		$font_size = max(10, min(16, $font_size));
		$username_font_size = (int) ($font_size * .9);
		$title_font_size = (int) ($font_size * .65);
		$message_font_size = (int) $font_size;

		if(strlen($new_message) < $settings['min_length']['value'])
		{
			if($settings['default_text']['value'])
			{
				$new_message = $settings['default_text']['value'];
			}
			else
			{
				// nothing to show
				return false;
			}
		}
		
		if($settings['max_length']['value'] && strlen($new_message) > $settings['max_length']['value'])
		{
			$new_message = substr($new_message, 0, $settings['max_length']['value']) . ' . . .';
		}

		// set up the user name link so that it displays correctly for the display group of the user
		$plain_text_username = $username = htmlspecialchars_uni($rand_post['username']);
		$usergroup = $rand_post['usergroup'];
		$displaygroup = $rand_post['displaygroup'];
		$username = format_name($username, $usergroup, $displaygroup);
		$author_link = get_profile_link($rand_post['uid']);
		$post_link = get_post_link($rand_post['pid'], $rand_post['tid']) . '#pid' . $rand_post['pid'];
		$thread_link = get_thread_link($rand_post['tid']);

		// allow smilies, but kill 
		$parser_options = array("allow_smilies" => 1);
		$new_message = str_replace(array('<br />', '/me'), array('', " * {$plain_text_username}"), $parser->parse_message($new_message . ' ', $parser_options));

		$rand_quote_text = <<<EOF
<span style="font-size: {$message_font_size}px;">{$new_message}</span>
EOF;

		// If the user has an avatar then display it . . .
		if($rand_post['avatar'] != "")
		{
			$avatar_filename = $rand_post['avatar'];
		}
		else
		{
			// . . . otherwise force the default avatar.
			$avatar_filename = "{$theme['imgdir']}/default_avatar.gif";
		}

		$rand_quote_avatar = <<<EOF
<img style="padding: 4px; width: 15%; vertical-align: middle;" src="{$avatar_filename}" alt="{$plain_text_username}'s avatar" title="{$plain_text_username}'s avatar"/>
EOF;

		$rand_quote_author = <<<EOF
<a  style="vertical-align: middle;" href="{$author_link}" title="{$plain_text_username}"><span style="font-size: {$username_font_size}px;">{$username}</span></a>
EOF;

		$read_more = <<<EOF

				<tr>
					<td class="tfoot">
						<div style="text-align: center;"><a href="{$post_link}" title="{$lang->asb_random_quotes_read_more_title}"><strong>{$lang->asb_random_quotes_read_more}</strong></a></div>
					</td>
				</tr>
EOF;

		if(my_strlen($rand_post['subject']) > 40)
		{
			$rand_post['subject'] = my_substr($rand_post['subject'], 0, 40) . " . . .";
		}

		if(substr(strtolower($rand_post['subject']), 0, 3) == 're:')
		{
			$rand_post['subject'] = substr($rand_post['subject'], 3);
		}

		$rand_post['subject'] = htmlspecialchars_uni($parser->parse_badwords($rand_post['subject']));

		$thread_title_link = <<<EOF
<strong><a href="{$thread_link}" title="{$lang->asb_random_quotes_read_more_threadlink_title}"><span style="font-size: {$title_font_size}px;">{$rand_post['subject']}</span></a></strong>
EOF;

		// eval() the template
		eval("\$this_quote = \"" . $templates->get("rand_quote_sidebox") . "\";");
		return $this_quote;
	}
	else
	{
		return false;
	}
}

?>

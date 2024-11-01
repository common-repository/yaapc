<?php
/*
Plugin Name: Yet another ajax paged comments (YAAPC)
Plugin URI: http://www.deanlee.cn/wordpress/yaapc
Description: This plugin provides AJAX enabled comments system with paging,comment posting and fields validation to your WordPress blog.
Version: 1.1.4
Author: Dean Lee
Author URI: http://www.deanlee.cn/
*/

class YAAPC
{
	var $num_pages = 0;
	var $current_page = 0;
	var $comment_count = 0;
	
	function YAAPC()
	{
		$this->plugin_path = get_option('siteurl') .'/wp-content/plugins/' . basename(dirname(__FILE__));
		add_action('wp_head', array(&$this, 'generate_header'));
		add_action('template_redirect', array(&$this, 'template_redirect'), 15);
		add_action('admin_menu', array(&$this, 'add_admin_pages'));
		add_filter('comment_post_redirect', array(&$this, 'comment_post_redirect'), 1, 2);
		add_action('wp_print_scripts', array(&$this, 'add_js'));
		$options =  get_option('yaapc_options');
		if (!is_array($options))
		{
			$options['prev_text'] = __('&laquo; Previous','wp-pagenavi');
			$options['next_text'] = __('Next &raquo;','wp-pagenavi');
			$options['items_per_page'] = 10;
			$options['ordering'] = 'DESC';
			$options['page_range'] = 8;
			$options['noindex'] = true;
		}
		if (!isset($options['noindex']))
			$options['noindex'] = true;
		foreach ($options as $option_name => $option_value)
	        $this-> {$option_name} = $option_value;
	}

	function template_redirect()
	{
		if (!is_single() && !is_page())
			return;
		$template = is_single() ? get_single_template() : get_page_template();
		if (empty($template) && file_exists(TEMPLATEPATH.'/index.php')) {
			$template = TEMPLATEPATH.'/index.php';
		}
		if (!empty($template)) {
			if (isset($_GET['yaapc'])) {

				eval('?><?php yaapc_comments_template() ?>');
				exit;
			}
			else {

				$contents = file_get_contents($template);
				if (!strpos($contents, 'yaapc_comments_template()')) {
					$contents = str_replace('comments_template()', 'yaapc_comments_template()', $contents);
					eval('?>'.trim($contents));
					exit;
				}
			}
		}
	}

	function comment_post_redirect($location, $comment)
	{
		global $post;
		if (isset($_GET['yaapc'])){
			$post = get_post($comment->comment_post_ID);
			eval('?><?php yaapc_comments_template() ?>');
			exit;
		}
		return $location;
	}

	function comments_template($file = '/comments.php')
	{
		global $wp_query, $withcomments, $post, $wpdb, $id, $comment, $user_login, $user_ID, $user_identity;
		define('COMMENTS_TEMPLATE', true);
		$include = apply_filters('comments_template', TEMPLATEPATH . $file);
		$req = get_option('require_name_email');
		$commenter = wp_get_current_commenter();
		extract($commenter, EXTR_SKIP);

		$sql_condition = '';
		if ($user_ID) {
			$sql_condition  = "AND (comment_approved = '1' OR ( user_id = '$user_ID' AND comment_approved = '0' ))";
		} elseif (empty($comment_author)) {
			$sql_condition  = "AND comment_approved = '1'";
		} else {
			$sql_condition  = "AND (comment_approved = '1' OR (comment_author = '". addslashes($comment_author). "' AND comment_author_email = '". addslashes($comment_author_email) ."' AND comment_approved = '0'))";
		}

		$this->comment_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_post_ID = '$post->ID' $sql_condition ");
		$this->num_pages = (int)ceil($this->comment_count / $this->items_per_page);
		// set page number
		$page = (int)@$_GET['cp'];
		if ($page > 0) {
			$this->current_page = $page;
		} 
		if ($this->current_page > $this->num_pages || $this->current_page < 1)
			$this->current_page = $this->num_pages;
		
		$limit_clause = ' LIMIT '. $this->sql_limit();
		$comments = $wpdb->get_results("SELECT * FROM $wpdb->comments WHERE comment_post_ID = '$post->ID' $sql_condition  ORDER BY comment_date ".$this->ordering.$limit_clause);

		$comment_number = ($this->current_page - 1) * $this->items_per_page;
		$comment_mod = $this->comment_count % $this->items_per_page;
		if ($this->ordering == 'DESC') {
			if (($comment_mod != 0)) {
				$comment_number += $comment_mod;
			} 
			else {
				$comment_number += count($comments);
			}
			$comment_delta = -1;
		} 
		else { 
			$comment_number += 1;
			$comment_delta = 1;
		}

		foreach($comments as $comment) {
			$comment->yaapc_number = $comment_number;
			$comment_number += $comment_delta;
		}

		$file_contents = file_get_contents($include);
		if (!strpos($file_contents, 'yaapc_pages')) {
			$result = preg_match('/<(\S*)\s*class="commentlist"\\>/', $file_contents, $matchs);
			if ($result) {
				$pagenav = '<?php yaapc_pages(); ?>';
				$file_contents = preg_replace("|<$matchs[1]\s*class=\"commentlist\">(.*?)</$matchs[1]>|ism", "$pagenav$0$pagenav", $file_contents);
				eval('?'.'>'.trim($file_contents));
				return;
			}
		}
		else {
			require($include);
		}
	}


	function sql_limit()
	{
		$remainder = $this->comment_count % $this->items_per_page;
		$page = $this->ordering == 'DESC' ? ($this->num_pages + 1 - $this->current_page) : $this->current_page;
		$offset = ($page - 1) * $this->items_per_page;
		return sprintf("%d,%d", $offset, $this->items_per_page);
	}

	function get_page_url($index)
	{
		global $post, $multipage, $page;
		$id = $post->ID;
		$qparam = $post->post_type == 'page' ? 'page_id' : 'p';
		$multipage_classic = '';
		if ($multipage && $page) {
			$multipage_classic = "&amp;page=$page";
		}
		return get_settings('siteurl').'/'.get_settings('blogfilename')."?$qparam=$id$multipage_classic&amp;cp=$index#comments";
	}

	function generate_page_link($i)
	{
		if($i == $this->current_page) {
			return '<span class="current">'.$i.'</span>';
		} else {
			return '<a href="'.clean_url($this->get_page_url($i)).'" title="'.$i.'">'.$i.'</a>';
		}

	}

	function comments_pages()
	{
		$max_page = $this->num_pages;
		$pages_to_show_minus_1 = $this->page_range -1;
		$half_page_start = floor($pages_to_show_minus_1/2);
		$half_page_end = ceil($pages_to_show_minus_1/2);
		$start_page = $this->current_page - $half_page_start;
		if($start_page <= 0) {
			$start_page = 1;
		}
		$end_page = $this->current_page + $half_page_end;
		if(($end_page - $start_page) != $pages_to_show_minus_1) {
			$end_page = $start_page + $pages_to_show_minus_1;
		}
		if($end_page > $max_page) {
			$start_page = $max_page - $pages_to_show_minus_1;
			$end_page = $max_page;
		}
		if($start_page <= 0) {
			$start_page = 1;
		}
		if($max_page > 1) 
		{
			$navitems = array();
			$text = '';
			$text.= $before.'<div class="yaapc-pagenav">'."\n";
			
			
			$max = min(3, $start_page);
			for ($i = 1; $i < $max; $i++)
			{
				array_push($navitems, $this->generate_page_link($i));
			}
			if ($start_page > 3)
			{
				array_push($navitems, '<span class="extend">...</span>');
			}

			for($i = $start_page; $i  <= $end_page; $i++) {		
				array_push($navitems, $this->generate_page_link($i));
			}
			
			if ($end_page < $max_page - 2) {
				array_push($navitems, '<span class="extend">...</span>');
			}
			$min = max($end_page+1, $max_page-1);
			for ($i = $min; $i <= $max_page; $i++)
			{
				array_push($navitems, $this->generate_page_link($i));
			}
			$delta = 1;
			$prev_value = 1;
			$next_value = $max_page;
			if ($this->ordering == "DESC")
			{
				$delta = -1;
				$prev_value = $max_page;
				$next_value = 1;
			}
			if ($this->current_page != $prev_value)
			{
				$first_page_text = $this->prev_text;
				$text .= '<a class="nextprev" href="'.clean_url($this->get_page_url($this->current_page - $delta)).'" title="'.$first_page_text.'">'.$first_page_text.'</a>';
			}
			else
			{
				$text .= '<span class="nextprev">'.$this->prev_text.'</span>';
			}

			$text .= implode('', $this->ordering == "DESC" ? array_reverse($navitems) : $navitems);
			if ($this->current_page != $next_value)
			{
				$last_page_text = $this->next_text;
				$text.= '<a class="nextprev" href="'.clean_url($this->get_page_url($this->current_page +$delta)).'" title="'.$last_page_text.'">'.$last_page_text.'</a>';
			}
			else
			{
				$text .= '<span class="nextprev">'.$this->next_text.'</span>';
			}

			$text.= '</div>'.$after."\n";
			echo $text;
		}
	}

	function generate_header()
	{
		if (is_single() || is_page()){

			if ($this->noindex && isset($_GET['cp'])){
				echo '<meta name="robots" content="noindex,follow" />' . "\n";
			}
			echo '<link rel="stylesheet" href="'.$this->plugin_path .'/style.css" type="text/css" media="screen" />'."\n";
			if (!function_exists('wp_enqueue_script')){
				echo '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js" type="text/javascript"></script>'."\n";
				echo '<script src="'. $this->plugin_path . '/yaapc.js" type="text/javascript"></script>'."\n";
				echo '<script src="'. $this->plugin_path . '/jquery.form.pack.js" type="text/javascript"></script>'."\n";
			}
		}
	}

	
	function add_js() {
		if (is_single() || is_page()){
			wp_deregister_script(array('jquery')); 
			wp_enqueue_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js', false, '1.2.6'); 
			wp_enqueue_script('jquery-form');
			wp_enqueue_script('yaapc', $this->plugin_path . '/yaapc.js', array("jquery"), 1.0);
		} 
	}

	function add_admin_pages()
	{
		add_options_page('Paged Comments (YAAPC)', 'Paged Comments (YAAPC)', 8, 'yaapc', array(&$this, 'option_page'));
	}

	function option_page()
	{
		if ( isset($_POST['submitted']) ) {
		$options = array();
		$options['prev_text'] = $_POST['prev_text'];
		$options['next_text'] = $_POST['next_text'];
		$options['items_per_page'] = intval($_POST['items_per_page']);
		$options['ordering'] = $_POST['ordering'];
		$options['page_range'] = intval($_POST['page_range']);
		$options['noindex'] = intval($_POST['noindex']);
		// Remember to put all the other options into the array or they'll get lost!
		update_option('yaapc_options', $options);
		foreach ($options as $option_name => $option_value)
	        $this-> {$option_name} = $option_value;
		echo '<div id="message" class="updated fade"><p><strong>Plugin settings saved.</strong></p></div>';
		}
		?>
		<div class='wrap'>
		<h2>Yet Another Ajax Paged Comments</h2>
		<p><cite><a href="http://www.deanlee.cn/wordpress/yaapc" target="_blank">Yet Another Ajax Paged Comments</a></cite> provides AJAX enabled comments with paging,comment posting and fields validation to your WordPress blog.</p>
		
		<form name="yaapc" action="<?php echo $action_url; ?>" method="post">
			<input type="hidden" name="submitted" value="1" />
				
			<fieldset class="options">
				<ul>
					<li>
					<label for="items_per_page">
						Comments Per Page:
						<input type="text" id="items_per_page" name="items_per_page"
							size="2" maxlength="3"
							value="<?php echo $this->items_per_page; ?>" />
					</label>
					</li>
					<li>
					<label for="page_range">
						Number Of Pages To Show:
						<input type="text" id="page_range" name="page_range"
							size="2" maxlength="3"
							value="<?php echo $this->page_range; ?>" />
					</label>
					</li>
					<li>
					<label for="ordering">
						Comments Ordering:
						<select name="ordering">
                    <option value="DESC"<?php if ($this->ordering == 'DESC') { ?> selected="selected"<?php } ?> >DESC</option>
                    <option value="ASC"<?php if ($this->ordering == 'ASC') { ?> selected="selected"<?php } ?> >ASC</option>
                    </select>
					</label>
					</li>
					<li>
					<label for="prev_text">
						Previous link text:
						<input type="text" id="prev_text" name="prev_text" value="<?php echo htmlentities($this->prev_text); ?>" />
					</label>default: &amp;laquo; Previous;
					</li>
					<li>
					<label for="next_text">
						Next link text:
						<input type="text" id="next_text" name="next_text" value="<?php echo htmlentities($this->next_text); ?>" />
					</label>default: Next &amp;raquo;
					</li>

					<li>
					<label for="noindex">
						<input type="checkbox" id="noindex" name="noindex" value="1" <?php if ($this->noindex) echo("checked");?> />Generate "noindex follow" in meta to direct the robot to not index the paged comments.
					</label>
					</li>
					<!--<li>
					<label for="help_promote">
						<input type="checkbox" id="help_promote" name="help_promote" value="1" <?php if ($this->help_promote) echo("checked");?> />
						Help promote Yet Another Ajax paged comments Plugin
						</label>
					</li>-->
					
				</ul>
			</fieldset>
			<p class="submit"><input type="submit" name="Submit" value="Save changes &raquo;" /></p>
		</form>
	</div>
	<?php
	}

}

$YAAPC = new YAAPC();
function yaapc_comments_template()
{
	global $YAAPC;
	$YAAPC->comments_template();
}

function yaapc_pages()
{
	global $YAAPC;
	$YAAPC->comments_pages();
}
?>
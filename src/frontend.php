<?php

class wpo_enhancements_frontend
{
	var $options                      = [];
	var $buffer_started               = false;
	var $current_host                 = '';
	var $user_events                  = ['scroll', 'click', 'mousemove', 'touchmove'];
	var $js_scripts_to_add_to_loadext = [];

	public function __construct()
	{
		if (is_admin()) {
			return;
		}

		$this->options = get_option('wpo_enhancements_options');

		$this->current_host = parse_url(home_url(), PHP_URL_HOST);

		if (!$this->fieldOn('enabled')) {
			return;
		}

		if ($this->fieldOn('preload')) {
			add_action('wp_head', array($this, 'preload'));
		}

		if ($this->fieldOn('footer_scripts')) {
			add_action('wp_footer', array($this, 'footer_scripts'));
		}

		if ($this->fieldOn('remove_all_scripts')) {
			add_action('wp_enqueue_scripts', array($this, 'remove_all_scripts'), PHP_INT_MAX - 1);
		}

		if (!$this->check_logged_in()) {
			add_filter('rocket_buffer', array(&$this, 'process_rocket_buffer'), 1000);
			add_filter('rocket_buffer', array(&$this, 'process_rocket_buffer_pre_wprocket'), 18);
		}

		add_action('init', array(&$this, 'maybe_force_wprocket_disable'));
		add_action('wp_enqueue_scripts', array(&$this, 'dequeue_scripts'), 1000);
	}


	private function fieldOn($name)
	{
		return (strtolower($this->field($name)) == 'on');
	}

	private function field($name)
	{
		return $this->option('wpo_enhancements_field_' . $name);
	}

	private function option($name)
	{
		if (!isset($this->options[$name])) {
			return '';
		}

		return $this->options[$name];
	}

	public function maybe_force_wprocket_disable()
	{
		//  Only when WP Rocket is active.
		if (!function_exists('get_rocket_option')) {
			return false;
		}

		if ($this->is_rejected_user_agent()) {
			// Finally: prevent caching
			add_action('template_redirect', array(&$this, 'do_not_cache'), 1);
		}

		return true;
	}

	public function do_not_cache()
	{
		if (!defined('DONOTCACHEPAGE')) {
			define('DONOTCACHEPAGE', true);
		}

		if (!defined('DONOTROCKETOPTIMIZE')) {
			define('DONOTROCKETOPTIMIZE', true);
		}

		return true;
	}

	private function is_wprocket_cache_enabled()
	{
		if (!function_exists('get_rocket_option')) {
			return false;
		}

		if (defined('DONOTCACHEPAGE') && !DONOTCACHEPAGE) {
			return false;
		}

		if (defined('DONOTROCKETOPTIMIZE') && !DONOTROCKETOPTIMIZE) {
			return false;
		}

		$current = home_url($_SERVER['REQUEST_URI']);

		if ($this->is_rejected_uri($current)) {
			return false;
		}

		if ($this->is_rejected_user_agent()) {
			return false;
		}

		return true;
	}

	private function is_rejected_uri($current)
	{
		if (!function_exists('get_rocket_option')) {
			return false;
		}

		$rejected_uris = get_rocket_option('cache_reject_uri');
		if (empty($rejected_uris)) {
			return false;
		}
		foreach ($rejected_uris as $uri) {
			if ($current === home_url($uri)) {
				return true;
			}
		}

		return false;
	}

	function is_rejected_user_agent()
	{
		if (!function_exists('get_rocket_option')) {
			return false;
		}

		if (empty($_SERVER['HTTP_USER_AGENT'])) {
			return false;
		}

		$user_agent   = trim($_SERVER['HTTP_USER_AGENT']);
		$rejected_uas = get_rocket_option('cache_reject_ua');

		foreach ($rejected_uas as $rejected_ua) {
			if (preg_match('#' . $rejected_ua . '#', $user_agent)) {
				return true;
			}
		}

		return false;
	}

	function apply_wpo_enhancements()
	{
		if (isset($_GET['nocache'])) {
			return false;
		}
		if (!$this->is_wprocket_cache_enabled()) {
			return false;
		}

		return true;
	}

	function remove_all_scripts()
	{
		if (!$this->apply_wpo_enhancements()) {
			return;
		}

		$excluded = [];
		foreach (wp_scripts()->registered as $item) {
			if (in_array($item->handle, $excluded)) {
				continue;
			}
			wp_deregister_script($item->handle);
		}
	}

	function inline_js($file)
	{
		$data = file_get_contents($file);
		if (class_exists('MatthiasMullie\Minify\JS')) {
			$min  = new MatthiasMullie\Minify\JS($data);
			$data = $min->minify();
		}

		return $data . ';';
	}

	/**
	 * @return bool
	 */
	private function isHomePage()
	{
		return is_front_page();
	}

	/**
	 * @return bool
	 */
	private function is404()
	{
		return is_404();
	}

	/**
	 * @return bool
	 */
	private function isBlogPost()
	{
		return is_singular('post');
	}

	/**
	 * Check if user is logged in
	 *
	 * @return boolean
	 */
	function check_logged_in()
	{
		foreach (array_keys($_COOKIE) as $cookie_name) {
			if (strpos($cookie_name, 'wordpress_logged_in') !== false) {
				return true;
			}
		}

		return false;
	}

	function buffer_go()
	{
		$this->buffer_started = true;
		ob_start(array(&$this, 'buffer_callback'));
	}

	function buffer_stop()
	{
		if ($this->buffer_started && ob_get_level() > 0) {
			ob_end_flush();
		}
	}

	function buffer_callback($buffer)
	{
		if (!$this->apply_wpo_enhancements()) {
			return $buffer;
		}

		$buffer = $this->clean_noscripts($buffer);

		$buffer = $this->add_load_external($buffer);

		$buffer = $this->load_js_with_timeout($buffer);

		$buffer = $this->move_rel_stylesheets($buffer);

		$buffer = $this->delay_font_face_loading($buffer);

		$buffer = $this->replace_remove_cpcss_script($buffer);

		$buffer = $this->replace_critical_css($buffer);

		$buffer = $this->add_custom_inline_css($buffer);

		return $buffer;
	}

	function process_rocket_buffer($buffer)
	{
		return $this->buffer_callback($buffer);
	}

	function process_rocket_buffer_pre_wprocket($buffer)
	{
		if (!$this->apply_wpo_enhancements()) {
			return $buffer;
		}

		return $this->html_replacements($buffer);
	}

	private function getReplacements(): array
	{
		$template = [
			'src' => '',
			'dst' => '',
		];

		$config = $this->getJsonFieldAsArray('html_replacements_config');

		$items = [];
		foreach ($config as $item) {
			$items[] = array_merge(
				$template,
				$item
			);
		}

		return $items;
	}

	function html_replacements($content)
	{
		if (!$this->fieldOn('html_replacements')) {
			return $content;
		}

		$items = $this->getReplacements();

		foreach ($items as $item) {
			$content = str_replace($item['src'], $item['dst'], $content);
		}

		return $content;
	}

	function delay_font_face_loading($content)
	{
		if (!$this->fieldOn('delay_font_face_loading')) {
			return $content;
		}

		$re    = '/@font-face{[^}]+}/m';
		$subst = '';

		preg_match_all($re, $content, $matches, PREG_SET_ORDER, 0);
		$content = preg_replace($re, $subst, $content);

		$fonts = '';
		foreach ($matches as $item) {
			$fonts .= $item[0];
		}
		$fonts = str_replace("\n", '', $fonts);
		$fonts = str_replace('"', '\"', $fonts);

		$script = <<<DATA
<script>
var doLoadFontsExecuted=false;
function doLoadFonts() {
    if ( doLoadFontsExecuted ) {return;}
    doLoadFontsExecuted=true;

    var newStyle = document.createElement('style');
    newStyle.appendChild(document.createTextNode("$fonts"));
    document.head.appendChild(newStyle);
}
document.addEventListener("scroll", function() { doLoadFonts(); });
document.addEventListener("click", function() { doLoadFonts(); });
document.addEventListener("mousemove", function() { doLoadFonts(); });
document.addEventListener("touchmove", function() { doLoadFonts(); });


</script>
DATA;

		$content = str_replace('</body>', $script . '</body>', $content);

		return $content;
	}

	function replace_critical_css($content)
	{
		if (!$this->fieldOn('replace_critical_css')) {
			return $content;
		}

		$file = '';
		if (is_front_page()) {
			$file = 'frontpage.css';
		}
		if (!empty($file)) {
			$fullfile = __DIR__ . '/css/' . $file;
			if (file_exists($fullfile)) {
				$css = file_get_contents($fullfile);

				$re      = '/<style id="rocket-critical-css">.*?<\/style>/m';
				$subst   = '<style id="rocket-critical-css" class="wpo-enhancements">' . wp_strip_all_tags(
						$css
					) . '</style>';
				$content = preg_replace($re, $subst, $content);
			}
		}

		return $content;
	}

	function replace_remove_cpcss_script($content)
	{
		if (!$this->fieldOn('replace_remove_cpcss_script')) {
			return $content;
		}

		$re      = '/<script>"use strict";var wprRemoveCPCSS.*?<\/script>/m';
		$content = preg_replace($re, '', $content);

		$re      = '/<script>const wprRemoveCPCSS.*?<\/script>/m';
		$content = preg_replace($re, '', $content);

		$events = $this->buildUserEventsBlock('function() { doRemoveCPCSS(); }');
		$script = '<script>
                const wprRemoveCPCSS = () => {
                    $elem = document.getElementById( "rocket-critical-css" );
                    if ( $elem ) {
                        setTimeout(function(){
                            $elem.remove();    
                            console.log("Inline CSS removed");
                        }, 2000);                        
                    }
                };
                var wprRemoveCPCSSDONE=false;
                function doRemoveCPCSS() {
                    if ( wprRemoveCPCSSDONE ) {return;}
                    wprRemoveCPCSSDONE=true;
                    wprRemoveCPCSS();
                }
                ' . $events . '
            </script>';

		$content = str_replace('</body>', $script . '</body>', $content);

		return $content;
	}

	function clean_noscripts($content)
	{
		if (!$this->fieldOn('clean_noscripts')) {
			return $content;
		}

		$re      = '/<noscript>.*?<\/noscript>/m';
		$content = preg_replace($re, '', $content);

		return $content;
	}

	function add_load_external($content)
	{
		if (!$this->fieldOn('add_load_external')) {
			return $content;
		}

		$html = sprintf(
			'<scr' . 'ipt type="text/javascript" async>
            %1$s
            </scr' . 'ipt>',
			$this->inline_js(__DIR__ . DIRECTORY_SEPARATOR . 'lib/loadexternal.js')
		);

		$content = str_replace('</body>', $html . '</body>', $content);

		return $content;
	}

	function load_js_with_timeout($content)
	{
		if (!$this->fieldOn('add_load_external')) {
			return $content;
		}
		if (!$this->fieldOn('load_js_with_timeout')) {
			return $content;
		}

		$re = '/<script[^>]+?src\s*=\s*(["\'])?(.*?\.js.*?)\1[^>]*>\s*<\/script>/m';
		preg_match_all($re, $content, $matches, PREG_SET_ORDER, 0);

		$links = [];
		foreach ($matches as $match) {
			$full = $match[0];
			if ($this->is_wprocket_excluded_inline_js($full)) {
				continue;
			}
			$content = str_ireplace($full, '', $content);
			$links[] = $match[2];
		}

		$links = array_merge($links, $this->js_scripts_to_add_to_loadext);

		if ($this->fieldOn('sort_external_scripts_after_internal_ones')) {
			$links = $this->sort_external_scripts_after_internal_ones($links);
		}
		$links = $this->quote_all($links);

		$events = $this->buildUserEventsBlock('function() { loadJsWithTimeout(); }');
		$html   = sprintf(
			'<scr' . 'ipt type="text/javascript" async data-test="1">
var didLoadJsWithTimeout = false;
function loadJsWithTimeout() {
    if ( didLoadJsWithTimeout ) { return; }
    didLoadJsWithTimeout=true;
    new loadExt([%1$s]);
}            
%2$s
</scr' . 'ipt>',
			implode(',', $links),
			$events
		);

		$content = str_replace('</body>', $html . '</body>', $content);

		return $content;
	}

	function is_wprocket_excluded_inline_js($script)
	{
		$exclusions = get_rocket_option('exclude_inline_js');
		foreach ($exclusions as $excluded) {
			if (stripos($script, $excluded) !== false) {
				return true;
			}
		}

		return false;
	}

	private function sort_external_scripts_after_internal_ones(array $links)
	{
		$external = [];
		$internal = [];
		foreach ($links as $link) {
			if ($this->is_internal_link($link)) {
				$internal[] = $link;
			} else {
				$external[] = $link;
			}
		}
		$links = array_merge($internal, $external);

		return $links;
	}

	private function is_internal_link($link)
	{
		$link_domain = parse_url($link, PHP_URL_HOST);
		if (empty($link_domain)) {
			return true;
		}

		return ($this->current_host === $link_domain);
	}

	function move_rel_stylesheets($content)
	{
		if (!$this->fieldOn('add_load_external')) {
			return $content;
		}
		if (!$this->fieldOn('move_rel_stylesheets')) {
			return $content;
		}

		$re = '/(?<!<noscript>)\s*<link\s*rel\s*=["\']?(?:stylesheet|preload)["\']?.*?href\s*=\s*(["\'])?(.*?)(\.css|\1).*?\/?>/m';
		preg_match_all($re, $content, $matches, PREG_SET_ORDER, 0);

		$css = [];
		foreach ($matches as $match) {
			$full = $match[0];
			if (stripos($full, ' as="font"') !== false) {
				continue;
			}
			if (stripos($full, 'ie9') !== false) {
				continue;
			}
			if (stripos($full, 'ie.css') !== false) {
				continue;
			}
			$content = str_ireplace($full, '', $content);
			$link    = $match[2];
			if ($match[3] !== '"' && $match[3] !== "'") {
				$link .= $match[3];
			}
			$css[] = '"' . $link . '"';
		}

		if (empty($css)) {
			return $content;
		}


		$events  = $this->buildUserEventsBlock('function() { moveRelStyleSheets(); }');
		$timeout = '';
		if ($this->fieldOn('load_rel_stylesheets_with_timeout')) {
			$timeout = $this->buildSetTimeout(
				'function() { moveRelStyleSheets(); }',
				intval($this->field('load_rel_stylesheets_with_timeout_config'))
			);
		}

		$html = sprintf(
			'<scr' . 'ipt type="text/javascript" async>
var didMoveRelStyleSheets = false;
function moveRelStyleSheets() {
    if ( didMoveRelStyleSheets ) { return; }
    didMoveRelStyleSheets=true;
    new loadExt([%1$s]);
}            
%2$s
%3$s
</scr' . 'ipt>',
			implode(', ', $css),
			$events,
			$timeout
		);

		$content = str_replace('</body>', $html . '</body>', $content);

		return $content;
	}

	private function quote_all(array $links)
	{
		$items = [];
		foreach ($links as $link) {
			$items[] = '"' . $link . '"';
		}

		return $items;
	}


	private function getPreload(): array
	{
		$template = [
			'type'         => '',
			'src'          => '',
			'html_preload' => 1,
			'http_push'    => 0,
		];

		$config = $this->getJsonFieldAsArray('preload_config');

		$items = [];
		foreach ($config as $item) {
			$items[] = array_merge(
				$template,
				$item
			);
		}

		return $items;
	}

	public function preload()
	{
		if (!$this->apply_wpo_enhancements()) {
			return;
		}

		$items = $this->getPreload();

		foreach ($items as $item) {
			if ($item['html_preload']) {
				echo sprintf('<link rel="preload" crossorigin href="%1$s" as="%2$s">', $item['src'], $item['type']);
			}
			if ($item['http_push']) {
				header(sprintf('Link: <%1$s>; rel=preload; as=%2$s', $item['src'], $item['type']), false);
			}
		}
	}

	/**
	 * @param $name
	 *
	 * @return array|mixed
	 */
	private function getJsonFieldAsArray($name)
	{
		$config = $this->field($name);
		$config = json_decode($config, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			$config = [];
		}

		return $config;
	}

	function add_custom_inline_css($content)
	{
		if (!$this->fieldOn('custom_inline_css')) {
			return $content;
		}

		$css = $this->field('custom_inline_css_content');
		if (empty($css)) {
			return $content;
		}

		$html = "\n" . '<style id="wpo-enhacements-missing-css">' . wp_strip_all_tags($css, true) . '</style>' . "\n";

		$content = str_replace('<head>', '<head>' . $html, $content);

		return $content;
	}

	private function buildUserEventsBlock($function)
	{
		$html = '';
		foreach ($this->user_events as $event) {
			$html .= sprintf('document.addEventListener("%1$s", %2$s);', $event, $function) . "\n";
		}

		return $html;
	}

	private function buildSetTimeout($function, int $timeout)
	{
		return sprintf('window.setTimeout(%1$s, %2$d);', $function, $timeout) . "\n";
	}

	private function getFooterScripts(): array
	{
		$template = [
			'name' => '',
		];

		$config = $this->getJsonFieldAsArray('footer_scripts_config');

		$items = [];
		foreach ($config as $item) {
			$items[] = array_merge(
				$template,
				$item
			);
		}

		return $items;
	}

	function footer_scripts()
	{
		if (!$this->apply_wpo_enhancements()) {
			return;
		}

		$items = $this->getFooterScripts();
		if (empty($items)) {
			return;
		}
		echo '<script type="text/javascript" async>';
		foreach ($items as $item) {
			echo $this->inline_js(__DIR__ . DIRECTORY_SEPARATOR . 'js/' . $item['name']);
		}
		echo '</script>';
	}

	public function dequeue_scripts()
	{
		if (!$this->apply_wpo_enhancements()) {
			return;
		}
		$scripts = wp_scripts();
		if ($this->fieldOn('load_recaptcha_with_loadext')) {
			if (defined('__GRIWPC_RECAPTCHA_SHOW__')) {
				if (isset($scripts->registered['recaptcha-call'])) {
					$script                               = $scripts->registered['recaptcha-call'];
					$this->js_scripts_to_add_to_loadext[] = $script->src;
					$script->src                          = '';
				}
			}
		}
	}

}

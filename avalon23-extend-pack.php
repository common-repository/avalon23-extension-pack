<?php
/*
  Plugin Name: Extension pack for Avalon23
  Plugin URI: https://avalon23.dev/document/avalon23-extension-pack/
  Description: Extensions for Avalon23 Filter . For example: auto price, preloader etc.
  Requires at least: WP 4.9
  Tested up to: WP 5.4
  Author: Paradigma Tools
  Version: 1.0.1
  Requires PHP: 5.4
  Tags: avalon23, filters, woocommerce, products 
  Text Domain: avalon23-extend-pack
  Domain Path: /languages
  WC requires at least: 3.6
  WC tested up to: 4.6
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
define('AVALON23_EXTEND_PATH', plugin_dir_path(__FILE__));
define('AVALON23_EXTEND_LINK', plugin_dir_url(__FILE__));
define('AVALON23_EXTEND_VERSION', '1.0.0');
class Avalon23_Extend_Pack {
	public function __construct() {		
	}
	public function init() {
		add_filter('avalon23_min_max_prices', array($this, 'get_filtered_price'), 99, 2);
		add_action('avalon23_draw_infotab', array($this, 'draw_info'));	
		add_action('wp_footer', array($this, 'wp_footer'));	
		wp_enqueue_style( 'avalon23_pack_css', AVALON23_EXTEND_LINK . 'assets/css/front.css',  array(), AVALON23_EXTEND_VERSION );
		wp_enqueue_script( 'avalon23_pack_js', AVALON23_EXTEND_LINK . 'assets/js/front.js', array(), AVALON23_EXTEND_VERSION );
		wp_add_inline_style( 'avalon23_pack_css', $this->get_inline_css());
		
		add_action('avalon23_extend_settings', array($this, 'add_settings'), 11);
		add_action('admin_enqueue_scripts', array($this, 'admin_js'));

	}
	public function admin_js() {
		if (isset($_GET['page']) && $_GET['page'] == 'avalon23') {
			wp_enqueue_script('avalo23_pack_admin_js', AVALON23_EXTEND_LINK . 'assets/js/admin.js', array(), AVALON23_EXTEND_VERSION );		
		}
	}
	public function get_filtered_price( $min_max, $filter_id ){
		global $wpdb;
		$args = avalon23()->filter->generate_predefined_query(array(), $filter_id );
		$tax_query = isset($args['tax_query']) ? $args['tax_query'] : array();

		$meta_query = isset($args['meta_query']) ? $args['meta_query'] : array();

		if (avalon23()->filter->get_current_taxonomy() ) {
			$tax_query[] = array(
				'taxonomy' => avalon23()->filter->current_category->taxonomy,
				'terms' => avalon23()->filter->current_category->term_id,
				'field' => 'term_id',
			);
		}

		$meta_query = new WP_Meta_Query($meta_query);
		$tax_query = new WP_Tax_Query($tax_query);

		$meta_query_sql = $meta_query->get_sql('post', $wpdb->posts, 'ID');
		$tax_query_sql = $tax_query->get_sql($wpdb->posts, 'ID');

		$in_sql = '';
		$not_in_sql = '';
		$author_in_sql = '';

		if (isset($args['post__in']) && $args['post__in']) {
			$in_sql = " AND {$wpdb->posts}.ID IN (" . implode(',', $args['post__in']) . ')';
		}

		if (isset($args['post__not_in']) && $args['post__not_in']) {
			$in_sql = " AND {$wpdb->posts}.ID NOT IN (" . implode(',', $args['post__not_in']) . ')';
		}
		if (isset($args['author__in']) && $args['author__in']) {
			$in_sql = " AND {$wpdb->posts}.post_author  IN (" . implode(',', $args['author__in']) . ')';
		}

		$sql = "SELECT min( FLOOR( price_meta.meta_value + 0.0)  ) as min_price, max( CEILING( price_meta.meta_value + 0.0)  )as max_price FROM {$wpdb->posts} ";
		$sql .= " LEFT JOIN {$wpdb->postmeta} as price_meta ON {$wpdb->posts}.ID = price_meta.post_id " . $tax_query_sql['join'] . $meta_query_sql['join'];
		$sql .= " WHERE {$wpdb->posts}.post_type = 'product'
					AND {$wpdb->posts}.post_status = 'publish'
					AND price_meta.meta_key IN ('" . implode("','", array_map('esc_sql', apply_filters('woocommerce_price_filter_meta_keys', array('_price')))) . "')
					AND price_meta.meta_value > '' " . $tax_query_sql['where'] . $meta_query_sql['where'];
		$sql = apply_filters('avalon23_get_filtered_price_query', $sql, $args);		
		return $wpdb->get_row( $sql );
	}
	
	public function draw_info() {
		include(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, AVALON23_EXTEND_PATH . 'views/info.php'));
	}
	public function wp_footer() {
		$src = AVALON23_EXTEND_LINK . 'assets/img/200x200_search.gif';
		$attachment_id = Avalon23_Settings::get('preload_image');
		$img_src = wp_get_attachment_image_src($attachment_id, 'thumbnail');
		if (is_array($img_src) && !empty($img_src[0])) {
			$src = $img_src[0];
		}		
		include_once AVALON23_EXTEND_PATH . 'views/footer.php';
	}
	public function add_settings( $rows ) {
		$optimize_settings = array();
		$images_fields = '';
		$uploader_data = array(
			'href' => 'javasctipt: void(0);',
			'onclick' => 'return avalon23_change_preloader_image(this);',
			'class' => 'avalon23_override_field_type',
			'data-field-type-override' => 'image',
			'data-table-id' => 0,
			'data-field-id' => 0);
		$uploader_delete_data = array(
			'href' => 'javasctipt: void(0);',
			'onclick' => 'return avalon23_delete_preloader_image(this);',
			'data-table-id' => 0,
			'data-field-id' => 0
		);	
		$color_data = array(
			'class' => 'avalon23-color-field avalon23_override_field_type',
			'data-table-id' => 0,
			'data-field-id' => 0,
			'data-field' => 'preload_color',
			'value' => Avalon23_Settings::get('preload_color'),
		);
		$img_id = 0;
		$img_id = Avalon23_Settings::get('preload_image');
		$optimize_settings = [
			[
				'title' => esc_html__('Preloader image', 'avalon23-products-filter'),
				'value' => AVALON23_HELPER::draw_image_uploader( $img_id, $uploader_data, $uploader_delete_data),
				'notes' => esc_html__('This is an image preloader for Ajax filtering. Better to use GIF', 'avalon23-products-filter')
			],
			[
				'title' => esc_html__('Preloader bg color', 'avalon23-products-filter'),
				'value' => AVALON23_HELPER::draw_color_piker($color_data),
				'notes' => esc_html__('This is a bg color of preloader for Ajax filtering.', 'avalon23-products-filter')
			],
		];		
		return array_merge($rows, $optimize_settings);
	}
	public function get_inline_css() {
		$color = Avalon23_Settings::get('preload_color');
		if (!$color) {
			$color = '#bbbdbf';
		}
		ob_start();
		?>
		.avalon23_loader_wrapper:after{
			background: <?php echo esc_attr($color) ?>
		}
		<?php
		return ob_get_clean();
	}
	
}
if (in_array('avalon23-products-filter/avalon23-products-filter.php', apply_filters( 'active_plugins', get_option('active_plugins') ) ) ||  in_array('avalon23-products-filter-for-woocommerce/avalon23-products-filter.php', apply_filters( 'active_plugins', get_option('active_plugins') ) )) {
	$avalon23_ext = new Avalon23_Extend_Pack();
	add_action('init', array($avalon23_ext, 'init'), 9999);
}
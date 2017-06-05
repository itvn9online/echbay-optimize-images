<?php
/**
 * Plugin Name: EchBay Optimize Images
 * Description: Set token key for access to admin page
 * Plugin URI: https://www.facebook.com/webgiare.org/
 * Author: Dao Quoc Dai
 * Author URI: https://www.facebook.com/ech.bay/
 * Version: 1.0.0
 * Text Domain: echbayeoi
 * Domain Path: /languages/
 * License: GPLv2 or later
 */

define ( 'EOI_DF_DIR', dirname ( __FILE__ ) . '/' );
// echo EOI_DF_DIR . "\n";

define ( 'EOI_COOKIES_FOR_MODIFE', 'EOI_cokies_token_for_admin_url' );


// Exit if accessed directly
if ( ! defined ( 'ABSPATH' ) ) {
	die ( EOI_check_and_resize_image_now() );
}

define ( 'EOI_DF_VERSION', '1.0.0' );
// echo EOI_DF_VERSION . "\n";

define ( 'EOI_THIS_PLUGIN_NAME', 'EchBay Optimize Images' );
// echo EOI_THIS_PLUGIN_NAME . "\n";




// global echbay plugins menu name
// check if not exist -> add new
if ( ! defined ( 'EBP_GLOBAL_PLUGINS_SLUG_NAME' ) ) {
	define ( 'EBP_GLOBAL_PLUGINS_SLUG_NAME', 'echbay-plugins-menu' );
	define ( 'EBP_GLOBAL_PLUGINS_MENU_NAME', 'Webgiare Plugins' );
	
	define ( 'EOI_ADD_TO_SUB_MENU', false );
}
// exist -> add sub-menu
else {
	define ( 'EOI_ADD_TO_SUB_MENU', true );
}





/*
* class.php
*/
function EOI_create_token_for_resize () {
	return md5 ( substr( md5 ( str_replace( 'www.', '', $_SERVER['HTTP_HOST'] ) ), 5, 5 ) );
}



// check class exist
if (! class_exists ( 'EOI_Actions_Module' )) {
	
	// my class
	class EOI_Actions_Module {
		
		/*
		* config
		*/
		var $eb_plugin_data = '';
		
		var $eb_plugin_media_version = EOI_DF_VERSION;
		
		var $eb_plugin_prefix_option = '_aoi_token_for_admin_url';
		
		var $eb_plugin_root_dir = '';
		
		var $eb_plugin_url = '';
		
		var $eb_plugin_nonce = '';
		
		var $eb_plugin_admin_dir = 'wp-admin';
		
		var $web_link = '';
		
		var $eb_plugin_en_queue = 'EAS-static-';
		
		var $eb_plugin_cookie_name = '';
		
		
		/*
		* begin
		*/
		function load() {
			
			
			/*
			* Check and set config value
			*/
			// root dir
			$this->eb_plugin_root_dir = basename ( EOI_DF_DIR );
			
			// Get version by time file modife
			$this->eb_plugin_media_version = filemtime( EOI_DF_DIR . 'admin.js' );
			
			// URL to this plugin
//			$this->eb_plugin_url = plugins_url () . '/' . EOI_DF_ROOT_DIR . '/';
			$this->eb_plugin_url = plugins_url () . '/' . $this->eb_plugin_root_dir . '/';
			
			// nonce for echbay plugin
//			$this->eb_plugin_nonce = EOI_DF_ROOT_DIR . EOI_DF_VERSION;
			$this->eb_plugin_nonce = $this->eb_plugin_root_dir . EOI_DF_VERSION;
			
			//
			if ( defined ( 'WP_ADMIN_DIR' ) ) {
				$this->eb_plugin_admin_dir = WP_ADMIN_DIR;
			}
		}
		
		function get_admin_link () {
			return $this->get_web_link() . '/' . $this->eb_plugin_admin_dir . '/admin.php?page=' . strtolower( str_replace( ' ', '-', EOI_THIS_PLUGIN_NAME ) );
		}
		
		function get_web_link () {
			if ( $this->web_link != '' ) {
				return $this->web_link;
			}
			
			//
			if ( defined('WP_SITEURL') ) {
				$this->web_link = WP_SITEURL;
			}
			else if ( defined('WP_HOME') ) {
				$this->web_link = WP_HOME;
			}
			else {
				$this->web_link = get_option ( 'siteurl' );
			}
			
			//
			$this->web_link = explode( '/', $this->web_link );
//			print_r( $this->web_link );
			
			$this->web_link[2] = $_SERVER['HTTP_HOST'];
//			print_r( $this->web_link );
			
			// ->
			$this->web_link = implode( '/', $this->web_link );
			
			//
			if ( substr( $this->web_link, -1 ) == '/' ) {
				$this->web_link = substr( $this->web_link, 0, -1 );
			}
//			echo $this->web_link; exit();
			
			//
			return $this->web_link;
		}
		
		
		function get_file_in_folder($open_folder, $type = '', $brace = '') {
			if ($brace != '') {
				$arr = glob ( $open_folder . $brace, GLOB_BRACE );
			} else {
				$arr = glob ( $open_folder . '*' );
			}
			
			//
			if ($type == 'file') {
		
				$arr = array_filter ( $arr, 'is_file' );
				
				//
				foreach ( $arr as $k => $v ) {
					$arr[$k] = basename( $v );
				}
				
		//		print_r($arr);
		//		exit();
			} else if ($type == 'dir') {
				$arr = array_filter ( $arr, 'is_dir' );
			}
			
			//
			foreach ( $arr as $k => $v ) {
				$arr[$k] = str_replace( '../', '', $v );
			}
			
			//
			return $arr;
		}
		
		
		// form admin
		function admin() {
			global $wpdb;
			
			
			
			//
//			echo '<br><h1><a href="' . $this->get_admin_link() . '">' . EOI_THIS_PLUGIN_NAME . '</a></h1><br>';
			
			$arr = wp_upload_dir();
//			print_r( $arr );
			
			// kiểm tra nếu có file rồi -> đổi tên file luôn
			$path = dirname( $arr['path'] );
			$basedir = $arr['basedir'];
			$baseurl = $arr['baseurl'];
			
			//
			$all_sizes = get_intermediate_image_sizes();
			$all_sizes[] = 'full';
			$all_sizes = array_reverse( $all_sizes );
//			print_r( $all_sizes );
			
			//
			$total_post = 0;
			$sql = $wpdb->get_results( "SELECT COUNT(ID)
			FROM
				`" . $wpdb->posts . "`
			WHERE
				post_type = 'attachment'", OBJECT );
//			print_r( $sql );
			if ( isset($sql[0]) ) {
				$sql = $sql[0];
				foreach ( $sql as $v ) {
					$total_post = $v;
				}
			}
			$post_per_page = 50;
			$trang = isset($_GET['trang']) ? (int)$_GET['trang'] : 1;
			$totalPage = ceil ( $total_post / $post_per_page );
			if ($trang > $totalPage) {
				$trang = $totalPage;
			}
			else if ( $trang < 1 ) {
				$trang = 1;
			}
			//echo $trang . '<br>' . "\n";
			$offset = ($trang - 1) * $post_per_page;
			
			//
			$sql = $wpdb->get_results( "SELECT *
			FROM
				`" . $wpdb->posts . "`
			WHERE
				post_type = 'attachment'
			ORDER BY
				ID DESC
			LIMIT " . $offset . ", " . $post_per_page, OBJECT );
//			print_r( $sql );
			
			//
			$arr_list_file = array();
			foreach ( $sql as $v ) {
//				echo $v->guid . '<br>' . "\n";
				
				foreach ( $all_sizes as $v2 ) {
//					$a = wp_get_attachment_image( $v->ID, $v2 );
					$a = wp_get_attachment_image_src( $v->ID, $v2 );
//					$a[0] = str_replace( $baseurl, $basedir, $a[0] );
//					print_r( $a );
					
					$arr_list_file[ $a[0] ] = $a[1] . 'x' . $a[2];
				}
			}
//			print_r($arr_list_file);
//			echo implode( "\n", $arr_list_file );
			$str_list_file = '';
			foreach ( $arr_list_file as $k => $v ) {
				$str_list_file .= '<div data-path="' . $k . '" data-size="' . $v . '"></div>';
			}
			
			//
//			echo 1;
//			exit();
			
			// admin -> used real time version
			$this->eb_plugin_media_version = time();
			$this->get_web_link();
			
			
			//
			$main = file_get_contents ( EOI_DF_DIR . 'admin.html', 1 );
			
			$main = $this->template ( $main, array (
				'js' => 'var EOI_url_for_process="' . $this->eb_plugin_url . basename( __FILE__ ) . '?token=' . EOI_create_token_for_resize() . '",
					current_dir_upload="' . urlencode( $basedir ) . '",
					EOI_baseurl="' . urlencode($baseurl) . '",
					EOI_cookies_for_modife="' . EOI_COOKIES_FOR_MODIFE . '",
					EOI_total_page=' . $totalPage . ',
					EOI_current_page=' . $trang . ',
					EOI_post_per_page=' . $post_per_page . ',
					EOI_total_post=' . $total_post . ';',
				
				'_ebnonce' => wp_create_nonce( $this->eb_plugin_nonce ),
				
				'plugin_name' => EOI_THIS_PLUGIN_NAME,
				'plugin_version' => EOI_DF_VERSION,
				'plugin_link' => $this->get_admin_link(),
				
				'web_link' => $this->web_link,
				
				'str_list_file' => $str_list_file,
				
				'custom_setting' => $this->eb_plugin_data,
			) );
			
			echo $main;
			
			echo '<p>* Other <a href="' . $this->web_link . '/' . $this->eb_plugin_admin_dir . '/plugin-install.php?s=itvn9online&tab=search&type=author" target="_blank">WordPress Plugins</a> written by the same author. Thanks for choose us!</p>';
			
		}
		
		
		
		
		// add value to template file
		function template($temp, $val = array(), $tmp = 'tmp') {
			foreach ( $val as $k => $v ) {
				$temp = str_replace ( '{' . $tmp . '.' . $k . '}', $v, $temp );
			}
			
			return $temp;
		}
	} // end my class
} // end check class exist



//
class EOI_SimpleImage {
	var $image;
	var $image_width;
	var $image_height;
	var $image_type;
	
	function load($filename) {
		$image_info = getimagesize ( $filename ) or die( $filename );
		$this->image_width = $image_info [0];
		$this->image_height = $image_info [1];
		$this->image_type = $image_info [2];
		
		//
		if ($this->image_type == IMAGETYPE_GIF) {
			$this->image = imagecreatefromgif ( $filename );
		} elseif ($this->image_type == IMAGETYPE_PNG) {
			$this->image = imagecreatefrompng ( $filename );
		} else {
			$this->image = imagecreatefromjpeg ( $filename );
		}
	}
	
	function save($filename, $image_type = '', $compression = 100, $permissions = null) {
		if ( $image_type == '' ) $image_type = $this->image_type;
		
		if ($image_type == IMAGETYPE_GIF) {
			imagegif ( $this->image, $filename );
		} elseif ($image_type == IMAGETYPE_PNG) {
			imagepng ( $this->image, $filename, 0, PNG_NO_FILTER );
		} else {
			imagejpeg ( $this->image, $filename, $compression );
		}
		
		if ($permissions != null) {
			chmod ( $filename, $permissions );
		}
	}
	
	function output($image_type = '') {
		if ( $image_type == '' ) $image_type = $this->image_type;
		
		if ($image_type == IMAGETYPE_JPEG) {
			imagejpeg ( $this->image );
		} elseif ($image_type == IMAGETYPE_GIF) {
			imagegif ( $this->image );
		} elseif ($image_type == IMAGETYPE_PNG) {
			imagepng ( $this->image );
		}
	}
	
	function getWidth() {
		return imagesx ( $this->image );
	}
	
	function getHeight() {
		return imagesy ( $this->image );
	}
	
	function resizeToHeight($height) {
		$ratio = $height / $this->getHeight ();
		$width = $this->getWidth () * $ratio;
		$this->resize ( $width, $height );
	}
	
	function resizeToWidth($width) {
		$ratio = $width / $this->getWidth ();
		$height = $this->getheight () * $ratio;
		$this->resize ( $width, $height );
	}
	
	function scale($scale) {
		$width = $this->getWidth () * $scale / 100;
		$height = $this->getheight () * $scale / 100;
		$this->resize ( $width, $height );
	}
	
	function resize($width, $height) {
		$new_image = imagecreatetruecolor ( $width, $height );
//		echo $this->image_type; exit();
		
		// set transparent for png file
		if ($this->image_type == IMAGETYPE_PNG) {
			imagealphablending($new_image, false);
			imagesavealpha($new_image, true);
			$transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
			imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
		}
		
		imagecopyresampled ( $new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth (), $this->getHeight () );
		
		$this->image = $new_image;
	}
}




//
function EOI_resize_png( $source_file, $dst_file, $new_width, $new_height, $width = 0, $height = 0) {
	if ( $width == 0 || $height == 0 ) {
		$a = getimagesize( $source_file );
		$width = $a[0];
		$height = $a[1];
	}
	
	// Sử dụng class chung cho gọn
	return EOI_resize_images( $source_file, $dst_file, $new_width, $new_height, $width, $height);
	
	// Giữ nguyên nền trong suốt
	/*
	$source_image = imagecreatefrompng($source_file);
	$image = imagecreatetruecolor($new_width, $new_height);
	
	imagealphablending($image, false);
	imagesavealpha($image, true);
	$transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
	imagefilledrectangle($image, 0, 0, $width, $height, $transparent);
	imagecopyresampled($image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
	imagepng( $image, $dst_file, 0, PNG_NO_FILTER );
	*/
	
	// Chuyển sang nền trắng
	$image = imagecreatefrompng($source_file);
	$bg = imagecreatetruecolor(imagesx($image), imagesy($image));
	imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
	imagealphablending($bg, true);
	imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
//	imagedestroy($image);
	imagejpeg($bg, $file_resize, 75);
//	imagedestroy($bg);
}



function EOI_resize_images( $source_file, $dst_file, $new_width, $new_height, $width = 0, $height = 0) {
	$image = new EOI_SimpleImage ();
	$image->load( $source_file );
	
	if ( $width == 0 || $height == 0 ) {
		$a = getimagesize( $source_file );
		$width = $a[0];
		$height = $a[1];
	}
	
	if ( $new_width == $new_height ) {
		if ( $width > $height ) {
			$image->resizeToWidth( $new_width );
		}
		else {
			$image->resizeToHeight( $new_height );
		}
//		$image->resize( $new_width, $new_height );
	}
	else if ( $new_width > $new_height ) {
		$image->resizeToWidth( $new_width );
	}
	else {
		$image->resizeToHeight( $new_height );
	}
	$image->save($dst_file, '', 80);
	
	echo ' <strong>SimpleImage</strong>; ';
}





function EOI_check_and_resize_image_now () {
	
//	print_r($_GET); exit();
	
	//
	if ( ! isset( $_GET['token'] )
	|| $_GET['token'] != EOI_create_token_for_resize()
	||  ! isset( $_GET['file'] ) ) {
		die('Parameter not found');
	}
	
	//
	if ( ! isset( $_COOKIE[ EOI_COOKIES_FOR_MODIFE ] ) ) {
		exit();
	}
//	print_r( $_COOKIE );
	
	//
//	$_COOKIE[ EOI_COOKIES_FOR_MODIFE ] = urldecode( $_COOKIE[ EOI_COOKIES_FOR_MODIFE ] );
	
	//
	$file_path = isset( $_GET['path'] ) ? urldecode( $_GET['path'] ) : '';
	if ( $file_path == '' || ! is_dir( $file_path ) ) {
		die('Dir not found: ' . $file_path);
	}
//	echo $file_path . "\n";
	
	//
//	$file_resize = $_COOKIE[ EOI_COOKIES_FOR_MODIFE ] . $_GET['file'];
	$file_resize = isset( $_GET['file'] ) ? $file_path . urldecode( $_GET['file'] ) : '';
	if ( $file_resize == '' || ! file_exists( $file_resize ) ) {
		die('File not found: ' . $file_resize);
	}
//	echo $file_resize . "\n";
	
	//
	if ( isset( $_GET['nooptimize'] ) ) {
		die('Filesize: ' . ceil ( filesize ( $file_resize )/ 1000 ) . 'kb');
	}
	
	//
	if ( ! isset( $_GET['current_width'] ) || ! isset( $_GET['current_height'] ) ) {
		die('SIZE not found');
	}
	$current_width = (int)$_GET['current_width'];
	$current_height = (int)$_GET['current_height'];
	
	//
//	$parent_file = $_COOKIE[ EOI_COOKIES_FOR_MODIFE ] . $_GET['parent_file'];
	$parent_file = isset( $_GET['parent_file'] ) ? $file_path . urldecode( $_GET['parent_file'] ) : '';
	if ( $parent_file == '' || ! file_exists( $parent_file ) ) {
		die('Source file not found: ' . $parent_file);
	}
//	echo $parent_file . "\n";
	$arr_parent_size = getimagesize( $parent_file );
	$file_parent_size = filesize( $parent_file );
	echo ' Source file: ' . $arr_parent_size['mime'] . '(' . ceil ( $file_parent_size/ 1000 ) . 'kb) ';
	
	// kiểm tra định dạng file
	switch ( $arr_parent_size['mime'] ) {
		case "image/jpeg":
		case "image/png":
		case "image/gif":
			break;
		
		default:
			die( ' <span class="redcolor">File type: ' . $arr_parent_size['mime'] . ' not support</span> ' );
	}
	
	
	
	// chỉ áp dụng với file thumbnail -> giữ nguyên file gốc
	$check_thumb = explode( '-', $file_resize );
	$check_thumb = $check_thumb[ count( $check_thumb ) - 1 ];
//	echo $check_thumb . "\n";
	$check_thumb = explode( '.', $check_thumb );
	$check_thumb = explode( 'x', $check_thumb[0] );
//	print_r($check_thumb);
	if ( count( $check_thumb ) != 2 ) {
		die('Function for thumbail only');
	}
//	exit();
	
	
	
	//
	$arr_size = getimagesize( $file_resize );
//	print_r( $arr_size ); exit();
//	echo ' File type: ' . $arr_parent_size['mime'] . '-' . $arr_size['mime'] . '; ';
	
	//
	$old_size = filesize ( $file_resize );
//	print_r( $old_size ); exit();
	echo ' New file: ' . $arr_size['mime'] . '(' . ceil ( $old_size/ 1000 ) . 'kb) ';
	
	
	// sử dụng EOI_SimpleImage đối với JPG
	if ( $arr_parent_size['mime'] == 'image/jpeg' ) {
		EOI_resize_images( $parent_file, $file_resize, $current_width, $current_height, $arr_parent_size[0], $arr_parent_size[1] );
	}
	else {
		
		// nếu ảnh mới là ảnh vuống, mà ảnh gốc không phải ảnh vuông
		// -> thumbnail small -> resize theo tỉ lệ
		if ( $current_width == $current_height && $arr_parent_size[0] != $arr_parent_size[1] ) {
			echo ' Resize small thumb - ';
		}
		// nếu ảnh resize đạt chuẩn rồi thì thôi
		else if ( $old_size <= $file_parent_size ) {
			die('<em>File has been resize</em>');
		}
		
		
		
		// Ưu tiên sử dụng Imagick (nếu có)
		if ( class_exists('Imagick') ) {
			// create new imagick object
			$image = new Imagick();
			$image->readImage($parent_file);
			
			if ( $arr_parent_size[0] > $arr_parent_size[1] ) {
				$image->resizeImage($current_width, 0, Imagick::FILTER_CATROM, 1);
			} else {
				$image->resizeImage(0, $current_height, Imagick::FILTER_CATROM, 1);
			}
//			$image->resizeImage($current_width, $current_height, Imagick::FILTER_LANCZOS, 1);
//			$image->resizeImage($current_width, $current_height, Imagick::FILTER_CATROM, 1);
			
			// optimize the image layers
			/*
			if ( $arr_parent_size['mime'] == 'image/jpeg' ) {
				$image->setImageFormat( 'jpg' );
				$image->setImageCompression(Imagick::COMPRESSION_JPEG);
			}
			else {
				*/
				$image->setImageCompression(Imagick::COMPRESSION_UNDEFINED);
//			}
			$image->setImageCompressionQuality( 75 );
			$image->optimizeImageLayers();
			
			$image->writeImages($file_resize, true);
			$image->destroy();
			
			echo ' <strong>Imagick</strong>; ';
		}
		else if ( $arr_parent_size['mime'] == 'image/png' ) {
			// Nhanh gọn thì sử dụng function cho PNG riêng
			EOI_resize_png( $parent_file, $file_resize, $current_width, $current_height, $arr_parent_size[0], $arr_parent_size[1] );
		}
//		else {
		else if ( $old_size > $file_parent_size ) {
			copy( $parent_file, $file_resize );
			chmod ( $file_resize, 0666 );
		}
	}
//	sleep(2);
	
	
	//
	/*
	if ( $arr_parent_size['mime'] == IMAGETYPE_PNG ) {
		$file_type = 'png';
	}
	else if ( $arr_parent_size['mime'] == IMAGETYPE_JPEG ) {
		$file_type = 'jpg';
	}
	else if ( $arr_parent_size['mime'] == IMAGETYPE_GIF ) {
		$file_type = 'gif';
	}
	else {
		$file_type = explode( '.', $parent_file );
		$file_type = $file_type[ count( $file_type ) - 1 ];
	}
	$file_test = dirname( $file_resize ) . '/EOI_check_img_after_resize.' . $file_type;
//	echo $file_test;
//	echo $file_resize;
	copy( $file_resize, $file_test );
	chmod ( $file_test, '0666' );
	*/
	
	//
	$check_new_size = filesize ( $file_resize );
//	$check_new_size = filesize ( $file_test );
//	unlink( $file_test );
	echo ' New size: ' . ceil ( $check_new_size/ 1000 ) . 'kb; ';
	
	// Nếu file mới vẫn chưa đạt chuẩn
	if ( $check_new_size > $file_parent_size ) {
		// Copy trực tiếp luôn cho đỡ lệch
		copy( $parent_file, $file_resize );
		chmod ( $file_resize, 0666 );
		echo ' <strong><em>Recopy file</em></strong> ';
	}
	
}




/*
 * Show in admin
 */
function EOI_show_setting_form_in_admin() {
	global $EOI_func;
	
	$EOI_func->admin ();
}

function EOI_admin_menu () {
	
	// only show menu if administrator login
	if ( ! current_user_can('manage_options') )  {
		return false;
	}
	
	// menu name
	$a = EOI_THIS_PLUGIN_NAME;
	
	// add main menu
	if ( EOI_ADD_TO_SUB_MENU == false ) {
		add_menu_page( $a, EBP_GLOBAL_PLUGINS_MENU_NAME, 'manage_options', EBP_GLOBAL_PLUGINS_SLUG_NAME, 'EOI_show_setting_form_in_admin', NULL, 99 );
	}
	
	// add sub-menu
	add_submenu_page( EBP_GLOBAL_PLUGINS_SLUG_NAME, $a, trim( str_replace( 'EchBay', '', $a ) ), 'manage_options', strtolower( str_replace( ' ', '-', $a ) ), 'EOI_show_setting_form_in_admin' );
	
}
// end class.php





// check and call function for admin
if (is_admin ()) {
	//
	$EOI_func = new EOI_Actions_Module ();
	
	// load custom value in database
	$EOI_func->load ();
	
	
	add_action ( 'admin_menu', 'EOI_admin_menu' );
	
}




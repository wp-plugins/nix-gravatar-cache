<?php
/*
Plugin Name: NIX Gravatar Cache
Author: NIX Solutions Ltd
Version: 0.0.6
Description: Cache Gravatar in your Host and speed up your site
Author URI: http://nixsolutions.com/departments/cms/
*/

class NFGC_Gravatar_Cache {

    protected $upload_url;
    protected $upload_path;
    protected $plugin_dir_path;

    public $plugin_name = 'NIX Gravatar Cache';

    function __construct(){
        if ( get_option( 'upload_url_path' ) ) {
            $this->upload_url  = get_option( 'upload_url_path' );
            $this->upload_path = get_option( 'upload_path' );
        }
        else {
            $up_dir = wp_upload_dir();

            $this->upload_url  = $up_dir['baseurl'];
            $this->upload_path = $up_dir['basedir'];
        }

        $this->plugin_dir_path = plugin_dir_path( __FILE__ );

        require_once $this->plugin_dir_path . '/messages.class.php';
        NFGC_Messages::init();

        $active = get_option( 'nf_c_a_options' );
        if ( $active[0]['active'] == 1 ) {
            add_filter( 'get_avatar', array( $this,'get_cached_avatar' ), -1000000000, 5 );
        }

        add_action( 'admin_menu', array( $this,'add_admin_menu' ) );
        register_activation_hook( __FILE__, array( $this, 'activate' )  );
        $this->init();

        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        register_uninstall_hook( __FILE__ , array( $this, 'uninstall' ) );

        if ( !is_writable( $this->upload_path.'/gravatar/' ) && is_dir( $this->upload_path.'/gravatar/' ) ) {
            NFGC_Messages::add_message( 'error', 'Please set write permissions for "'. $this->upload_path .'/gravatar/"' );
        }else{
            if ( @!mkdir( $this->upload_path.'/gravatar/', 0777 ) && ! is_dir( $this->upload_path.'/gravatar/' ) ) {
                NFGC_Messages::add_message( 'error', 'Could not create directory "gravatar". Please set write permissions for "'. $this->upload_path .'/gravatar/"'  );
            }
        }

        if ( isset ( $_POST['nf_clear_cache'] ) )
            $this->clear_cache();

    }

    public function get_template_path() {
        return $this->plugin_dir_path .'template';
    }

    // Activate plugin and update default option
    public function activate() {

        $dir = $this->upload_path.'/gravatar/';

        // delete_option('nf_c_a_options');
        if ( get_option( 'nf_c_a_options' ) == false ) {
            $default_options = array('active'   => 1,
                                     'ttl_day'  => 10,
                                     'ttl_hour' => 0,
                                     'ttl_min'  => 0
                                    );
            update_option( 'nf_c_a_options', array( $default_options ) );
        }

    }

    // Deactivate plugin and clear cache
    public function deactivate() {

        $this->clear_cache();

    }

    // Notice in plugin options page
    public function admin_help_notice() {
        global $current_screen;
        if ( $current_screen->base == 'settings_page_'. basename( __FILE__,'.php' ) ) {
            return true;
        }
    }

    // convert ttl option to second
    private function cache_to_second(){
        $cache_time = get_option( 'nf_c_a_options' );
        $cache_time = array_reverse( $cache_time[0] );

        $action = array();
        foreach ( $cache_time as $key => $value ) {
            if ( $key == 'active' )
                continue;

            switch ( $key ) {
                case 'ttl_min':
                    $cache_second = $value != 0 ? $value*60 : '';
                    break;
                case 'ttl_hour':
                    $cache_second = $value != 0 ? ( $value*60*60 ) + $cache_second : $cache_second;
                    break;
                case 'ttl_day':
                    $cache_second = $value != 0 ? ( $value*60*60*24 ) + $cache_second : $cache_second;
                    break;
            }

        }

        if ( ! $cache_second ) {
            $cache_second = 864000;// TTL of cache in seconds (10 days)

        }

        return $cache_second;
    }


    // The main functional
    public function get_cached_avatar( $source, $id_or_email, $size, $default, $alt ) {

        if ( !is_writable( $this->upload_path.'/gravatar/' ) || is_admin() ) {
            return $source;
        }
        $time = $this->cache_to_second();

        preg_match('/d=([^&]*)/', $source, $d_tmp);
        $g_url_default_sorce = isset($d_tmp[1]) ? $d_tmp[1] : false;

        preg_match('/forcedefault=([^&]*)/', $source, $d_tmp);
        $g_forcedefault = isset($d_tmp[1]) ? $d_tmp[1] : false;

        preg_match('/avatar\/([a-z0-9]+)\?s=(\d+)/', $source, $tmp);
        $garvatar_id = $tmp[1];

        $file_name      = md5($garvatar_id.$g_url_default_sorce);
        $g_path         = $this->upload_path.'/gravatar/'.$file_name.'-s'.$size.'.jpg';
        $g_path_default = $this->upload_path.'/gravatar/default'.'-s'.$size.'.jpg';
        $g_url          = $this->upload_url.'/gravatar/'.$file_name.'-s'.$size.'.jpg';
        $g_url_default  = $this->upload_url.'/gravatar/'.'default'.'-s'.$size.'.jpg';

        // Check cache
        static $nf_avatars_cache = null;
        if ($nf_avatars_cache === null)    $nf_avatars_cache = get_option('nf_avatars_cache');
        if (! is_array($nf_avatars_cache)) $nf_avatars_cache = array();

        if (isset($nf_avatars_cache[$garvatar_id][$size])) {
            $g_url  = $nf_avatars_cache[$garvatar_id][$size]['url'];
            $g_path = $nf_avatars_cache[$garvatar_id][$size]['path'];
        }

        if (! is_file($g_path) || (time()-filemtime($g_path)) > $time) {
            $curl_url = 'http://www.gravatar.com/avatar/'.$garvatar_id.'?s='.$size.'&r=G&d='.$g_url_default_sorce;

            $ch = curl_init($curl_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            $response    = curl_exec($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header      = substr($response, 0, $header_size);

            // Checking for redirect
            $header_array = array();
            preg_match('/^Location\: (.*)$/m', $header, $header_array);
            $redirect_url = isset($header_array[1]) ? $header_array[1] : false;

            if ($redirect_url) {
                $g_url  = $g_url_default;
                $g_path = $g_path_default;
                if (! is_file($g_path) || (time()-filemtime($g_path)) > $time) {
                    copy($redirect_url, $g_path);
                }
            }
            else {
                // Check mime type
                $mime_str   = curl_getinfo( $ch, CURLINFO_CONTENT_TYPE );
                $mime_array = array();
                preg_match( '#/([a-z]*)#i', $mime_str, $mime_array );

                if (isset($mime_array[1])) {
                    // Write cache to file
                    $fp   = fopen( $g_path, "wb" );
                    $body = substr( $response, $header_size );
                    fwrite( $fp, $body );
                    fclose( $fp );
                }
            }
            curl_close($ch);

            $nf_avatars_cache[$garvatar_id][$size]['url']  = $g_url;
            $nf_avatars_cache[$garvatar_id][$size]['path'] = $g_path;
            update_option( 'nf_avatars_cache', $nf_avatars_cache );
        }

        return '<img alt="" src=\''.$g_url.'\' class="avatar avatar-'.$size.'" width="'.$size.'" height="'.$size.'" />';
    }

    // Create plugin option settings menu
    public function add_admin_menu() {
        // settings menu page
       add_options_page( 'Cached Avatar ', $this->plugin_name, 'manage_options', basename( __FILE__ ), array( $this,'view_options_page' ) );
    }

    // Create page option
    public function view_options_page() {
        // update options

        if ( isset( $_POST['nf_c_a_submit'] ) ) {
            $update_val_options = $_POST['nf_c_a_options'];

            foreach ( $update_val_options as $option => $value ) {
                $update_val_options[$option] = abs( intval( $value ) );
            }

            if( $update_val_options['ttl_min'] == 0 && $update_val_options['ttl_hour'] == 0 && $update_val_options['ttl_day'] == 0 ) {
                $update_val_options['ttl_day'] = 10;
            }

            update_option( 'nf_c_a_options', array( $update_val_options ) );

        }

        $options = get_option( 'nf_c_a_options' );

        include( $this->get_template_path() .'/main-options-page.php');
    }

    private function clear_cache() {
        $dir = $this->upload_path.'/gravatar/';
        $no_permision_to_delete = false;

        // Open directory
        if ( is_dir( $dir ) ) {
            if ( $opendir = opendir( $dir ) ) {
                $count = 0;
                while ( ( $file = readdir( $opendir ) ) !== false ) {
                    if ( filetype( $dir . $file ) == 'file' ) {
                        if ( @unlink( $dir . $file ) ) {
                            $count++;
                        }else {
                            $no_permision_to_delete = true;
                        }
                    }
                }
                if ( $no_permision_to_delete ) {
                    NFGC_Messages::add_message( 'error','Unable to clear the cache' );
                }else{
                    update_option('nf_avatars_cache', array() );
                    NFGC_Messages::add_message( 'info','The cache is cleared!' );
                    NFGC_Messages::add_message( 'info','Removed '.$count.' files' );
                }
                closedir( $opendir );
            }
        }
   }

    // return count and size
    public function get_cache_info() {
        $dir  = $this->upload_path.'/gravatar/';
        $skip = array('.','..');
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');

        if ( is_dir( $dir ) ) {
           $file_list = scandir( $dir );

           // delete . and ..
           foreach ( $skip as $value ) {
               unset( $file_list[ array_search( $value, $file_list ) ] );
           }

           // sum files size
           foreach ( $file_list as $file ) {
               $size     = filesize( $dir . $file );
               $all_size = $all_size + $size;
           }
        }

        $readable_form = @round( $all_size / pow( 1024, ( $i = floor( log( $all_size, 1024) ) ) ), 2 ) . ' ' . $unit[$i];

        return array( 'amount' => count( $file_list ) , 'used_space' => $readable_form );
   }

    private function init() {
        wp_enqueue_script( 'nfgc-main-script', plugins_url( '/js/main.js', __FILE__ ), array('jquery') );
        wp_enqueue_style( 'nfgc-main-style', plugins_url( '/css/style.css', __FILE__ ) );
   }


}// Class

global $nfgc;
$nfgc = new NFGC_Gravatar_Cache();
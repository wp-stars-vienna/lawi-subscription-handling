<?php


namespace Wps;

use stdClass;

class Update {
    public $plugin_name = '';
    public $plugin_slug = '';
	public $jsonfile_url = '';
	public $plugin_version = null;
	public $updater_cache_duration = 1; //43200; // 12 hours in seconds
	public $enable_caching = false;
	public $remote = null;
	public $plugin_data_object = null;
	public $author_link_html = '<a href="https://wp-stars.com/">wp-stars.com</a>';

	public function __construct(string $plugin_slug, string $plugin_name, string $jsonfile_url){

        $this->plugin_slug = $plugin_slug;
        $this->jsonfile_url = $jsonfile_url;
        $this->plugin_name = $plugin_name;
		add_action( 'init', array($this, 'on_init_action'));
	}

	/**
	 * Diese Methode wird ausgeführt sobald die init action von Wordpress ausgelöst wurde.
	 *
	 * @return void
	 */
	public function on_init_action(){
		if( current_user_can('update_plugins') ){
			add_filter('site_transient_update_plugins', array($this,'push_update'));
			add_action( 'upgrader_process_complete', array($this,'after_update'), 10, 2 );
			add_filter('plugins_api', array($this,'plugin_info'), 20, 3);
		}
	}

	public function push_update( $transient ){
	    // disable update for development
	    if(!function_exists('get_plugin_data') || get_plugin_data(WPS_WPTOOLS_FILE)['Version'] === '%%version%%'){
            return;
        }

		$this->plugin_version = get_plugin_data(WPS_WPTOOLS_FILE)['Version'];
		$this->get_remote_info_json();

		if(isset($this->remote) && $this->remote != false){
			$this->remote = json_decode( $this->remote );

			if( isset($this->remote) && version_compare( $this->plugin_version, $this->remote->version, '<' ) && version_compare(get_bloginfo('version'),$this->remote->requires, '>=' ) ) {
				$this->collect_plugin_data();
				$transient->response[$this->plugin_data_object->plugin] = $this->plugin_data_object;
			}
		}
		return $transient;
	}

	public function plugin_info($res,$action,$args){

		// do nothing if this is not about getting plugin information
		if( $action !== 'plugin_information' )
		return false;

		// do nothing if it is not our plugin
		if( $this->plugin_slug !== $args->slug )
		return $res;

		$this->collect_plugin_data();
		$res = $this->plugin_data_object;
		return $res;
	}

	public function collect_plugin_data(){
		$this->plugin_data_object = new stdClass();
		$this->plugin_data_object->name = $this->plugin_name;
		$this->plugin_data_object->slug = $this->plugin_slug;
		$this->plugin_data_object->plugin = $this->plugin_slug . '/' . $this->plugin_slug . '.php';
		$this->plugin_data_object->author = $this->author_link_html;
		$this->plugin_data_object->author_profile = '';
		$this->plugin_data_object->new_version = $this->remote->version;
		$this->plugin_data_object->tested = $this->remote->tested;
		$this->plugin_data_object->requires = $this->remote->requires;
		$this->plugin_data_object->package = $this->remote->download_url;
		$this->plugin_data_object->trunk = $this->remote->download_url;
		$this->plugin_data_object->last_updated = $this->remote->last_updated;
		$this->plugin_data_object->sections = array(
			'description' => 'description',
			'installation' => 'installation',
			'changelog' => 'changes',
		);
	}

	public function after_update( $upgrader_object, $options ) {
		if ( $options['action'] == 'update' && $options['type'] === 'plugin' )  {
			delete_transient( 'mwd-wordpress-tools' );
		}
	}

	// Plugin-Info-JSON Caching
	public function set_transient(){
		if(!is_wp_error($this->remote) && isset($this->remote['response']['code']) && $this->remote['response']['code'] == 200 && !empty($this->remote['body'])){
			$set_transient = set_transient( 'mwd-wordpress-tools', $this->remote, $this->updater_cache_duration );
		}
	}

	// Get Plugin-Info-JSON from Remote
	public function get_remote_info_json(){
		
		$remote_data = wp_remote_get( $this->jsonfile_url . '?version=' . $this->plugin_version, array(
			'timeout' => 10,
			'headers' => array(
				'Accept' => 'application/json'
			) )
		);

		$this->remote = $remote_data['body'];
	}
}
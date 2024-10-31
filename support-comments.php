<?php
/*
Plugin Name: Support Comments
Plugin URI: http://fishcantwhistle.com
Description: Import comments from a wordpress.org plugin support forum to a wordpress page or post
Version: 0.1
Author: Fish Can't Whistle
*/

$supportComments = new supportComments;

class supportComments {

	function supportComments(){
		$this->__construct();
	} // function

	function __construct(){

		add_action( 'update_support_comments', array( $this, 'update_support_comments' ) );

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		add_action( 'save_post', array( $this, 'grab_comments' ) );

		register_activation_hook( __FILE__,   array( $this, 'activate' ) );

	}

	function admin_menu() {
		add_meta_box(
			'supportComments',
			__( 'Support Forum Comments Import', 'supportComments' ),
			array( $this, 'meta_box' ),
			'post',
			'side'
		);
		add_meta_box(
			'supportComments',
			__( 'Support Forum Comments Import', 'supportComments' ),
			array( $this, 'meta_box' ),
			'page',
			'side'
		);
	}

	function meta_box( $post ) {
		?>
		<label for="plugin_info"><?php _e( 'Plugin slug:', 'supportComments' ); ?></label>
		<input type="text" name="supportComments" id="plugin_info" value="<?php esc_attr_e( get_post_meta( $post->ID, 'supportComments', true ) ); ?>" />
		<?php
	}

	function grab_comments($post_ID){

		//echo $post_ID;

		if ( wp_is_post_revision( $post_ID ) or wp_is_post_autosave( $post_ID ) )
			return;

		if(isset( $_POST['supportComments'] ) && !empty( $_POST['supportComments'] )){

			$plugin = trim( $_POST['supportComments'] );

			if ( !update_post_meta( $post_ID, 'supportComments', $plugin ) )
				add_post_meta( $post_ID, 'supportComments', $plugin );

		}

		global $wpdb;

		$rss_tags = array(
		'title',
		'link',
		'guid',
		'comments',
		'description',
		'pubDate',
		'category',
		'creator'
		);
		$rss_item_tag = 'item';
		$rss_url = 'http://wordpress.org/support/rss/plugin/'.get_post_meta( $post_ID, 'supportComments', true );
		//echo $rss_url; exit;
		$rssfeed = $this->rss_to_array($rss_item_tag, $rss_tags, $rss_url);

		//echo '<pre>';
		//print_r($rssfeed);
		//exit;

		foreach($rssfeed as $item){

			$comment_post_ID = $post_ID;
			$comment_author = $item['creator'];
			$comment_author_email = $item['guid'];
			$comment_author_url = $item['link'];
			$comment_date = strtotime($item['pubDate']);
			$comment_content = addslashes(urldecode($item['description']));
			$comment_approved = 0;
			$comment_agent = 'Support Forum';
			$comment_type = '';
			$comment_parent = 0;
			$user_id = 0;

			$recordexist = $wpdb->get_var("SELECT comment_ID FROM " . $wpdb->prefix . "comments WHERE comment_author_email = '$comment_author_email' LIMIT 1");

			if (!$recordexist) {
				$sql = '("'.$comment_post_ID.'", "'.$comment_author.'", "'.$comment_author_email.'", "'.$comment_author_url.'", FROM_UNIXTIME('.$comment_date.'), FROM_UNIXTIME('.$comment_date.'), "'.$comment_content.'", "'.$comment_approved.'", "'.$comment_agent.'", "'.$comment_type.'", "'.$comment_parent.'", "'.$user_id.'")';

				$wpdb->query("UPDATE $wpdb->posts SET comment_count = comment_count+1 WHERE ID = '$comment_post_ID'");

				/*
$commentlog .= addslashes('<strong>'.$ytvideo->AUTHOR[0]->NAME[0]->_text . '</strong>: ' . $ytvideo->CONTENT[0]->_text . ' @ ' . '<a href="' . get_permalink($comment_post_ID) . '">' . get_the_title($comment_post_ID) .'</a>' . '<br /><br />');
				$insertedcomments ++;
*/
				$results = $wpdb->query("INSERT INTO $wpdb->comments
				(comment_post_ID, comment_author, comment_author_email, comment_author_url, comment_date, comment_date_gmt, comment_content, comment_approved, comment_agent, comment_type, comment_parent, user_id)
				VALUES ".$sql);

			}

		return;

		}

	}

	function update_support_comments() {

		$q = new WP_Query;

		$posts = $q->query( array(
			'posts_per_page' => -1,
			'meta_key'       => 'supportComments',
			'post_type'      => 'any'
		) );

		if ( !count( $posts ) )
			return;

		foreach ( $posts as $p ) {
			$this->grab_comments($p->ID);
		}

	}

	function activate() {
		wp_schedule_event( time(), 'hourly', 'update_support_comments' );
	}

	function rss_to_array($tag, $array, $url) {
	  $doc = new DOMdocument();
	  $doc->load($url);
	  $rss_array = array();
	  $items = array();
	  foreach($doc-> getElementsByTagName($tag) AS $node) {
	    foreach($array AS $key => $value) {
	      $items[$value] = $node->getElementsByTagName($value)->item(0)->nodeValue;
	    }
	    array_push($rss_array, $items);
	  }
	  return $rss_array;
	}

}

?>
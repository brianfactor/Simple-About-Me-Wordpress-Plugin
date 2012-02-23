<?php 
/*
Plugin Name: Simple "About Me" Widget
Plugin URI: https://github.com/brianfactor/Simple-About-Me-Wordpress-Plugin
Description: A simple "About Me" widget like the one from Blogger - it outputs a name, mugshot, and a short bio.
Version: 0.3
Author: Brian Morgan
Author URI: http://brianfactor.wordpress.com
License: GPL2
*/

// (Project S.A.M. - Simple About Me)

// I'm unsure about backward compatibility - it requires at least 2.8 (when the OO widget api was created)

// Support for multiple widgets and authors coming SOON... well, at some point... maybe.

	
class simpleAM_widget extends WP_Widget {

	/* Object variables */
	
	//public $current_plugin_dir = dirname(__FILE__);	// Equal to __DIR__ in PHP 5.3
	public $default_options = array(
			'author'	=> 0,	// Start with no user.
			'title'		=> ''
	);
	
	/**
	 * Core widget functions
	 */
	
	/* Constructor method */
	
	function simpleAM_widget() {
		parent::WP_Widget(
		/* Base ID */	'simpleAMW',
		/* Name */	'Simple "About Me" Widget',
				array( 'description' => 'Displays the basic information from your profile.' )
		);
	}
	
	/* Render this widget in the sidebar */
	
	function widget( $args, $instance ) {
		extract($args); 
		
		// Get the user that this about widget is for (get their id)
		$author_id = $instance['author'];
		// Get the title
		$sam_title = $this->get_title($instance['title'], $author_id);
		
		$title = apply_filters( 'widget_title', $sam_title );
		echo $before_widget;
		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; } ?>
		
			<style type="text/css">
				.about-me-bio .excerpt-thumb { margin: 8px; } 
				.about-me-bio { position:relative; }
				.about-me-bio .excerpt-thumb img { margin: 0px; }
			</style>
		
			<div class="about-me-bio">
					<div class="excerpt-thumb"><img class="wp-post-image" src="<?php echo $this->get_mugshot_url( $author_id ); ?>" /></div>
					<p class="about-me-text"><?php echo $this->get_about_text( $author_id ); ?></p>
					<div style="clear:both"></div>
			</div>
			
		<?php echo $after_widget;
	}

	/* Output user options */
	
	function form( $instance ) {
		
		// Update the form variables if there are values stored for this instance.
		if ( $instance ) {
			// Get the user that this about widget is for (get their id)
			$author_id = $instance['author'];
			// Get the title
			$title = $this->get_title($instance['title'], $author_id);
		}
		else {
			$author_id = $this->default_options['author'];
			$title = $this->default_options['title'];
		}
		
		// ** Output input fields - the containing form has already been created. **
		
		// Options for which author's bio to output. ?>
		<p><strong>Select the author this widget is about:</strong><br />
			<select name="<?php echo $this->get_field_name('author'); ?>">
				<?php $this->blog_author_options( $author_id ); ?>
			</select>
		</p>
		
		<div class="sam-customization">
			<?php // Options for the Title ?>
			<p><strong>Title:</strong><br />
				<input class="widefat" type="text" 
					id="<?php echo $this->get_field_id('title'); ?>" 
					name="<?php echo $this->get_field_name('title'); ?>" 
					value="<?php echo $title; ?>"/>
				<br />Leave blank for default - "About Your Name."
			</p>
			
			<p><strong>Mugshot:</strong>
				<br />To get your profile picture to show up automatically, <a href="http://www.authormedia.com/2009/04/27/how-to-get-your-avatar-to-show-up-everywhere/">get a Gravatar</a>.<br />
			</p>

			<p><strong>Bio:</strong>
				<br />Go edit <a href="<?php echo admin_url('profile.php'); ?>">your Profile</a> "Biogaphical Info" to change the text of this bio.
			</p>
		</div>
		
		<p><strong>Rough Preview:</strong></p>
		<div class="sam-preview">
		
			<div style="border:1px solid grey; padding: 5px; margin: 5px;">
				<h3><?php echo $title; ?></h3>
				<div style="position:relative;">
					<div class="excerpt-thumb"><img class="wp-post-image" style="float: left;" src="<?php echo $this->get_mugshot_url( $author_id ); ?>" /></div>
					<p class="about-me-text"><?php echo $this->get_about_text( $author_id ); ?></p>
					<div style="clear:both"></div>
				</div>
			</div>
			
		</div>
	<?php }
	
	/* Sanitize and store form input */
	
	function update ( $new_instance, $old_instance ) {
		$instance = $old_instance; // Start with all the variable so we don't loose the ones the user didn't change.
		$instance['author'] = $new_instance['author'];
		if ( $new_instance['author'] != $old_instance['author'] ) { // If the form changed authors, reset the title
			$instance['title'] = $default_options['title'];
		}
		else {
			$instance['title'] = $new_instance['title'];
		}
		return $instance;
	}
	
	/**
	 * Other useful functions for this widget
	 */
	 
	/* Ouput all authors and their ids in option tags */
	
	function blog_author_options( $selected_id ) {
		// Get an array of all the users on the site that aren't subscribers
		$wp_user_search = new WP_User_Query( array( 'role' => 'administrator' ) );
		$admins = $wp_user_search->get_results();
		$wp_user_search = new WP_User_Query( array( 'role' => 'editor' ) );
		$editors = $wp_user_search->get_results();
		$wp_user_search = new WP_User_Query( array( 'role' => 'author' ) );
		$authors = $wp_user_search->get_results();
		$wp_user_search = new WP_User_Query( array( 'role' => 'contributor' ) );
		$contributors = $wp_user_search->get_results();
		$authors = array_merge($admins,$editors,$authors,$contributors);
		
		foreach ($authors as $author) {
			echo '<option value="' . $author->ID . '" ';
			if ( $selected_id == $author->ID ) { echo 'selected '; }
			echo '>';
			echo $author->display_name;
			echo '</option>';
		}
		
	}
	
	/* Retrive title/name */
	
	function get_title( $optional_text, $author_id ) {
		
		if($author_id == 0) return 'About Your Name';
		
		if ( !empty($optional_text) ) {
			return esc_html($optional_text);
		}
		else {		// If the user leaves the title blank, return "About Display Name"
			$author = get_userdata($author_id);
			$name = $author->display_name;
			return esc_html('About ' . $name);
		}
		
	}
	
	/* Retrive the "about me" text */
	
	function get_about_text ($author_id) {
		if ($author_id == 0) return '';
		
		$author = get_userdata($author_id);
		$bio = $author->user_description;
		return $bio;
	}
	
	/* Retrieve the mugshot - url to the image */
	
	function get_mugshot_url( $author_id ) {
		// Default Gravatar image (not really necessary)
		$default_url = 'http://0.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536';
		if ($author_id == 0) return '';
		
		// Get the mugshot from gravatar - http://en.gravatar.com/site/implement/hash/
		$author = get_userdata($author_id);
		$email = $author->user_email;
		$hash = md5( strtolower( trim( $email ) ) );
		$gravatar_url = 'http://www.gravatar.com/avatar/' . $hash;	// Default size: 80x80
		if ( !empty($gravatar_url) ) {
			 return $gravatar_url;
		}
		// else return $default_url;
	}
	
}

/* Register this widget and it's control options */
add_action( 'widgets_init', 'simpleAMW_init' );
function simpleAMW_init() {
	register_widget('simpleAM_widget');
}

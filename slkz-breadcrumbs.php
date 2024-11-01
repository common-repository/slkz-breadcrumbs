<?php
	/*
	Plugin Name: SlkZ Breadcrumbs
	Plugin URI: http://solykz.11vm-serv.net/travaux/wordpress/extensions/slkz-breadcrumbs
	Description: Adds a breadcrumb trail to your WordPress installation to allow your users to keep track of their location within your pages structure.
	Version: 1.0
	Author: SolykZ
	Text Domain: slkz-breadcrumbs
	Domain Path: /languages/
	Author URI: http://solykz.11vm-serv.net/
	*/
	
	// Make sure we don't expose any info if called directly
	if ( !function_exists( 'add_action' ) ) {
		echo "Unfortunately, I have nothing to declare. I am just a plugin, you know...";
		exit;
	}
	
	function slkzBreadcrumbs() {
		$breadcrumb = new slkzBreadcrumbs();
		$breadcrumb->echoOutputData();
	}
	
	Class slkzBreadcrumbs {
		/**
		 * Simple hack to get the plugin description translated in WordPress administration.
		 *
		 * @since  1.0
		 * @access private
		 * @var	string
		 */
		private $adminDesc = '';
		
		/**
		 * String used between each breadcrumb item to separate them.
		 *
		 * @since  1.0
		 * @access private
		 * @var	string
		 */
		private $itemsSeparator = '';
		
		/**
		 * Location of the WordPress blog.
		 *
		 * @since  1.0
		 * @access private
		 * @var	string
		 */
		private $siteUrl = '';
		
		/**
		 * Unique identifier of the current post.
		 *
		 * @since  1.0
		 * @access private
		 * @var	string
		 */
		private $postID = '';
		
		/**
		 * Full data of the current post.
		 *
		 * @since  1.0
		 * @access private
		 * @var	string
		 */
		private $postData = '';
		
		/**
		 * Contains the breadcrumb trail data in a manipulable form.
		 *
		 * @since  1.0
		 * @access private
		 * @var	array
		 */
		private $breadcrumbs = array();
		
		/**
		 * Breadcrumbs in their HTML presentation.
		 *
		 * @since  1.0
		 * @access private
		 * @var	string
		 */
		private $outputData = '';
		
		/**
		 * Sets up the breadcrumb trail.
		 *
		 * @since  1.0
		 * @access public
		 * @return void
		 */
		public function __construct() {
			// Initialize the translation engine
			load_plugin_textdomain('slkz-breadcrumbs', false, dirname ( plugin_basename (__FILE__) ) . '/languages/');
			
			// Register the plugin stylesheet
			wp_enqueue_style( 'slkzBreadcrumbs', plugins_url( '/styles/styles.css', __FILE__ ) );
			add_action( 'wp_enqueue_scripts', 'slkzBreadcrumbs' );
			
			// Populate some key attributes
			$this->adminDesc = __("Adds a breadcrumb trail to your WordPress installation to allow your users to keep track of their location within your pages structure.", 'slkz-breadcrumbs' );		
			$this->itemsSeparator = ' &rsaquo; ';
			$this->siteUrl = get_bloginfo('url');
			$this->postID = &$post->ID;
			$this->postData = get_post($this->postID);
			
			// Call the magic method and output the result
			$this->buildBreadcrumbs();
			$this->setOutputData();
		}
		
		/**
		 * Sets the HTML presentation of the breadcrumbs.
		 *
		 * @since  1.0
		 * @access private
		 * @return void
		 */
		private function setOutputData() {
			$output = '';
			$this->outputData = '<ul id="slkz_breadcrumbs">';
			$this->outputData .= '<li>' . __("You are here:", 'slkz-breadcrumbs' ) . ' </li>';
			end($this->breadcrumbs);
			$lastElement = key($this->breadcrumbs);
			
			foreach ( $this->breadcrumbs as $k => $breadcrumb ) {
				if ( $k !== $lastElement ) {
					$output  = ( $breadcrumb['title'] ) ?
						'<a href="' . $breadcrumb['url'] . '" title="' . $breadcrumb['title'] . '">' . $breadcrumb['name'] . '</a>' :
						'<a href="' . $breadcrumb['url'] . '">' . $breadcrumb['name'] . '</a>';
					$output .= $this->itemsSeparator;
				}
				else {
					$output = $breadcrumb['name'];
				}
				
				$this->outputData .= '<li>' . $output . '</li>';
			}
			
			$this->outputData .= '</ul>';
		}
		
		/**
		 * Adds a new item to the breadcrumb trail.
		 *
		 * @since  1.0
		 * @access private
		 * @return void
		 */
		private function setBreadcrumbItem($url, $name, $title = '') {
			$this->breadcrumbs[] = Array(
				'url'   => $url,
				'name'  => $name,
				'title' => $title,
			);
		}
		
		/**
		 * Builds the breadcrumb trail based on the current page type and place in the pages structure.
		 *
		 * @since  1.0
		 * @access private
		 * @return void
		 */
		private function buildBreadcrumbs() {
			// On a page, we have to fetch each parent of the current page up to the root
			if ( is_page() ) {
				$this->setBreadcrumbItem(get_permalink($this->postData->ID),$this->postData->post_title, $this->postData->post_excerpt);
				while ( $this->postData->post_parent ) {
					$this->postData = get_post($this->postData->post_parent);
					$this->setBreadcrumbItem(get_permalink($this->postData->ID),$this->postData->post_title, $this->postData->post_excerpt);
				}
			}
			elseif ( is_single() || is_category() ) {
				if ( is_single() ) {
					$this->setBreadcrumbItem(get_permalink($this->postData->ID), $this->postData->post_title, $this->postData->post_excerpt);
				}
				
				// Fetch the post category
				$this->postCat = get_the_category($this->postData->ID);
				$this->postCat = $this->postCat[0];
				$this->setBreadcrumbItem(get_category_link($this->postCat->cat_ID), $this->postCat->cat_name, $this->postCat->category_description);
				
				// Then fetch each parent category
				while ( $this->postCat->parent ) {
					$this->postCat = get_category( $this->postCat->parent );
					$this->setBreadcrumbItem(get_category_link($this->postCat->cat_ID), $this->postCat->cat_name, $this->postCat->category_description);
				}
			}
			
			// We finally have to include the Home item
			$this->setBreadcrumbItem($this->siteUrl, __("Home", 'slkz-breadcrumbs' ), __("Home", 'slkz-breadcrumbs' ));
			
			// The array is complete but in a reverse order
			$this->breadcrumbs = array_reverse($this->breadcrumbs);
		}
		
		/**
		 * Displays the breadcrumb trail on the page.
		 *
		 * @since  1.0
		 * @access public
		 * @return void
		 */
		public function echoOutputData() {
			echo $this->outputData;
		}
	}
?>
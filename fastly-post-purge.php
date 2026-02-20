<?php
/**
 * Plugin Name: Fastly Post Purge
 * Plugin URI: https://ukings.ca
 * Description: Automatically purges the Fastly cache for a page URL when a post is saved.
 * Version: 1.0.1
 * Author: ChangeMakers
 * Author URI: https://ukings.ca
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fastly-post-purge
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Fastly Post Purge Plugin Class
 */
class Fastly_Post_Purge {

    /**
     * Constructor
     */
    public function __construct() {
        // Hook into post save action
        add_action('save_post', array($this, 'purge_post_on_save'), 10, 2);
    }

    /**
     * Purge post URL from Fastly cache when post is saved
     *
     * @param int $post_id The ID of the post being saved
     * @param WP_Post $post The post object
     * @return void
     */
    public function purge_post_on_save($post_id, $post) {
        // We need to purge key URLs: homepage, news, events, careers
        $always_purge_urls = array(
            home_url('/'),
            home_url('/news/'),
            home_url('/events/'),
            home_url('/calendar/'),
            home_url('/campus-community/employment/'),
        );

        // Don't purge autosaves or revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Only purge published posts
        if ($post->post_status !== 'publish') {
            return;
        }

        // Get the post URL
        $post_url = get_permalink($post_id);

        if (!$post_url) {
            return;
        }

        array_push($always_purge_urls, $post_url);

	// Ensure all always-purge URLs use the ukings.ca domain 
	// if they don't already
        $required_domain = 'ukings.ca';
        foreach ($always_purge_urls as $idx => $u) {
            if (strpos($u, $required_domain) === false) {
                $parts = @parse_url($u);
                if ($parts === false) {
                    continue;
                }

                $scheme = isset($parts['scheme']) ? $parts['scheme'] : 'https';
                $path = isset($parts['path']) ? $parts['path'] : '/';
                $query = isset($parts['query']) ? '?' . $parts['query'] : '';
                $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

                $always_purge_urls[$idx] = $scheme . '://' . $required_domain . $path . $query . $fragment;
            }
        }

        // Call the Fastly purge method
        foreach ($always_purge_urls as $url) {
            $this->purge_url($url);
        }
    }

    /**
     * Purge a URL from Fastly cache
     *
     * @param string $url The URL to purge
     * @return bool|WP_Error The result of the purge request
     */
    public function purge_url($url) {
        // Check if Purgely_Purge class exists
        if (!class_exists('Purgely_Purge')) {
            error_log('Fastly Post Purge: Purgely_Purge class not found. Fastly plugin may not be activated.');
            return false;
        }

        try {
            // Instantiate the purge request class
            $purge_request = new Purgely_Purge();

            // Call the public purge method with URL type
            $result = $purge_request->purge(Purgely_Purge::URL, $url);
	    
	    if ($result) {
                error_log('Fastly Post Purge: Successfully purged URL - ' . $url);
            } else {
                error_log('Fastly Post Purge: Failed to purge URL - ' . $url);
            }

            return $result;
        } catch (Exception $e) {
            error_log('Fastly Post Purge: Exception - ' . $e->getMessage());
            return false;
        }
    }
}

// Initialize the plugin
if (is_admin()) {
    new Fastly_Post_Purge();
}

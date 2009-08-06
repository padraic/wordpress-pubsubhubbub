<?php
/*
Plugin Name: WP Pubsubhubbub
Plugin URI: http://code.google.com/p/pubsubhubbub/
Description: Implements a Pubsubhubbub Real-Time Publisher informing Planet Earth
of your blog updates now, not later, with support for multiple Hubs and the most
recent emerging practices (even if not in the Specification yet).
Version: 1.0a
Author: Padraic Brady
Author URI: http://blog.astrumfutura.com
*/


/**
 * Set up a functioning path for loading the Pubsubhubbub library files
 * needed. Only those required are packaged, imported from latest svn/git HEAD.
 */
$path = dirname(__FILE__);
set_include_path(get_include_path() . PATH_SEPARATOR . $path . '/library');
require_once 'Zend/Pubsubhubbub/Publisher.php';

// All custom functions prefixed with "wpsh_" to avoid function name clashes

/**
 * Add an action hook to initiate Pubsubhubbub Publisher notifications
 * to all configured Hubs
 */
add_action('publish_post', 'wpsh_notify_hubs');

/**
 * Issue a notification to all utilised Hubs about the current update. Seems
 * silly but any Subscriber can subscribe to any feed URL, i.e. it could be
 * Atom 1.0 or RSS 2.0. RSS 1.0 and RDF feeds are omitted since these are
 * not currently addressed specifically in the Pubsubhubbub Specification.
 *
 * Subscribers should be encouraged to subscribe only the Atom 1.0 and RSS 2.0 feeds.
 */
function wpsh_notify_hubs($postId) {
    try {
        $publisher = new Zend_Pubsubhubbub_Publisher;
        $feeds = array_unique(array(
            get_bloginfo('rss2_url'), get_bloginfo('atom_url')
        ));
        $publisher->addTopicUrls($feeds);
        $publisher->addHubUrls(wpsh_get_hubs());
        $publisher->notifyAll();
        if (!$publisher->isSuccess()) {
            $errors = $publisher->getErrors();
            foreach ($errors as $error) {

            }
        }
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
    return $postId;
}

/**
 * Return the array of Hubs supported by this blog. If none are defined by
 * the user, we'll assume they are using the current Google reference hub.
 * This is a convenient default but the user should be encouraged to
 * deliberately select this, or other Hubs for clarity.
 */
function wpsh_get_hubs() {
    $hubs = get_option('hub_urls');
    if (!$hubs) {
        return array('http://pubsubhubbub.appspot.com');
    } else {
        return explode("\n", trim($hubs));
    }
}

/**
 * In order for Pubsubhubbub to operate, all feeds must contain a <link> tag
 * under the Atom 1.0 XML Namespace with a "rel" attribute value of "hub" and a
 * "href" attribute value indicating the Hub's endpoint URL. This <link> may be
 * repeated to indicate the blog notifies multiple Hubs of updates.
 * Subscribers may subscribe to one or more of these Hubs.
 *
 * Callback functions are declared after this list.
 */
add_action('atom_head', 'wpsh_add_atom_links');
add_action('rss_head', 'wpsh_add_rss_links');

function wpsh_add_atom_links($rss = false) {
    $namespace = '';
    if ($rss) {
        $namespace = 'atom:';
    }
    $hubs = get_hubs();
    foreach ($hubs as $url) {
        echo '<', $namespace,'link rel="hub" href="', $url, '" />';
    }
}

function wpsh_add_rss_links() {
    wpsh_add_atom_links(true);
}

/**
 * Create Administration Interface Hook and a function to write out
 * the necessary HTML.
 */
add_action('admin_menu', 'wpsh_include_options_page');

function wpsh_include_options_page() {
    add_options_page('WP Pubsubhubbub Settings', 'WP Pubsubhubbub', 8, __FILE__, 'wpsh_write_options_page');
}

function wpsh_write_options_page() {
    $hubs = implode("\n", wpsh_get_hubs());
    $submitText = __('Save Changes');
    $wpnonce = wp_nonce_field('update-options', null, null, false);
    $page = <<<OPTIONS
<div class="wrap">
<h2>Configure one or more Hub Endpoint URLs to receive update pings</h2>
<form method="post" action="options.php">
$wpnonce
<table class="form-table">
<tr valign="top">
<th scope="row">Hub Endpoint Urls</th>
<td>
<p>Add each Hub Endpoint Url to a new line:<br /></p>
<textarea name="hub_urls" cols="40%" rows="8">$hubs</textarea>
</td>
</tr>
</table>
<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="hub_urls" />
<p class="submit">
<input type="submit" class="button-primary" value="$submitText" />
</p>
</form>
</div>
OPTIONS;
    echo $page;
}

<?php
/*
Plugin Name: WP Pubsubhubbub
Plugin URI: http://code.google.com/p/pubsubhubbub/
Description: Implements a Pubsubhubbub Real-Time Publisher informing Planet Earth
of your blog updates now, not later.
Version: 1.0
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

/**
 * Issue a notification to all utilised Hubs about the current update. Seems
 * silly but any Subscriber can subscribe to any feed URL, i.e. it could be
 * Atom 1.0, RSS 2.0 or the RSS 1.0/RDF feeds. So we notify our Hubs of changes
 * to all of them. We could drop RSS 1.0/RDF since they should be rarely used.
 */
function notify_hub($postId) {
    $publisher = new Zend_Pubsubhubbub_Publisher;
    $feeds = array_unique(
        array(
            get_bloginfo('rss2_url'), get_bloginfo('atom_url'),
            get_bloginfo('rss_url'), get_bloginfo('rdf_url'),
        )
    );
    $publisher->addTopicUrls($feeds);
    $publisher->addHubUrls(get_hub_urls());
    $publisher->notifyAll();
    return $postid
}

function get_hub_urls() {
    $hubs = array();

    return $hubs;
}

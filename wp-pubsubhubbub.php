<?php
/*
Plugin Name: WP Pubsubhubbub
Plugin URI: http://github.com/padraic/wordpress-pubsubhubbub/
Description: Implements a Pubsubhubbub Real-Time Publisher informing Planet Earth of your blog updates now, not later, with support for multiple Hubs and the most recent emerging practices. Edit the Hubs in use on the <a href="./options-general.php?page=wp-pubsubhubbub/wp-pubsubhubbub">WP Pubsubhubbub settings page</a>
Version: 1.0
Author: Padraic Brady
Author Email: padraic.brady@yahoo.com
Author URI: http://www.survivethedeepend.com
*/

/**
Copyright (c) 2009, Padraic Brady
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.

    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.

    * Neither the name of Padraic Brady nor the names of its
      contributors may be used to endorse or promote products derived from this
      software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/**
 * Set up a functioning path for loading the Pubsubhubbub library files
 * needed. Only those required are packaged, imported from latest svn/git HEAD.
 */
define('WPPSH_LIBRARY', dirname(__FILE__) . '/library');
set_include_path(get_include_path() . PATH_SEPARATOR . WPPSH_LIBRARY);
require_once 'Zend/Pubsubhubbub/Publisher.php';

// All custom functions/options prefixed with "wppsh_" to avoid name clashes

/**
 * Add an action hook to initiate Pubsubhubbub Publisher notifications
 * to all configured Hubs
 */
add_action('publish_post', 'wppsh_notify_hubs');

/**
 * Issue a notification to all utilised Hubs about the current update. Seems
 * silly but any Subscriber can subscribe to any feed URL, i.e. it could be
 * Atom 1.0 or RSS 2.0. RSS 1.0 and RDF feeds are omitted since these are
 * not currently addressed specifically in the Pubsubhubbub Specification.
 *
 * Subscribers should be encouraged to subscribe only the Atom 1.0 and RSS 2.0 feeds.
 */
function wppsh_notify_hubs($postId) {
    try {
        $publisher = new Zend_Pubsubhubbub_Publisher;
        $feeds = array_unique(array(
            get_bloginfo('rss2_url'), get_bloginfo('atom_url')
        ));
        $publisher->addUpdatedTopicUrls($feeds);
        $publisher->addHubUrls(explode("\n", trim(wppsh_get_hubs())));
        $publisher->notifyAll();
        if (!$publisher->isSuccess()) {
            $errors = $publisher->getErrors();
            foreach ($errors as $error) {
                print_r($error['response']->getBody());
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
function wppsh_get_hubs() {
    $hub = get_option('wppsh_hub_urls');
    if (!$hub) {
        return 'http://pubsubhubbub.appspot.com';
    } else {
        return $hub;
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
add_action('atom_head', 'wppsh_add_atom_links');
add_action('rss2_head', 'wppsh_add_rss_links');

function wppsh_add_atom_links($rss = false) {
    $namespace = '';
    if ($rss) {
        $namespace = 'atom:';
    }
    $hubs = explode("\n", trim(wppsh_get_hubs()));
    $out = '';
    foreach ($hubs as $url) {
        $out .= '<' . $namespace . 'link rel="hub" href="' . trim($url) . '" />' . "\n\t";
    }
    echo $out;
}

function wppsh_add_rss_links() {
    wppsh_add_atom_links(true);
}

/**
 * Create Administration Interface Hook and a function to write out
 * the necessary HTML.
 */
add_action('admin_menu', 'wppsh_include_options_page');

function wppsh_include_options_page() {
    add_options_page('WP Pubsubhubbub Settings', 'WP Pubsubhubbub', 8, __FILE__, 'wppsh_write_options_page');
}

function wppsh_write_options_page() {
    include 'options.phtml';
}

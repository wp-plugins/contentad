<?php

/**
 * WordPress Widget Base
 *
 * A code base for creating widgets in WordPress
 *
 * @version 1.0.0
 * @author Micah Wood
 * @copyright Copyright (c) 2011 - Micah Wood
 * @license GPL 3 - http://www.gnu.org/licenses/gpl.txt
 */

class ContentAd_Widget extends WP_Widget {

    function ContentAd_Widget() {
        $this->WP_Widget(
			$id = false,
            $title = __('Content.ad Widget'),
            $widget_ops = array(
                'classname' => 'content-ad-widget',
                'description' => 'Displays ads that are set to display within a widget.'
            )
        );
    }

    function widget( $args, $instance ){

		if ( is_single() ) {

			// Begin widget wrapper
			echo $args['before_widget'];

        	// Display widget content to user
			echo ContentAd_API::get_ad_code();

			// End widget wrapper
			echo $args['after_widget'];

		}
    }

}
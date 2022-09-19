<?php

add_action('rest_api_init', 'at_rest_init_linetoday');
function at_rest_init_linetoday() {

    register_rest_route('wp/v2', '/feed-line-today/', array(
        'methods'   => 'GET',
        'callback'  => 'get_line_today_article'
    ));
	
}

function get_line_today_article() {
	
	header('Content-Type: text/xml; charset=utf-8');
	
	$args = array(
		'post_type' 		=> array('news'), // array('news', 'news-clip', 'programs')
		'orderby' 			=> 'date',
		'order'   			=> 'DESC',
		'posts_per_page'	=> '100',
		'tax_query' => array(
			array(
				'taxonomy' => 'news-category',
				'field'    => 'term_id',
				'terms'    => array( 4175,49,51,53,54,55,57,59,60,69,70,71,81 ), // array( 52, 3032 ),
				'operator' => 'IN', //'NOT IN',
			),
		),
		'meta_query' => array(
			array(
				'key' => 'showon-line-today',
				'value' => 'true'
				)
		),
	);

	$query = new WP_Query( $args );
	
	$datetime_gmt_0 		= date('Y-m-d H:i:s',strtotime('+0 hours',strtotime(date("Y-m-d H:i:s"))));
	$datetime_gmt_7 		= date('Y-m-d H:i:s',strtotime('+7 hours',strtotime(date("Y-m-d H:i:s"))));
	$current_dttm_plus_1sec = date('Y-m-d H:i:s',strtotime('+1 minutes',strtotime($datetime_gmt_0)));
	
	$strtotime_uuid 		= strtotime($datetime_gmt_0);
	$strtotime_time 		= strtotime($datetime_gmt_0);

	// STEP : RENDER ARTICLE
	echo '<?xml version="1.0" encoding="UTF-8" ?> 		
 		<articles>
			<UUID>Topnews'.$strtotime_uuid.'</UUID>
			<time>'.$strtotime_time.'000</time>';
	
	foreach( $query->posts as $key=>$value) {
		
		render_schema($value);
		
		// CHECK : post meta
//		check_post_meta($value->ID, 'showon-line-today');
		
	}
	echo '</articles>';
	

	
	wp_reset_query();
	
}

function check_post_meta($post_id, $post_meta_key){
	$rpmv = get_post_meta($post_id, $post_meta_key, true);
	echo $post_id; echo ',';
	echo $post_meta_key; echo ',';
	echo $rpmv; echo ',';
	echo ' | ';
}

function render_schema($post){
	header('Content-Type: text/xml; charset=utf-8');
	$post_feature_image 		= get_the_post_thumbnail_url($post->ID,'full');
	$post_feature_image_without_webp = explode("?x-image-process=style/webp", $post_feature_image);
	$post_category_terms 		= get_the_terms( $post->ID, 'news-category' );
    $post_category_term 		= array_shift( $post_category_terms );
	if($post_category_term) {
		$category = '
		<category>'.$post_category_term->name.'</category>';
	} else {
		$category = '';
	}
	
	$post_datetime 		= $post->post_date;
	$post_modify		= $post->post_modified;
	$startYmdtUnix 		= strtotime( date('Y-m-d H:i:s',strtotime('-7 hours',strtotime($post_datetime))) . PHP_EOL );//strtotime($post->post_date);
	$endYmdtUnix 		= strtotime( date('Y-m-d',strtotime('+30 days', $startYmdtUnix)) . PHP_EOL );
	$publishTimeUnix 	= strtotime( date('Y-m-d H:i:s',strtotime('-7 hours',strtotime($post_datetime))) . PHP_EOL ); //strtotime($post->post_date);
	$updateTimeUnix 	= strtotime( date('Y-m-d H:i:s',strtotime('-7 hours',strtotime($post_modify))) . PHP_EOL ); //strtotime($post->post_modified);
	
	$content1 = $post->post_content;
	$content2 = get_post_meta($post->ID, 'news-content-2', true);
	$content3 = get_post_meta($post->ID, 'news-content-3', true);
	
	$showon_line_today = get_post_meta($post->ID, 'showon-line-today', true);
	
	$content1_without_webp = str_replace(str_split('?x-image-process=style/webp'), '', $content1); //$content1;
	$content2_without_webp = str_replace(str_split('?x-image-process=style/webp'), '', $content2); //$content2;
	$content3_without_webp = str_replace(str_split('?x-image-process=style/webp'), '', $content3); //$content3;
	// preg_match_all('/(foo)(bar)(baz)/', 'foobarbaz', $matches, PREG_OFFSET_CAPTURE);
	// print_r($matches);
	
	$contents = $content1.$content2.$content3;
//	$contents = $content1_without_webp.$content2_without_webp.$content3_without_webp;

	echo '
			<article>
				<ID>'.$post->ID.'</ID>
				<nativeCountry>TH</nativeCountry>
				<language>th</language>
				<publishCountries>
					<country>TH</country>
				</publishCountries>
				<startYmdtUnix>'.$startYmdtUnix.'000</startYmdtUnix>
				<endYmdtUnix>'.$endYmdtUnix.'000</endYmdtUnix>'.$category.'
				<publishTimeUnix>'.$publishTimeUnix.'000</publishTimeUnix>
				<updateTimeUnix>'.$updateTimeUnix.'000</updateTimeUnix>
				<title>'.$post->post_title.'</title>
				<contents>
					<image>
						<title>'.$post->post_title.'</title>
						<url>'.$post_feature_image_without_webp[0].'</url>
					</image>
					<text>
						<content>
							<![CDATA[ 
							'.$contents.'
							]]>
						</content>
					</text>
				</contents>
				<author>TOP NEWS</author>
				<sourceUrl>https://www.topnews.co.th/news/'.$post->ID.'</sourceUrl>
			</article>
		';

}
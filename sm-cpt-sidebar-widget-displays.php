<?php
/*
Plugin Name: SM CPT Sidebar Widget Displays
Plugin URI: http://sethmatics.com/extend/
Description: Lists all pages in site in admin
Author: sethcarstens
Author URI: http://profiles.wordpress.org/sethmatics
Version: 1.0.2
Description:
Very simple, its plugin that will attempt to display Custom Post Types into the sidebar using widgets. Initially it will be for displaying text that can be styled, like a "testimonials" custom post type that you want to display random testimonials in the sidebar using there "excerpts". As it grows the plugin will handle output of custom fields, featured images, and will give additional default styling options (that can be disabled for customized websites).
*/

if (class_exists('WP_Widget')) : 
		class Display_CTPs_Widget extends WP_Widget {
				function Display_CTPs_Widget() {
						$widget_ops = array('classname' => 'widget_display_cpts', 'description' => 'Display an excerpt of any Custom Post Type in the sidebar. (ie good for showing ctps like testimonials)' );
						$this->WP_Widget('display_cpts', 'SM Display CPTs', $widget_ops);
				}
		 
				function widget($args, $instance) {
						extract($args, EXTR_SKIP);
						
						$qry_args = array( 
							'post_type' => $instance['cpt'], 
							'posts_per_page' => intval($instance['cpt_list_limit']),
							'orderby' => 'rand'
						);

						//advanced related slugs filter, see widget description for example
						//TODO: build query multiple taxonomies
						//http://plugins.svn.wordpress.org/query-multiple-taxonomies/tags/1.6.2/walkers.php
						//for now, we will use just the first taxonomy returned... sorry
						if(!empty($instance['cpt_slug_match_filter'])) {
							$related_slugs = $this->related_slugs($instance['cpt']);
							if($related_slugs) $qry_args = array_merge($qry_args, $related_slugs);
						}
						
						query_posts( $qry_args );

						//TODO: Add option to "remove widget" when empty 
						//instead of displaying unfiltered results as default
						if ( have_posts() ) :
							echo $before_widget;
							echo '<div id="'.$widget_id.'">';

							$title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
							if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };
							
							echo '<ul class="ctp-type-'.$instance['cpt'].'"">';
							while ( have_posts() ) : the_post();
								//custom addition for testimonials (which was the original purpose of this plugin)
								$meta1 = get_post_meta(get_the_ID(), 'testimonial-option-author-position', true);
								if(!empty($meta1)) $meta1 = ', '.$meta1;
								//display the CPT in a list (which is the typical widget loop HTML tag)
								echo '<li><span class="'.$instance['cpt'].'-excerpt">'.get_the_excerpt().'</span><span class="'.$instance['cpt'].'-author">- <a href="'.get_permalink().'">'.the_title( $before = '', $after = '', false ).$meta1.'</a></span></li>';
							endwhile;
							echo '</ul>';
							echo '</div>';
							echo $after_widget;
						else :
							echo $before_widget;
							echo '<div id="'.$widget_id.'">';
							$title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
							if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };
							
							echo '<ul class="ctp-type-'.$instance['cpt'].'"">';
								echo '<li><span class="'.$instance['cpt'].'-excerpt">Sorry, no results found.</span></li>';
							echo '</ul>';
							echo '</div>';
							echo $after_widget;
						endif;
						//reset back to the main query in case anything after this widget needs to use it
						wp_reset_query();
				}
		 
				function update($new_instance, $old_instance) {
						$instance = $old_instance;
						$instance['title'] = esc_attr($new_instance['title']);
						$instance['cpt'] = esc_attr($new_instance['cpt']);
						if(is_numeric($new_instance['cpt_list_limit'])) 
							$instance['cpt_list_limit'] = intval($new_instance['cpt_list_limit']);
						else 
							$instance['cpt_list_limit'] = 3;
						$instance['cpt_slug_match_filter'] = esc_attr($new_instance['cpt_slug_match_filter']);
						return $instance;
				}
				
				function form($instance) {
						//escape all used params first, then its safe to use them anywhere
						$title = esc_attr($instance['title']);
						$cpt = esc_attr($instance['cpt']);
						$cpt_list_limit = esc_attr($instance['cpt_list_limit']);
						$cpt_slug_match_filter = esc_attr($instance['cpt_slug_match_filter']);
						echo '  <p>
											<label for="'.$this->get_field_id('title').'">Title: 
													<input class="widefat" 
															id="'.$this->get_field_id('title').'" 
															name="'.$this->get_field_name('title').'" 
															type="text" value="'.$title.'" />'.
											'</label>
										</p>';
						echo '	<p>
											<label for="'.$this->get_field_id('cpt').'">Choose Content Type:
												<select class="widefat" 
														id="'.$this->get_field_id('cpt').'" 
														name="'.$this->get_field_name('cpt').'">';
														$post_types=get_post_types('','objects');
														foreach ($post_types as $post_type ) {
															$selected = '';
															if($post_type->name === $cpt) $selected = 'selected="selected"';
														  echo '<option value="'.$post_type->name.'" '.$selected.'>'. $post_type->label. '</option>';
														}
						echo '			
												</select>	
											</label>
											<span style="color: #666;" class="description">**The excerpt is displayed, please make sure this content type supports excerpts.</span>
										</p>';
						echo '  <p>
											<label for="'.$this->get_field_id('cpt_list_limit').'">Limit: 
													<input class="widefat" 
															id="'.$this->get_field_id('cpt_list_limit').'" 
															name="'.$this->get_field_name('cpt_list_limit').'" 
															type="text" value="'.$cpt_list_limit.'" />'.
											'</label>
											<span style="color: #666;" class="description">Number of items that will be displayed, enter zero to display all.</span>
										</p>';
						if($cpt_slug_match_filter == true) $checked = 'checked="checked"';
						echo '  <p>
												<input class="" 
														id="'.$this->get_field_id('cpt_slug_match_filter').'" 
														name="'.$this->get_field_name('cpt_slug_match_filter').'" 
														type="checkbox" value="true" '.$checked.' /> '.
											'<label for="'.$this->get_field_id('cpt_slug_match_filter').'">Enable Advanced Slug Filter (beta)</label>
											<span style="color: #666;" class="description"><a href="#" style="color: #666;" onClick="jQuery(this).next().toggle();return false;">Click for Example</a><span style="display:none;">:<br />First you create and connect a category taxonomy to your "post type". Then create a category called "services". On the page with a matching slug (url matches category) ie. domain.com/services/, it will filter the results to display the matching category(s). Note that it will match multiple categories if there are multiple page slugs (and matching category slugs) in the URL.<span></span>
										</p>';

						/*
						TODO: Setup Order By types and allow option to be dynamic
						$order_types = get_orderby_options();
						echo '	<p>
											<label for="'.$this->get_field_id('cpt_orderby').'">Order by:
												<select class="widefat" 
														id="'.$this->get_field_id('cpt_orderby').'" 
														name="'.$this->get_field_name('cpt_orderby').'">';
														foreach ($order_types as $order_type ) {
															$selected = '';
															if($order_types->name === $cpt_orderby) $selected = 'selected="selected"';
														  echo '<option value="'.$order_types->name.'" '.$selected.'>'. $order_types->label. '</option>';
														}
						echo '			
												</select>	
											</label>
										</p>';
						echo '	<p>
											<label for="'.$this->get_field_id('cpt_order').'">Order in:
												<select class="widefat" 
														id="'.$this->get_field_id('cpt_order').'" 
														name="'.$this->get_field_name('cpt_order').'">';
															$selected = '';
															if($cpt_order === 'DESC') $selected = 'selected="selected"';
														  echo '<option value="ASC">Ascending</option>';
														  echo '<option value="DESC" '.$selected.'>Descending</option>';
						echo '			
												</select>	
											</label>
										</p>';
						*/
				}

				function related_slugs($tax = ''){
					global $wp;
					$related_list = array();
					//grab page slugs and move values to array key position
					$slugs = explode('/', $wp->request);
					$slugs = array_flip($slugs);
					
					//get all the taxonomies associated with the object (ie custom post type)
					$taxonomies = get_object_taxonomies($tax, 'objects');
					
					foreach($taxonomies as $t => $v) { 
						//grab categories list, filter out empty categories
						$terms = get_terms( $t );
					
						//match terms and categories (note empty terms are not considered)
						foreach($terms as $tval){
							if(isset($slugs[$tval->slug])) $related_list[$t][] = $tval->slug;
						}
					}
					//convert to csv's
					foreach($related_list as $key => $value){
						$related_list[$key] = $this->array_2_csv($related_list[$key]);
					}
					//return related lists for taxonomy filters used on query_posts function
					//if(STAGING) {echo '<pre>';var_export($related_list);echo '</pre>';}
					if(empty($related_list)) return false;
					else return $related_list;
				}

				function array_2_csv($array) {
					$csv = array();
					foreach ($array as $item) {
					    if (is_array($item)) {
					        $csv[] = array_2_csv($item);
					    } else {
					        $csv[] = $item;
					    }
					}
					return implode(',', $csv);
				}

				function get_orderby_options() {
					//for more options see http://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters
					$o->label = 'Custom Page Order'; 				$o->name = 'menu_order';
					$o->label = 'Date'; 										$o->name = 'date';
					$o->label = 'Modified Date';						$o->name = 'modified';
					$o->label = 'Random'; 									$o->name = 'rand';
					$o->label = 'Title';	 									$o->name = 'title';
					return $o;
				}
		}
endif;

add_action('widgets_init', 'register_Display_CTPs_Widget');
function register_Display_CTPs_Widget() {
		register_widget('Display_CTPs_Widget');
}
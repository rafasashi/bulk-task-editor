=== Bulk Task Editor ===
Author: Rafasashi
Author URI: https://code.recuweb.com
Donate link: https://code.recuweb.com/feature/bulk-edit/
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Requires WP: 6.4.3
Requires PHP: 7.4.28
Stable Tag: 1.0.1.40
Tested up to: 6.4.3

Bulk Task Editor (BTE) is a WordPress plugin designed to simplify and accelerate your content management workflow.

== Features ==

* **BULK ACTIONS** – Schedule and apply custom actions to edit multiple items at once without overloading your servers.
* **AUTOMATED** – Run automated workflows in the browser via JavaScript or schedule them in the background via CRON jobs.
* **SEARCH FILTERS** – Customize your search criteria using an extensive selection of filters to quickly find the items you need to manage.
* **PREVIEW ITEMS** – Display targeted items in a dynamic dialog box as search filters are updated.
* **USER-FRIENDLY** – Navigate and operate the plugin with ease thanks to its intuitive and user-friendly interface.
* **CUSTOMIZABLE** – Register and hook into the bulk task filter to incorporate custom actions tailored to your specific needs.

[Watch the Video Preview](https://www.youtube.com/embed/e0LPQjWz-v0)

== Built-in Tasks ==

Bulk Task Editor offers a variety of built-in tasks to kick-start your bulk editing process. If you need a specific action that’s not included, feel free to reach out via the [contact form](https://code.recuweb.com/contact-us/) or the [dedicated support forum](https://code.recuweb.com/support/forum/wordpress-plugins/bulk-task-editor/).

Here is an overview of the default tasks you can perform:

=== Post Types (Posts, Pages, etc.) ===
* Bulk edit post type
* Bulk edit post status
* Bulk edit author ID
* Bulk edit parent post (for hierarchical post types)
* Bulk edit post format (whenever supported)
* Bulk add, replace, or remove terms (for registered taxonomies)
* Bulk duplicate posts (single or multisite via table prefix switching)
* Bulk edit meta values
* Bulk remove meta
* Bulk rename meta
* Bulk delete posts

=== Taxonomies (Categories, Tags, etc.) ===
* Bulk edit parent term (for hierarchical taxonomies)
* Bulk edit term meta values
* Bulk remove term meta
* Bulk rename term meta
* Bulk delete terms

=== Users ===
* [Bulk add, replace, or remove user roles](https://code.recuweb.com/support/discussion/how-to-bulk-edit-user-roles/)
* Bulk edit user meta values
* Bulk remove user meta
* Bulk rename user meta
* Bulk delete users

== Search Filters ==

Quickly find items to edit by customizing your search with a wide range of filters, such as:
* Filter by search keywords
* Filter by status and format
* Filter by list of comma-separated IDs
* Filter by type (post type, taxonomy, user, etc.)
* Filter by multiple date ranges and time calculator (before, after)
* Filter by multiple authors with dynamic selector (included or excluded)
* Filter by multiple terms with dynamic selector (included or excluded)
* Filter by multiple metadata with operators (e.g., =, >, <, not exists, regex)
* And many more…

== Advanced Custom Tasks ==

Use the following template to implement a custom post type task.

=== Register the Task ===

```php

add_action( 'rewbe_post_type_actions', function($actions,$post_type){

     if( $post_type == 'your-custom-post-type' ){

          $actions[] = array(

               'label'  => 'Name of your task',
               'id'     => 'bulk_task_name',
               'fields' => array(
                    array(
                         'name'    => 'var_1',
                         'type'    => 'select',
                         'options' => array(
                              'value_1' => 'Option 1',
                              'value_2' => 'Option 2',
                              'value_3' => 'Option 3',
                         ),                    
                    ),
                    array(
                         'name' => 'var_2',
                         'type' => 'text',
                    ),
               ),
          );
     }
     
     return $actions;
     
},10,2);

```

=== Add the Callback Function ===

```php

add_action('rewbe_do_post_{bulk_task_name}',function($post,$args){
     
     if( !empty($args['var_1']) && !empty($args['var_2']) ){
          
          $var_1 = sanitize_title($args['var_1']);
          $var_2 = sanitize_text_field($args['var_2']);
          
          // your logic here
     }
     
     return $post;
     
},10,2);

```

== Learn More ==

For more information on implementing custom tasks, check out the following resources:

* [How to implement a bulk post task using hooks](https://code.recuweb.com/support/discussion/how-to-implement-a-custom-post-task-using-hooks/)
* [How to implement a bulk taxonomy task using hooks](https://code.recuweb.com/support/discussion/how-to-implement-a-custom-taxonomy-task-using-hooks/)
* [How to implement a bulk user task using hooks](https://code.recuweb.com/support/discussion/how-to-implement-a-custom-user-task-using-hooks/)

Bulk Task Editor can benefit a wide range of users. Here are some examples:

* **Content Managers**: Quickly update and manage large volumes of content with minimal effort.
* **SEO Specialists**: Efficiently apply SEO improvements across multiple posts or pages.
* **E-commerce Stores**: Bulk update product information, categories, and tags with ease.
* **Membership Sites**: Manage user roles and permissions in bulk.
* **Bloggers**: Schedule and publish multiple posts simultaneously to keep your blog updated.
* **Event Planners**: Update and manage event details, categories, and attendees effortlessly.
* **Educational Institutions**: Organize and update course materials, student information, and instructor details in bulk.
* **Real Estate Agents**: Manage property listings, status updates, and categories without hassle.
* **Marketing Teams**: Implement marketing strategies by bulk updating content, tags, and categories.
* **Nonprofits**: Easily manage donor information, event details, and volunteer roles.
* **News Websites**: Quickly edit and publish multiple news articles to stay current with breaking news.
* **Social Media Managers**: Update and manage social media content, schedules, and user roles effectively.
* **Custom Post Types**: Handle any custom post types specific to your website, ensuring efficient bulk management.
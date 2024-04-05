<?php
/**
 * Plugin Name: Book Plugin
 * Description: A plugin to manage books in WordPress.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class BookPlugin
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_book_menu'));
        add_shortcode('display_books', array($this, 'display_books_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_display_book_details', array($this, 'display_book_details'));
        add_action('wp_ajax_nopriv_display_book_details', array($this, 'display_book_details'));




        global $wpdb;
        $table_name = $wpdb->prefix . 'books';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        author varchar(255) NOT NULL,
        description text,
        about_author text,
        audio_link varchar(255),
        ebook_link varchar(255),
        paperback_link varchar(255),
        image_url varchar(255),
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);


    }

    public function add_book_menu()
    {
        add_menu_page('Book', 'Book', 'manage_options', 'book-plugin', array($this, 'book_menu_callback'));
    }

    public function book_menu_callback()
    {

        if (isset($_POST['submit'])) {
            // Handle form submission
            $title = sanitize_text_field($_POST['title']);
            $author = sanitize_text_field($_POST['author']);
            $description = sanitize_text_field($_POST['description']);
            $about_author = sanitize_text_field($_POST['about_author']);
            $audio_link = esc_url_raw($_POST['audio_link']);
            $ebook_link = esc_url_raw($_POST['ebook_link']);
            $paperback_link = esc_url_raw($_POST['paperback_link']);
            
            // Handle file upload
            $image_url = '';
            if (!empty($_FILES['image']['name'])) {
                $uploaded_image = wp_handle_upload($_FILES['image'], array('test_form' => false));
                if (!isset($uploaded_image['error'])) {
                    $image_url = $uploaded_image['url'];
                }
            }
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'books';
            $insert_data = array(
                'title' => $title,
                'author' => $author,
                'description' => $description,
                'about_author' => $about_author,
                'audio_link' => $audio_link,
                'ebook_link' => $ebook_link,
                'paperback_link' => $paperback_link,
                'image_url' => $image_url
            );
            $insert_format = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');
            $insert_result = $wpdb->insert($table_name, $insert_data, $insert_format);
            
            if ($insert_result) {
                echo '<div class="updated"><p>Book added successfully!</p></div>';
            } else {
                echo '<div class="error"><p>Error adding book. Please try again.</p></div>';
            }
        }


        ?>
        <div class="wrap">
            <h2>Add New Book</h2>
            <form id="book-form" method="post" enctype="multipart/form-data">
                <label for="title">Title:</label><br>
                <input type="text" id="title" name="title"><br>
                <label for="author">Author:</label><br>
                <input type="text" id="author" name="author"><br>
                <label for="description">Description:</label><br>
                <input type="text" id="description" name="description"><br>
                <label for="about_author">About Author:</label><br>
                <input type="text" id="about_author" name="about_author"><br>
                <label for="audio_link">Audio Book Link:</label><br>
                <input type="text" id="audio_link" name="audio_link"><br>
                <label for="ebook_link">eBook Link:</label><br>
                <input type="text" id="ebook_link" name="ebook_link"><br>
                <label for="paperback_link">Paperback Link:</label><br>
                <input type="text" id="paperback_link" name="paperback_link"><br>
                <label for="image">Image:</label><br>
                <input type="file" id="image" name="image"><br><br>
                <input type="submit" name="submit" value="Submit">
            </form>
        </div>
        <?php
    }

    public function display_books_shortcode()
    {
        global $wpdb;
        $books = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}books");

        ob_start();
        ?>
        <div class="book-grid">
            <?php foreach ($books as $book) : ?>
                <div class="book-item">
                    <img src="<?php echo $book->image_url; ?>" alt="<?php echo $book->title; ?>">
                    <h3><?php echo $book->title; ?></h3>
                    <p><?php echo $book->author; ?></p>
                    <select class="buy-option" data-ebook="<?php echo $book->ebook_link; ?>" data-audio="<?php echo $book->audio_link; ?>" data-paperback="<?php echo $book->paperback_link; ?>">
                        <option value="">Buy</option>
                        <option value="ebook">eBook</option>
                        <option value="audio">Audio Book</option>
                        <option value="paperback">Paperback</option>
                    </select>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script('book-plugin-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), null, true);
        wp_localize_script('book-plugin-script', 'book_plugin_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function display_book_details()
    {
        $book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
        if ($book_id) {
            global $wpdb;
            $book = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}books WHERE id = %d", $book_id));

            $response = array(
                'title' => $book->title,
                'description' => $book->description,
                'about_author' => $book->about_author
            );

            wp_send_json($response);
        }
        wp_die();
    }
}

new BookPlugin();

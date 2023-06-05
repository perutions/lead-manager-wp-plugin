<?php
/**
 * Plugin Name: My Lead Manager
 * Plugin URI:  https://perutions.com/wordpress-plugins/my-lead-manager/
 * Description: Manage all your business leads here and the status of the leads.
 * Version:     1.0.0
 * Author:      Perutions Infini
 * Author URI:  https://perutions.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: my-lead-manager
 */
 
 function my_custom_message() {
    echo '';
}
add_action('wp_footer', 'my_custom_message');

function my_plugin_admin_enqueue_styles() {
    wp_enqueue_style( 'my-plugin-admin-styles', plugins_url( 'css/styles.css', __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'my_plugin_admin_enqueue_styles' );

wp_register_style('prefix_bootstrap', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css');
wp_enqueue_style('prefix_bootstrap');

// Register a function to run during plugin activation
register_activation_hook( __FILE__, 'my_lm_plugin_create_table' );

// Create the custom table during plugin activation
function my_lm_plugin_create_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'my_lead_manager'; // Set the table name with the WordPress prefix
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(100),
        company VARCHAR(255),
        source VARCHAR(255),
        remarks TEXT,
        status ENUM('new', 'contacted', 'converted', 'lost') DEFAULT 'new',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// Add main Menu Lead Management
function my_lead_manager_menu_page() {
    add_menu_page(
        'Lead Management',         // Page title
        'Lead Manager',         // Menu title
        'manage_options',    // Capability required to access the menu page
        'lm-lead-report',    // Menu slug (should be unique)
        'my_lead_manager',    // Callback function to render the menu page
        'dashicons-admin-generic',   // Icon URL or Dashicons class
        25                    // Position of the menu item in the admin menu
    );
}

// Hook the menu creation function to show Main Menu Lead Management
add_action('admin_menu', 'my_lead_manager_menu_page');

// Add submenu Add new lead  under main menu Lead Management
function my_plugin_submenu_page() {
    add_submenu_page(
        'lm-lead-report',   // Existing menu slug (parent menu)
        'Add New Lead',         // Page title
        'Add New Lead',              // Menu title
        'manage_options',       // Capability required to access the submenu page
        'lm-new-lead',    // Menu slug (should be unique)
        'my_plugin_submenu_page_callback'   // Callback function to render the submenu page
    );
}

// Hook the submenu creation function to show submenu Add new Lead
add_action('admin_menu', 'my_plugin_submenu_page');

// Adding Edit URL without showing in Menu
function my_plugin_submenu_page_edit() {
    add_submenu_page(
        null,   // Existing menu slug (parent menu)
        'Edit Lead',         // Page title
        'Edit',              // Menu title
        'manage_options',       // Capability required to access the submenu page
        'lm-edit-lead',    // Menu slug (should be unique)
        'my_plugin_submenu_page_callback_edit'   // Callback function to render the submenu page
    );
}

// Hook the submenu creation function for Edit URL
add_action('admin_menu', 'my_plugin_submenu_page_edit');

// Business Lead Report Section
function my_lead_manager() {

    global $wpdb;
    

    $table_name = $wpdb->prefix . 'my_lead_manager'; // Replace 'my_table' with the actual table name

    // Query the database table to retrieve data
    $results = $wpdb->get_results( "SELECT * FROM $table_name" );
    
    if(isset($_GET['status'])) {
        if($_GET['status']=='success') {
            echo '<div class="entry-status success">Business Lead saved successfully</div>';
        } 
        if($_GET['status']=='failed') {
            echo '<div class="entry-status failed">Something went wrong while adding your Business Lead. Please try again.</div>';
        }
    }
    
    echo '<div class="lm-page-head">Manage your Business Leads</div>';
    
    echo '<a class="lm-add" href="'.admin_url( 'admin.php?page=lm-new-lead').'">Add New Lead</a>';
    
    if ( $results ) {
        echo '<table class="lm-table">';
        echo '<thead><tr><th>Name</th><th>Company</th><th>Email</th><th>Phone</th><th>Source</th><th>Status</th><th>Remarks</th><th>Action</th></tr></thead>';
        echo '<tbody>';
    
        foreach ( $results as $row ) {
            $id = $row->id;

            echo '<tr>';
            echo '<td>' . $row->name . '</td>';
            echo '<td>' . $row->company . '</td>';
            echo '<td>' . $row->email . '</td>';
            echo '<td>' . $row->phone . '</td>';
            echo '<td>' . $row->source . '</td>';
            echo '<td>' . $row->status . '</td>';
            echo '<td>' . $row->remarks . '</td>';
            echo '<td><a href="'.admin_url( 'admin.php?page=lm-edit-lead&id='.$row->id).'" class="editLM" data-id="'. $row->id.'">Edit</a><a class="deleteLM" href="javascript:void(0);" data-id="'. $row->id.'">Delete</a></td>';
            echo '</tr>';
        }
    
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<div class="lm-nodata">No data found.</div>';
    }
    
    echo '<div class="perutions">Thank you for using this plugin. From <a href="https://www.perutions.com" target="_blank">Perutions Infini</a></div>';
    
    ?>
    
        <script>
            jQuery(document).ready(function() {

                jQuery('.lm-table').DataTable();
                
                jQuery(document).on("click", ".deleteLM", function(event) {
                    
                    action='lm_lead_actiondelete';
                    id=jQuery(this).attr("data-id");
                    
                    if(confirm("Sure you want to delete this Business Lead?")) {

                        jQuery.ajax({
                          url: '<?php echo site_url(); ?>/wp-admin/admin-ajax.php',
                          type: 'POST',
                          data: 'id='+id+'&action='+action,
                          cache: false,
                          beforeSend: function(){
                            jQuery('.ajloader').show();
                          },
                           error: function() {
                              alert('Something is wrong');
                           },
                           success: function(data) {
                                alert(data);
                                location.reload();
                           }
                        });
                    
                    }

                });

            });
        </script>
    <?php
}

add_action( 'wp_ajax_nopriv_lm_lead_actiondelete', 'lm_lead_actiondelete' );
add_action( 'wp_ajax_lm_lead_actiondelete', 'lm_lead_actiondelete' );

// Function to delete a business Lead
function lm_lead_actiondelete() {
    global $wpdb;
    $id=$_POST['id'];
    $table_name = $wpdb->prefix . 'my_lead_manager';
    
    $where_condition = array(
        'id' => $id,
    );
    
    // Delete the row from the table
    $wpdb->delete( $table_name, $where_condition );

    if ( false !== $wpdb->rows_affected ) {

        echo 'Business Lead Deleted successfully.';
    } else {

        echo 'Something went wrong. Please try again.';
    }
    
    die();
}

// New Business Lead - Form
function my_plugin_submenu_page_callback() {

    ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <div class="post-body">
            <div class="container">
                <div class="lm-page-head">Add new Business Lead</div>
                <?php echo '<a class="lm-add" href="'.admin_url( 'admin.php?page=lm-lead-report').'">View all Leads</a>'; ?>
                <div class="row lmform">
                    
                    <div class="col-md-6">
                        <label>Company/Business Name</label>
                        <input type="text" name="company" size="30" value="" id="company" placeholder="Enter Company/Business Name" class="form-control">
                    </div>
                    
                    <div class="col-md-6">
                        <label>Client/Contact Person's Name</label>
                        <input type="text" name="name" size="30" value="" id="name" placeholder="Enter Client/Contact Person's Name.." class="form-control">
                    </div>
                    
                    <div class="col-md-6">
                        <label>Email Id</label>
                        <input type="text" name="email" size="30" value="" id="email" placeholder="Enter Client's Email Id.." class="form-control">
                    </div>
                    
                    <div class="col-md-6">
                        <label>Phone Number</label>
                        <input type="text" name="phone" size="30" value="" id="phone" placeholder="Enter Client's Phone Numbers.." class="form-control">
                    </div>
                    
                    <div class="col-md-6">
                        <label>Lead Source</label>
                        <input type="text" name="source" size="30" value="" id="source" placeholder="Enter the source where you got the lead from.." class="form-control">
                    </div>
                    
                    <div class="col-md-6">
                        <label>Lead Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">-- Select Lead's Current Status --</option>
                            <option value="new">New Lead</option>
                            <option value="contacted">Contacted</option>
                            <option value="converted">Converted</option>
                            <option value="lost">Lost</option>
                        </select>
                    </div>
                    
                    <div class="col-md-10">
                        <label>Remarks</label>
                        <textarea name="remarks" size="30" value="" id="remarks" placeholder="Enter any remarks about the lead.." class="form-control"></textarea>
                    </div>
                    
                    <div class="col-md-12">
                        <input type="hidden" name="action" value="my_lead_manager_process_form">
                        <!-- Add your form fields here -->
                        <input type="submit" value="Save Lead">
                    </div>
                </div>
            </div>
        </div>
        
        
    </form>
    <?php
}

add_action( 'init', 'my_plugin_init' );

//Function to process data from business lead add Form
function my_lead_manager_process_form() {

    // Retrieve form field values using $_POST superglobal array

    $company = sanitize_text_field( $_POST['company'] );
    $name = sanitize_text_field( $_POST['name'] );
    $email = sanitize_text_field( $_POST['email'] );
    $phone = sanitize_text_field( $_POST['phone'] );
    $source = sanitize_text_field( $_POST['source'] );
    $status = sanitize_text_field( $_POST['status'] );
    $remarks = sanitize_text_field( $_POST['remarks'] );

    global $wpdb;
    $table_name = $wpdb->prefix . 'my_lead_manager';
    
    // Prepare data for insertion
    $data = array(
        'company' => $company,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'source' => $source,
        'status' => $status,
        'remarks' => $remarks,
    );
    
    // Insert data into the table
    $wpdb->insert( $table_name, $data );
    
    if ( $wpdb->insert_id ) {
        $permalink = admin_url( 'admin.php?page=lm-lead-report&status=success');
    } else {
        $permalink = admin_url( 'admin.php?page=lm-lead-report&status=failed');
    }

    wp_redirect( $permalink ); // Redirect to the current page
    exit;
}

add_action( 'init', 'my_plugin_init' );

function my_plugin_init() {
    add_shortcode( 'my_plugin_form', 'my_plugin_submenu_page_callback' );
    add_action( 'admin_post_my_lead_manager_process_form', 'my_lead_manager_process_form' );
}

// Business Lead Edit form
function my_plugin_submenu_page_callback_edit() {
    $id = sanitize_text_field( $_GET['id'] );

    global $wpdb;
    $table_name = $wpdb->prefix . 'my_lead_manager';

    $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );
    
    if($row) {
    ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <div class="post-body">
            <div class="container">
                <div class="lm-page-head">Edit Business Lead</div>
                <?php echo '<a class="lm-add" href="'.admin_url( 'admin.php?page=lm-lead-report').'">View all Leads</a>'; ?>
                <div class="row lmform">
                    
                    <div class="col-md-6">
                        <label>Company/Business Name</label>
                        <input type="text" name="company" size="30" id="company" placeholder="Enter Company/Business Name" class="form-control" value="<?php echo $row->company; ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label>Client/Contact Person's Name</label>
                        <input type="text" name="name" size="30"  value="<?php echo $row->name; ?>" id="name" placeholder="Enter Client/Contact Person's Name.." class="form-control">
                    </div>
                    
                    <div class="col-md-6">
                        <label>Email Id</label>
                        <input type="text" name="email" size="30"  value="<?php echo $row->email; ?>" id="email" placeholder="Enter Client's Email Id.." class="form-control">
                    </div>
                    
                    <div class="col-md-6">
                        <label>Phone Number</label>
                        <input type="text" name="phone" size="30"  value="<?php echo $row->phone; ?>" id="phone" placeholder="Enter Client's Phone Numbers.." class="form-control">
                    </div>
                    
                    <div class="col-md-6">
                        <label>Lead Source</label>
                        <input type="text" name="source" size="30"  value="<?php echo $row->source; ?>" id="source" placeholder="Enter the source where you got the lead from.." class="form-control">
                    </div>
                    
                    <div class="col-md-6">
                        <label>Lead Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">-- Select Lead's Current Status --</option>
                            <option value="new" <?php if($row->status=='new') { echo "selected"; } ?>>New Lead</option>
                            <option value="contacted" <?php if($row->status=='contacted') { echo "selected"; } ?>>Contacted</option>
                            <option value="converted" <?php if($row->status=='converted') { echo "selected"; } ?>>Converted</option>
                            <option value="lost" <?php if($row->status=='lost') { echo "selected"; } ?>>Lost</option>
                        </select>
                    </div>
                    
                    <div class="col-md-10">
                        <label>Remarks</label>
                        <textarea name="remarks" size="30" id="remarks" placeholder="Enter any remarks about the lead.." class="form-control"><?php echo $row->remarks; ?></textarea>
                    </div>
                    
                    <div class="col-md-12">
                        <input type="hidden" name="action" value="my_lead_manager_process_edit_form">
                        <input type="hidden" name="id" value="<?php echo $row->id; ?>">
                        <!-- Add your form fields here -->
                        <input type="submit" value="Update Lead">
                    </div>
                </div>
            </div>
        </div>
        
        
    </form>
    <?php
    }
    ?>
    <script>
    jQuery(document).ready(function($) {

        $('a[href="admin.php?page=lm-lead-report"]').addClass('current wp-has-current-submenu wp-menu-open');

    });
    </script>
    <?php
}

//add_action( 'init', 'my_edit_init' );

// Function to process data from business lead edit form
function my_lead_manager_process_edit_form() {

    // Retrieve form field values using $_POST superglobal array

    $company = sanitize_text_field( $_POST['company'] );
    $name = sanitize_text_field( $_POST['name'] );
    $email = sanitize_text_field( $_POST['email'] );
    $phone = sanitize_text_field( $_POST['phone'] );
    $source = sanitize_text_field( $_POST['source'] );
    $status = sanitize_text_field( $_POST['status'] );
    $remarks = sanitize_text_field( $_POST['remarks'] );
    $id = sanitize_text_field( $_POST['id'] );


    global $wpdb;
    $table_name = $wpdb->prefix . 'my_lead_manager';
    
    // Prepare data for insertion
    $data = array(
        'company' => $company,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'source' => $source,
        'status' => $status,
        'remarks' => $remarks,
    );
    
    $where_condition = array(
        'id' => $id, // Replace 'id' with the actual column name for the ID
    );
    
    // Insert data into the table
    $wpdb->update( $table_name, $data, $where_condition );
    
    if ( false !== $wpdb->rows_affected ) {
        $permalink = admin_url( 'admin.php?page=lm-lead-report&status=success');
    } else {
        $permalink = admin_url( 'admin.php?page=lm-lead-report&status=failed');
    }

    wp_redirect( $permalink ); // Redirect to the current page
    exit;
}

add_action( 'init', 'my_plugin_edit_init' );

function my_plugin_edit_init() {
    add_shortcode( 'my_plugin_edit_form', 'my_plugin_submenu_page_callback_edit' );
    add_action( 'admin_post_my_lead_manager_process_edit_form', 'my_lead_manager_process_edit_form' );
}


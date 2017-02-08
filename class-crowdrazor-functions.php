<?php
require 'mailchimp/src/MailChimp.php';
require 'mailchimp/src/Batch.php';
require 'mailchimp/src/Webhook.php';
require_once('defuse-crypto.phar');
h

use \DrewM\MailChimp\MailChimp;

	class Razor_Funding_Functions
	{
		private $wp_db;
		private $test_secret_key;
		private $test_publishable_key;
		private $secret_key;
		private $publishable_key;
		private $prod_client_id;
		private $dev_client_id;
		private $stripe_test_mode;
		private $ach_payment_accept;

		public function __construct() {
		 	global $wpdb;
		 	$this->wp_db = $wpdb;
			
			if(get_option('rzr_stripe_test_mode')=="on")
				{
		 			if(get_option('rzr_stripe_test_secret_key')!=FALSE){
		 				$key = \Defuse\Crypto\Key::loadFromAsciiSafeString(ENCRYPTO);
		 				$this->secret_key = \Defuse\Crypto\Crypto::decrypt( get_option('rzr_stripe_test_secret_key'), $key );
		 			}
		 			$this->publishable_key = get_option('rzr_stripe_test_publishable_key');
		 			$this->client_id = get_option('rzr_stripe_development_client_id');
		 			wp_localize_script('../public/js/crowdrazor-public.js','rzr_pk',$this->publishable_key);
		 		} 
		 		else {
		 			if(get_option('rzr_stripe_live_secret_key')!=FALSE){
		 				$key = \Defuse\Crypto\Key::loadFromAsciiSafeString(ENCRYPTO);
		 				$this->secret_key = \Defuse\Crypto\Crypto::decrypt( get_option('rzr_stripe_live_secret_key'), $key );
		 			}
		 			$this->publishable_key = get_option('rzr_stripe_live_publishable_key');
		 			$this->client_id = get_option('rzr_stripe_production_client_id');
		 			wp_localize_script('../public/js/crowdrazor-public.js','rzr_pk',$this->publishable_key);
		 		}
		 	$this->ach_payment_accept = get_option('rzr_stripe_ach_payments_accepted');
		 	
		 	//user registration actions
		 	add_action('admin_post_nopriv_add_rzr_user' , array( &$this, 'add_rzr_user'));
			add_action('admin_post_add_rzr_user', array( &$this, 'add_rzr_user'));
		 	
		 	//add project actions
		 	add_action('admin_post_nopriv_add_rzr_project' , array( &$this, 'save_rzr_project'));
			add_action('admin_post_add_rzr_project', array( &$this, 'save_rzr_project'));
			
			//add export transfer
		 	add_action('admin_post_nopriv_rzr_export_transfer' , array( &$this, 'rzr_export_transfer'));
			add_action('admin_post_rzr_export_transfer', array( &$this, 'rzr_export_transfer'));
			
			//update customer card onreporting dashboard
			add_action('admin_post_nopriv_update_cust_card', array( &$this, 'update_card_details'));
			add_action('admin_post_update_cust_card', array( &$this, 'update_card_details'));
			
			//update customer card onreporting dashboard
			add_action('admin_post_nopriv_update_pmt_settings', array( &$this, 'update_pmt_settings'));
			add_action('admin_post_update_pmt_settings', array( &$this, 'update_pmt_settings'));
			
			//update customer card onreporting dashboard
			add_action('admin_post_nopriv_update_customer_profile', array( &$this, 'update_customer_profile'));
			add_action('admin_post_update_customer_profile', array( &$this, 'update_customer_profile'));
			
			add_action('admin_post_nopriv_process_checkout_step_2', array( &$this, 'process_checkout_step_2'));
			add_action('admin_post_process_checkout_step_2', array( &$this, 'process_checkout_step_2'));
			
			add_action('admin_post_nopriv_process_checkout_step_3', array( &$this, 'process_checkout_step_3'));
			add_action('admin_post_process_checkout_step_3', array( &$this, 'process_checkout_step_3'));
			
			//social meta tags on posts
			add_action( 'wp_head', array( &$this, 'add_social_meta_tags') , 2 );
			
			//save project form admin screen
			add_action('save_post', array(&$this, 'save_rzr_project'));
		}
		
		// forgot password function
		public function forgot_password(){
			$data = '<section>
		      <h1 class="page_title">Password Reset</h1>
		      <div class="password_reset">
		        <div class="password_reset_section">
		          <!--<img class="image" src="'.get_bloginfo('template_directory').'/images/demo-images.jpg">-->
		          <p>Please enter your username or email address. You will receive a link to create a new password via email.</p>
		          <form action="'.wp_lostpassword_url("").'" method="post">
		          <span>
		            <label>E mail</label><br>
		            <input type="text" name="user_login" placeholder="Email Address"><br>
		          </span>
		          <div class="get-password">
		            <input type="submit" class="button_support" name="wp-submit" value="Get New Password">
		          </div>
		          </form>
		        </div>
		      </div>  
		    </section>';
		    return $data;
		}
		
		// function used to login
		public function rzr_login(){
			$email = wp_strip_all_tags($_POST['email']);
	        $pass = wp_strip_all_tags($_POST['pass']);
	        $remember = ($_POST['terms']=='agree'?true:false);
	        $creds = array();
	        $creds['user_login'] = $email;
	        $creds['user_password'] = $pass;
	        $creds['remember'] = $remember;
	        $user = wp_signon( $creds, false );
	        
	        if ( is_wp_error($user) ){
	          echo $user->get_error_message();
	        }
	        else{
	          wp_redirect(admin_url());
	          die;
	        }
		}
		
		// function used to login form
		public function rzr_login_form($loginUrl=''){
			if(get_option('rzr_social_facebook_login')=="on"){
			$data = '
		    <form action="" method="post">
		    <section>
		    <!--<h1 class="page_title">Login Page</h1>-->
		    <div class="password_reset login_page">
		      <div class="password_reset_section">
		        <span>
		          <label>Username</label><br>
		          <input type="text" name="email" placeholder="Email Address"><br>
		        </span>
		        <span>
		          <label>Password</label><br>
		          <input type="password" name="pass" placeholder="Password"><br>
		        </span>
		        <span class="remember-me">
		          <input class="check_box" type="checkbox" name="terms" value="agree">Remember Me
		        </span>
		        <div class="get-password">
		          <input type="submit" class="button_support directory" name="submit" value="Login">
		        </div>
		      </div>
		    </div>
		    <div class="login_password_reset">
		      <p><a href="?action=forgot-pass">Lost password</a>?</p>
		    <button type="submit" class="button_support">Login with Facebook</button>
		      <a href="'.$loginUrl.'">Login with Facebook</a>
		    </div>
		  </section>
		  </form>
		  ';
		  }
		  else {
		  			$data = '
		    <form action="" method="post">
		    <section>
		    <!--<h1 class="page_title">Login Page</h1>-->
		    <div class="password_reset login_page">
		      <div class="password_reset_section">
		        <span>
		          <label>Username</label><br>
		          <input type="text" name="email" placeholder="Username or Email"><br>
		        </span>
		        <span>
		          <label>Password</label><br>
		          <input type="password" name="pass" placeholder="Password"><br>
		        </span>
		        <span class="remember-me">
		          <input class="check_box" type="checkbox" name="terms" value="agree">Remember Me
		        </span>
		        <div class="get-password">
		          <input type="submit" class="button_support directory" name="submit" value="Login">
		        </div>
		      </div>
		    </div>
		    <div class="login_password_reset">
		      <p><a href="?action=forgot-pass">Lost password</a>?</p>
		    </div>
		  </section>
		  </form>
		  ';
		  }
		  return $data;
		}

		// function used to login using facebook
		function rzr_facebook_login(){
			session_start();
		    require 'facebook/vendor/autoload.php';
		    $fb = new Facebook\Facebook([
		    'app_id' => get_option('rzr_social_facebook_app_id'), // Replace {app-id} with your app id
		    'app_secret' => get_option('rzr_social_facebook_app_secret'),
		    'default_graph_version' => 'v2.2',
		    'default_access_token' => '1450462385263252'
		    ]);

		    $helper = $fb->getRedirectLoginHelper();
		    
		    if($_GET['page']=='redirect')
		    {
		      try {
		        $accessToken = $helper->getAccessToken();
		      } catch(Facebook\Exceptions\FacebookResponseException $e) {
		        // When Graph returns an error
		        echo 'Graph returned an error: ' . $e->getMessage();
		        exit;
		      } catch(Facebook\Exceptions\FacebookSDKException $e) {
		        // When validation fails or other local issues
		        echo 'Facebook SDK returned an error: ' . $e->getMessage();
		        exit;
		      }

		      try {
		        // Get the Facebook\GraphNodes\GraphUser object for the current user.
		        // If you provided a 'default_access_token', the '{access-token}' is optional.
		        $response = $fb->get('/me?fields=id,name,email,first_name,last_name,location,locale,hometown', $accessToken);

		      } catch(Facebook\Exceptions\FacebookResponseException $e) {
		        // When Graph returns an error
		        echo 'Graph returned an error: ' . $e->getMessage();
		        exit;
		      } catch(Facebook\Exceptions\FacebookSDKException $e) {
		        // When validation fails or other local issues
		        echo 'Facebook SDK returned an error: ' . $e->getMessage();
		        exit;
		      }

		      $user = $response->getGraphUser();
		      $user_fb_email = $user->getEmail();
		      $user_fb_id = $user->getId();
		      $user_fb_name = $user['name'];
		      $user_fb_fn = $user['first_name'];
		      $user_fb_ln = $user['last_name'];

		      if($user_fb_email != '')
		      {
		        $user_name = $user_fb_email;
		        $user_email = $user_fb_email;
		        $user_id = username_exists( $user_name );
		        if(!$user_id)
		        {
		          $userdata = array( 'user_login' => $user_email, 'user_email' => $user_email, 'user_pass' => wp_generate_password() );
		          $user_id = wp_insert_user( $userdata );
		        }

		        if($user_id){

		          (get_user_meta($user_id, 'first_name', true)!=''?update_user_meta($user_id, 'first_name', $user_fb_fn):add_user_meta($user_id, 'first_name', $user_fb_fn));
		          
		          (get_user_meta($user_id, 'last_name', true)!=''?update_user_meta($user_id, 'last_name', $user_fb_ln):add_user_meta($user_id, 'last_name', $user_fb_ln));

		          wp_set_auth_cookie( $user_id );
				  $dashboard_url = $this->get_template_url("customer-dashboard");
		          wp_redirect($dashboard_url);
		          exit();
		        }


		      // Create a new user
		      }
			}
			return $helper;
		}

		// update project meta
		function update_project_meta($project_id=0, $user_id=0, $meta_key='', $meta_value='', $level_id=0){
		  global $wpdb;
		  $get_pledge = $wpdb->get_var("SELECT `id` FROM ".$wpdb->prefix."rzr_projects_meta WHERE `meta_key`='".$meta_key."' AND `project_id`='".$project_id."' AND `level_id`='".$level_id."' AND `user_id`='".$user_id."'");

		  if($get_pledge!='')
		  {
		    $wpdb->query("UPDATE ".$wpdb->prefix."rzr_projects_meta SET `meta_value`='".$meta_value."', `modified_at`=NOW() WHERE `project_id`='".$project_id."' AND `user_id`='".$user_id."' AND `meta_key`='".$meta_key."'");
		  }
		  else
		  {
		    $wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_projects_meta(`project_id`, `level_id`, `user_id`, `meta_key`, `meta_value`, `created_at`, `modified_at`) VALUES('".$project_id."', '".$level_id."', '".$user_id."', '".$meta_key."', '".$meta_value."', NOW(), NOW())");  
		  }
		}

		function add_project_pledge($project_id=0, $user_id=0, $meta_key='', $meta_value='', $level_id=0){
			global $wpdb;
			
			$check_pledge = $wpdb->get_var("SELECT COUNT(`id`) FROM ".$wpdb->prefix."rzr_projects_meta WHERE `project_id`='".$project_id."' AND `user_id`='".$user_id."' AND `level_id`='".$level_id."'");
			if($check_pledge == 0)
			{
				$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_projects_meta(`project_id`, `level_id`, `user_id`, `meta_key`, `meta_value`, `created_at`, `modified_at`) VALUES('".$project_id."', '".$level_id."', '".$user_id."', 'project_pledge_start', '".time()."', NOW(), NOW())");	
			}
			$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_projects_meta(`project_id`, `level_id`, `user_id`, `meta_key`, `meta_value`, `created_at`, `modified_at`) VALUES('".$project_id."', '".$level_id."', '".$user_id."', '".$meta_key."', '".$meta_value."', NOW(), NOW())");
		}

		function add_mailchimp_subscriber($project_id=0, $user_email=0){
			$email_mailchimp_api_platform = get_option('rzr_email_mailchimp_api');
			$email_mailchimp_api_user = get_user_meta(get_post_field( 'post_author', $project_id ), 'mailchimp_api', true);
			$email_mailchimp_list_id_platform = get_option('rzr_email_mailchimp_list_id');
			$email_mailchimp_list_id_project = get_post_meta($project_id, 'project_mailchimp', true);
			if(get_option('rzr_email_use_mailchimp')=="on"){
				if(!empty($email_mailchimp_api_platform) && !empty($email_mailchimp_list_id_platform)){
					$MailChimp = new MailChimp($email_mailchimp_api_platform);
					$list_id = $email_mailchimp_list_id_platform;
			
					$result = $MailChimp->post("lists/$list_id/members", [
				                'email_address' => $user_email,
				                'status'        => 'subscribed',
				            ]);
					}
				if(!empty($email_mailchimp_api_user) && !empty($email_mailchimp_list_id_project)){
		    		$MailChimp = new MailChimp($email_mailchimp_api_user);
		    		$list_id = $email_mailchimp_list_id_project;
		    
		    	 	$result = $MailChimp->post("lists/$list_id/members", [
				                'email_address' => $user_email,
				                'status'        => 'subscribed',
				            ]);
				}
			}
		}
		
		//send email to pledger
		function send_pledge_receipt($customer_email=0, $project_title=0, $project_level=0, $level_amount=0){
			if (get_option('rzr_email_receipt_sent_donor_checkout') == "on") {
				$from_name = get_option('rzr_company_name');
				$from_email = get_option('rzr_company_email');
				$to = $customer_email;
				$subject = $project_title . ' - Pledge Receipt';
				$shortcodes=array("[project_level]","[project_title]", "[level_amount]");
				$values=array($project_level,$project_title, $level_amount);
				$message=get_option('rzr_email_receipt_sent_donor_checkout_body');
				$message = str_replace($shortcodes,$values,$message);
				$sendgrid_template = get_option('rzr_email_sendgrid_template_id');
				if ($sendgrid_template != "") {
					$headers = array();
					$headers[] = 'From: '.$from_name.' <'.$from_email.'>';
					$headers[] = 'template: '.$sendgrid_template;
				}
				else {
					$headers = array();
					$headers[] = 'From: '.$from_name.' <'.$from_email.'>';
				}
				echo $headers;
				add_filter('wp_mail_content_type', 'set_html_content_type');
				wp_mail( $to, $subject, $message, $headers );
				remove_filter('wp_mail_content_type', 'set_html_content_type');

			}
		}
		
		//send email to owner about new pledge
		function send_owner_customer_checkout($pr_owner=0, $project_title=0, $project_level=0, $level_amount=0){
			if (get_option('rzr_email_owner_customer_checkout') == "on") {
				$from_name = get_option('rzr_company_name');
				$from_email = get_option('rzr_company_email');
				$owner_info = get_userdata($pr_owner);
				$to = $owner_info->user_email;
				$subject = $project_title . ' - Customer Checkout';
				$shortcodes=array("[project_level]","[project_title]", "[level_amount]");
				$values=array($project_level,$project_title, $level_amount);
				$message=get_option('rzr_email_owner_checkout_body');
				$message = str_replace($shortcodes,$values,$message);
				$sendgrid_template = get_option('rzr_email_sendgrid_template_id');
				if ($sendgrid_template != "") {
					$headers = array();
					$headers[] = 'From: '.$from_name.' <'.$from_email.'>';
					$headers[] = 'template: '.$sendgrid_template;
				}
				else {
					$headers = array();
					$headers[] = 'From: '.$from_name.' <'.$from_email.'>';
				}
				echo $headers;
				add_filter('wp_mail_content_type', 'set_html_content_type');
				wp_mail( $to, $subject, $message, $headers );
				remove_filter('wp_mail_content_type', 'set_html_content_type');

			}
		}
		
		//send email to pledger as payments are made for recurring pledges
		function send_recurring_payment_receipt($customer_email=0, $project_title=0, $project_level=0, $level_amount=0){
			if (get_option('rzr_email_receipt_recurring_payment') == "on") {
				$from_name = get_option('rzr_company_name');
				$from_email = get_option('rzr_company_email');
				$to = $customer_email;
				$subject = $project_title . ' - Payment Receipt';
				$shortcodes=array("[project_level]","[project_title]", "[level_amount]");
				$values=array($project_level,$project_title, $level_amount);
				$message=get_option('rzr_email_receipt_recurring_payment_body');
				$message = str_replace($shortcodes,$values,$message);
				$sendgrid_template = get_option('rzr_email_sendgrid_template_id');
				if ($sendgrid_template != "") {
					$headers = array();
					$headers[] = 'From: '.$from_name.' <'.$from_email.'>';
					$headers[] = 'template: '.$sendgrid_template;
				}
				else {
					$headers = array();
					$headers[] = 'From: '.$from_name.' <'.$from_email.'>';
				}
				echo $headers;
				add_filter('wp_mail_content_type', 'set_html_content_type');
				wp_mail( $to, $subject, $message, $headers );
				remove_filter('wp_mail_content_type', 'set_html_content_type');

			}
		}
		
		//send email to pledger for failed recurring payment
		function send_recurring_payment_failure($customer_email=0, $project_title=0, $project_level=0, $level_amount=0){
			if (get_option('rzr_email_receipt_recurring_failure') == "on") {
				$from_name = get_option('rzr_company_name');
				$from_email = get_option('rzr_company_email');
				$to = $customer_email;
				$subject = $project_title . ' - Payment Failure';
				$shortcodes=array("[project_level]","[project_title]", "[level_amount]");
				$values=array($project_level,$project_title, $level_amount);
				$message=get_option('rzr_email_receipt_failure_body');
				$message = str_replace($shortcodes,$values,$message);
				$sendgrid_template = get_option('rzr_email_sendgrid_template_id');
				if ($sendgrid_template != "") {
					$headers = array();
					$headers[] = 'From: '.$from_name.' <'.$from_email.'>';
					$headers[] = 'template: '.$sendgrid_template;
				}
				else {
					$headers = array();
					$headers[] = 'From: '.$from_name.' <'.$from_email.'>';
				}
				echo $headers;
				add_filter('wp_mail_content_type', 'set_html_content_type');
				wp_mail( $to, $subject, $message, $headers );
				remove_filter('wp_mail_content_type', 'set_html_content_type');

			}
		}
		
		//send new user registration email
		function send_user_registration_details($customer_email=0, $user_id=0, $random_password=0){
			if (get_option('rzr_email_user_registration') == "on") {
				$from_name = get_option('rzr_company_name');
				$from_email = get_option('rzr_company_email');
				$to = $customer_email;
				$subject = 'User Registration Details';
				$first_name = get_user_meta($user_id, 'first_name', true);
				$last_name = get_user_meta($user_id, 'last_name', true);
				$user_info = get_userdata($user_id);
				$username = $user_info->user_login;
				$shortcodes=array("[first_name]","[last_name]", "[username]", "[password]");
				$values=array($first_name, $last_name, $username, $random_password);
				$message=get_option('rzr_email_user_registration_body');
				$message = str_replace($shortcodes,$values,$message);
				$sendgrid_template = get_option('rzr_email_sendgrid_template_id');
				if ($sendgrid_template != "") {
					$headers = array();
					$headers[] = 'From: '.$from_name.' <'.$from_email.'>';
					$headers[] = 'template: '.$sendgrid_template;
				}
				else {
					$headers = array();
					$headers[] = 'From: '.$from_name.' <'.$from_email.'>';
				}
				echo $headers;
				add_filter('wp_mail_content_type', 'set_html_content_type');
				wp_mail( $to, $subject, $message, $headers );
				remove_filter('wp_mail_content_type', 'set_html_content_type');

			}
		}
		
		//send email to admin on project submit
		function send_creation_notification($project_title=0){
		if (get_option('rzr_email_creator_submission_project') == "on") {

			$to = get_option('admin_email', false);
			$subject = 'Project - '. $project_title . ' - Needs to be Reviewed';
			$shortcodes=array("[project_title]","[login_link]");
			$values=array($project_title, '<a href="' . site_url() .'/login">Login</a>');
			$message=get_option('rzr_email_creator_submission_body');
			$message = str_replace($shortcodes,$values,$message);
			$sendgrid_template = get_option('rzr_email_sendgrid_template_id');
			if ($sendgrid_template != "") {
				$headers = array();
				$headers[] = 'template: '.$sendgrid_template;
			}
			add_filter('wp_mail_content_type', 'set_html_content_type');
			wp_mail( $to, $subject, $message, $headers );
			remove_filter('wp_mail_content_type', 'set_html_content_type');
			}
		}

		// list all the donors
		public function get_all_donors(){
			$project_id = 0;
			if(isset($_GET['project_id']))
			{
				$project_id = wp_strip_all_tags($_GET['project_id']);
			}
			if($project_id!=0)
			{	
				$donors = $this->wp_db->get_results("SELECT `user_id`, `project_id`, `level_id` FROM (SELECT * FROM `".$this->wp_db->prefix."rzr_projects_meta` WHERE `meta_key`='project_pledge' AND `project_id`= '".$project_id."' ORDER BY `created_at` ASC) AS tmp_table");
			}
			else
			{
				return false;
			}
			return $donors;
		}

		// get donor pledge 
		public function get_donor_pledge($donor_id=0){
			$pledge = $this->wp_db->get_row("SELECT `meta_value` FROM ".$this->wp_db->prefix."rzr_projects_meta WHERE `user_id`=".$donor_id." AND `meta_key`='project_pledge' ORDER BY `created_at` DESC LIMIT 1");
			return $pledge;
		}

		// get pledge recurring
		public function get_pledge_recurring($project_id=0, $level_id=0, $user_id=0){
			$pledge_recurring = $this->wp_db->get_row("SELECT `meta_value` FROM ".$this->wp_db->prefix."rzr_projects_meta WHERE `user_id`=".$user_id." AND `project_id`=".$project_id." AND `level_id`=".$level_id." AND `meta_key`='project_pledge_recurring' ORDER BY `created_at` DESC LIMIT 1");
			return (!empty($pledge_recurring)?$pledge_recurring->meta_value:0);	
		}

		public function get_pledge_paid($project_id=0, $level_id=0, $user_id=0){
			$pledge_paid = $this->wp_db->get_row("SELECT `meta_value` FROM ".$this->wp_db->prefix."rzr_projects_meta WHERE `user_id`=".$user_id." AND `project_id`=".$project_id." AND `level_id`=".$level_id." AND `meta_key`='project_pledge_paid' GROUP BY `meta_value` ORDER BY `created_at` DESC LIMIT 1");
			
			return (!empty($pledge_paid)?$pledge_paid->meta_value:0);		
		}

		// pledges listing
		public function get_pledge_expiration(){
			$project_id = 0;
			if($_GET['project_id']!='')
			{
				$project_id = wp_strip_all_tags($_GET['project_id']);
			}
			if($project_id!=0)
			{
				$current_date = date('Y-m-d h:m:s');
				$next_date = date('Y-m-d h:m:s', strtotime('+3 months'));
				$prev_date = date('Y-m-d h:m:s', strtotime('-3 months'));
				$pledges = $this->wp_db->get_results("SELECT * FROM ".$this->wp_db->prefix."rzr_projects_meta WHERE `meta_key`='project_pledge' AND `project_id`='".$project_id."' AND `created_at` > '".$prev_date."'");
				foreach($pledges as $key=>$pledge)
				{
					$level_id = $pledge->level_id;
					$project_id = $pledge->project_id;
					$user_id = $pledge->user_id;

					$freq = get_post_meta($project_id, 'project_level_frequency'.$level_id, true);
					$no = get_post_meta($project_id, 'project_level_pmts'.$level_id, true);
					$created_timestamp = strtotime($pledge->created_at);
					$current_timestamp = time();
					$time_diff = $created_timestamp-$current_timestamp;
					$pledge_recurring = $this->get_pledge_recurring($project_id, $level_id, $user_id);
					
					$pledge_paid = $this->get_pledge_paid($project_id, $level_id, $user_id);

					$pledges[$key] = (object) array_merge( (array)$pledge, array( 'pledged_amount' => $pledge_paid ) );
					if($pledge_end_date > $next_date)
					{
						unset($pledges[$key]);
					}
				}

				return $pledges;

			}
			else
			{
				return false;
			}
		}

		// pledge amount till date
		public function get_pledge_amount_till_date($project_id=0, $level_id=0){
			//$q = $this->wp_db->get_var("SELECT `created_at` FROM ".$this->wp_db->prefix."rzr_projects_meta WHERE ");
			//echo "SELECT SUM(`meta_value`) AS total FROM ".$this->wp_db->prefix."rzr_projects_meta WHERE `meta_key`='project_pledge' AND `project_id`='".$project_id."' AND '".date('Y-m-d h:i:s')."' >= `created_at`";
			$pledge_amount = $this->wp_db->get_var("SELECT SUM(`meta_value`) AS total FROM ".$this->wp_db->prefix."rzr_projects_meta WHERE `meta_key`='project_pledge' AND `project_id`='".$project_id."'");

			//$pledge_amount = $this->wp_db->get_var("SELECT SUM(`meta_value`) AS total FROM ".$this->wp_db->prefix."rzr_projects_meta WHERE `meta_key`='project_pledge' AND `project_id`='".$project_id."' AND '2016-07-21 02:34:08' > `2016-07-22 02:34:08`");

			return $pledge_amount;
		}

		public function get_project_cash_flow($project_id=151){
			$year = ($_GET['year_c']!=''?$_GET['year_c']:date("Y"));
			$y[] = strtotime($year.'-1-1');
			$y[] = strtotime($year.'-2-1');
			$y[] = strtotime($year.'-3-1');
			$y[] = strtotime($year.'-4-1');
			$y[] = strtotime($year.'-5-1');
			$y[] = strtotime($year.'-6-1');
			$y[] = strtotime($year.'-7-1');
			$y[] = strtotime($year.'-8-1');
			$y[] = strtotime($year.'-9-1');
			$y[] = strtotime($year.'-10-1');
			$y[] = strtotime($year.'-11-1');
			$y[] = strtotime(($year).'-12-1');

			$jan_1 = date('Y-m-d H:m:s', strtotime(($year+1).'-12-1'));

			$q = "SELECT `meta_value` FROM ".$this->wp_db->prefix."rzr_projects_meta WHERE `created_at`>='".$jan."' AND `created_at`<'".$feb."' AND `meta_key`='project_pledge'";
			//echo "SELECT * FROM ".$wpdb->prefix."rzr_projects_meta WHERE `project_id`='".$project_id."' AND `meta_key`='project_pledge_start' AND `meta_value` != ''";	
			$q1 = $this->wp_db->get_results("SELECT * FROM ".$this->wp_db->prefix."rzr_projects_meta WHERE `project_id`='".$project_id."' AND `meta_key`='project_pledge_start' AND `meta_value` != ''");
			//$sum = array('jan'=>0,'feb'=>0,'mar'=>0, 'apr'=>0, 'may'=>0, 'june'=>0, 'july'=>0, 'aug'=>0, 'sept'=>0, 'oct'=>0, 'nov'=>0, 'dec'=>0);
			foreach($q1 as $q1_single)
			{
				$level_id = $q1_single->level_id;
				$level_freq = get_post_meta($project_id, 'project_level_frequency'.$level_id, true);
				$level_recur = get_post_meta($project_id, 'project_level_recurring'.$level_id, true);
				$level_pmts = (get_post_meta($project_id, 'project_level_pmts'.$level_id, true)!=''?get_post_meta($project_id, 'project_level_pmts'.$level_id, true):0);
				//$total = 0;
				$pledge_start_date = $q1_single->meta_value;
				$pledge_end_date = time();
				if($level_freq == 'weekly')
				{
					$pledge_end_date = strtotime('+'.$level_pmts.' weeks', strtotime($q1_single->created_at));
				}
				elseif($level_freq == 'monthly')
				{
					$pledge_end_date = strtotime('+'.$level_pmts.' months', strtotime($q1_single->created_at));

					// die(($pledge_end_date/$pledge_start_date)/(60*60*24*7));
				}

				
				foreach($y as $key=>$y_single)
				{
					if($pledge_end_date >= $y[$key])
					{
						if($pledge_start_date > $y[$key] && $pledge_start_date < $y[$key+1])
							{
								if($level_freq == 'weekly')
								{
									$pledge_month_diff = $y[$key+1]-$pledge_start_date;
									$weeks = ceil(abs($pledge_month_diff/(60*60*24*7)));
							 		$weeks_amt = $weeks*$level_recur;
							 		$sum[$key] += $weeks_amt;
							 	}
							 	else
							 	{
							 		$sum[$key] += $level_recur;
							 	}
								
							}
							elseif($y[$key] > $pledge_start_date && $y[$key+1] < $pledge_end_date)
							{
								if($level_freq == 'weekly')
								{
									$weeks_amt = 4*$level_recur;
									$sum[$key] += $weeks_amt;
								}
								else
								{
									$sum[$key] += $level_recur;
								}
							}
							elseif($y[$key] > $pledge_start_date && $y[$key+1] > $pledge_end_date)
							{
								if($level_freq == 'weekly')
								{
									$pledge_month_diff = $pledge_end_date-$y[$key];
									$weeks = ceil(abs($pledge_month_diff/(60*60*24*7)));
							 		$weeks_amt = $weeks*$level_recur;
							 		$sum[$key] += $weeks_amt;
							 	}
							 	else
								{
									$sum[$key] += $level_recur;
								}
							}
					}
				}

			}
			
			return $sum;
		}

		// get projects
		public function get_projects(){
		global $current_user;
		$project_owner = $current_user->ID;
			$args = array(
				'post_type' => 'project',
				'post_status' => 'publish',
				'orderby' => 'post_date',
				'order' => 'DESC',
				'author' => $project_owner
			);

			$projects = get_posts($args);
			return $projects;
		}

		// remainig balances report
		public function get_remaining_balances(){
			$project_id = 0;
			if($_GET['project_id']!='')
			{
				$project_id = wp_strip_all_tags($_GET['project_id']);
			}
			if($project_id!=0)
			{
				$get_project_users = $this->wp_db->get_results("SELECT `meta_value` AS total, user_id, `level_id` FROM ".$this->wp_db->prefix."rzr_projects_meta WHERE `project_id`='".$project_id."' AND `meta_key`='project_pledge' AND `project_id`='".$project_id."' ORDER BY `created_at` DESC");
			}
			else
			{
				return false;
				}
			
			return $get_project_users;
		}

		// get projects select box
		public function get_projects_selectbox(){
			$project_id = 0;
			if(isset($_GET['project_id']))
			{
				$project_id = wp_strip_all_tags($_GET['project_id']);
			}
			$projects = $this->get_projects();
			$data = '<span class=""><select class="select_city select_title" onchange="javascript:location.href = this.value;"><option value="?">Select Project</option>';
			foreach($projects as $project)
			{
				$data .= '<option value="?project_id='.$project->ID.'" '.($project->ID==$project_id?'selected':'').'>'.$project->post_title.'</option>';
			}
			$data .= '</select></span>';
			return $data;
		}

		public function get_latest_year(){
			$projects = $this->get_projects();
			$year = array();
			$year_start = array();
			$i = 1;
			foreach($projects as $single_project)
			{
				$project_id = $single_project->ID;
				$q1 = $this->wp_db->get_results("SELECT * FROM ".$this->wp_db->prefix."rzr_projects_meta WHERE `project_id`='".$project_id."' AND `meta_key`='project_pledge_start' AND `meta_value` != ''");
				foreach($q1 as $q1_single)
				{
					//$fre =
					$level_id = $q1_single->level_id;
					$level_freq = get_post_meta($project_id, 'project_level_frequency'.$level_id, true);
					$level_recur = get_post_meta($project_id, 'project_level_recurring'.$level_id, true);
					$level_pmts = (get_post_meta($project_id, 'project_level_pmts'.$level_id, true)!=''?get_post_meta($project_id, 'project_level_pmts'.$level_id, true):0);
					//$total = 0;
					$pledge_start_date = $q1_single->meta_value;
					$pledge_end_date = time();
					if($level_freq == 'weekly')
					{
						$pledge_end_date = strtotime('+'.$level_pmts.' weeks', strtotime($q1_single->created_at));
					}
					elseif($level_freq == 'monthly')
					{
						$pledge_end_date = strtotime('+'.$level_pmts.' months', strtotime($q1_single->created_at));
					}
					$year_pledge = date('Y', $pledge_end_date);
					$year_start_pledge = date('Y', $q1_single->meta_value);
					$year[$year_pledge] = $i;
					$year_start[$year_start_pledge] = $i;
					$i++;
				}
			}
			// echo "<pre>";
			// print_r($year);
			// echo "</pre>";

			if(count($year)>0)
			{
				krsort($year);
				krsort($year_start);
				$final_year = array_flip($year);
				$final_start_year = array_flip($year_start);
				return array(max($final_year), min($final_start_year));	
			}
			else
			{
				return date('Y');
			}
		}

		// get years dropdown
		public function get_years_dropdown(){
			list($year, $min_year) = $this->get_latest_year();

			if($_GET['year_c']!='')
			{
				$year_c = wp_strip_all_tags($_GET['year_c']);
			}
			else
			{
				$year_c = date('Y');
			}
			$data = '<span class=""><select class="select_city select_title" onchange="javascript:location.href = this.value;">';
			for($i=$year;$i>=$min_year;$i--)
			{
				$data .= '<option value="?year_c='.$i.'" '.($i==$year_c?'selected':'').'>'.$i.'</option>';
				//$year--;
			}
			$data .= '</select></span>';
			return $data;
		}

		// get project detail
		public function get_project_payment($project_id=0){
			$project_payments = $this->wp_db->get_results("SELECT * FROM ".$this->wp_db->prefix."rzr_projects_meta WHERE `project_id`='".$project_id."' AND `meta_key`='project_pledge' ORDER BY `created_at` DESC LIMIT 10");
			return $project_payments;
		}
		
		// get stripe customer id
		public function get_strip_customer(){
			if(is_user_logged_in())
			{
				global $current_user;
				return $this->wp_db->get_var("SELECT `stripe_user_id` FROM ".$this->wp_db->prefix."rzr_stripe_users WHERE `user_id`=".$current_user->ID);			
			}
			
		}
		
		// add stripe connected
		public function add_stripe_connected($resp = array()){
			if(is_user_logged_in())
			{
				global $current_user;
				
				if(get_option('rzr_stripe_test_mode')=="on") {
					$live_mode="0";
				} else {
					$live_mode="1";
				}

				$stripe_user_id = $resp['stripe_user_id'];

				$this->wp_db->query("INSERT INTO ".$this->wp_db->prefix."rzr_stripe_connected(`user_id`, `livemode`, `stripe_user_id`, `created_at`) VALUES('".$current_user->ID."', '".$live_mode."', '".$stripe_user_id."', NOW())");
				
				echo 'User Connected Successfully';
			}	
		}
		
		// check stripe connected exist
		public function exist_stripe_connected(){
			global $current_user;
			$strip_connected = $this->wp_db->get_results("SELECT * FROM ".$this->wp_db->prefix."rzr_stripe_connected WHERE `user_id`=".$current_user->ID);
			if(count($strip_connected)>0)
			return true;
			else
			return false;
		}
		
		// get connected stripe account
		public function get_connected_stripe_account($user_id=0){
			global $current_user;
			$connected_account = $this->wp_db->get_var("SELECT `stripe_user_id` FROM ".$this->wp_db->prefix."rzr_stripe_connected WHERE `user_id`=".$user_id."");
			if($connected_account !='')
			{
				return $connected_account;	
			}
			else
			{
				return false;	
			}	
		}
		
		public function get_stripe_keys(){
			$stripe_keys=array(
				"stripe_secret_key"=>$this->secret_key,
				"stripe_publishable_key"=>$this->publishable_key);
			return $stripe_keys;	
		}

		// get project pledge
		public function get_project_pledge($id = 0){
			global $wpdb;
			$pledge = $wpdb->get_row("SELECT SUM(`meta_value`) AS total_pledge FROM ".$wpdb->prefix."rzr_projects_meta WHERE `project_id`='".$id."' AND `meta_key`='project_pledge'");
			return $pledge->total_pledge;
		}
		
		public function get_project_match($id = 0){
			global $wpdb;
			$match = $wpdb->get_row("SELECT SUM(`meta_value`) AS total_match FROM ".$wpdb->prefix."rzr_projects_meta WHERE `project_id`='".$id."' AND `meta_key`='project_match'");
			return $match->total_match;
		}

		public function get_wp_customer_stripe($customer=''){
			global $wpdb;
			$user_id = $wpdb->get_var("SELECT `user_id` FROM ".$wpdb->prefix."rzr_stripe_users WHERE `stripe_user_id`='".$customer."'");
			return $user_id;
		}
		
		// function used to get paid pledges 
		public function get_paid_pledge($project_id=0, $level_id=0, $user_id=0){
			global $wpdb;
			$paid_pledge = $wpdb->get_var("SELECT SUM(`meta_value`) FROM ".$wpdb->prefix."rzr_projects_meta WHERE `project_id`='".$project_id."' AND `level_id`='".$level_id."' AND `user_id`='".$user_id."' AND `meta_key`='project_pledge_paid'");
			return $paid_pledge;
		} 

		// function to get pledge start date
		public function get_pledge_start_date($project_id=0,$level_id=0,$user_id=0){
			global $wpdb;
			$pledge_start = $wpdb->get_var("SELECT `meta_value` FROM ".$wpdb->prefix."rzr_projects_meta WHERE `meta_key`='project_pledge_start' AND `project_id`='".$project_id."' AND `level_id`='".$level_id."' AND `user_id`='".$user_id."'");
			return $pledge_start;
		}

		public function get_user_from_balanced($bal_id=0){
			global $wpdb;
			$cust_id = $wpdb->get_var("SELECT `cust_id` FROM `".$wpdb->prefix."balanced_transactions` WHERE `txn_id`='".$bal_id."'");
			if($cust_id != '')
			{
				$user_id = $wpdb->get_var("SELECT `user_id` FROM `".$wpdb->prefix."rzr_stripe_users` WHERE `stripe_user_id`='".$cust_id."'");
				return $user_id;	
			}
			else
			{
				return false;
			}
			
		}
	
		function add_customer_upload_caps() {
				// gets the customer role
				$customer_role = get_role( 'customer' );
				$caps = array(
					'edit_published_posts',
					'edit_published_pages',
					'edit_others_pages',
					'delete_posts',
					'delete_pages',
					'edit_posts',
					'edit_others_posts',
					'upload_files',
					'manage_categories'
				);

				foreach ( $caps as $cap ) {
	
					// Remove the capability.
					$customer_role->add_cap( $cap );
				}
	
			}
			
		function remove_customer_upload_caps() {
				// gets the customer role
				$customer_role = get_role( 'customer' );
				$caps = array(
					'edit_published_posts',
					'edit_published_pages',
					'edit_others_pages',
					'delete_posts',
					'delete_pages',
					'edit_posts',
					'edit_others_posts',
					'upload_files',
					'manage_categories'
					
				);

				foreach ( $caps as $cap ) {
	
					// Remove the capability.
					$customer_role->remove_cap( $cap );
				}
	
			}
			
		function get_connect_bank_details($connected_account=0) {
			require 'stripe/vendor/autoload.php';
			$api_key = $this->secret_key;
			\Stripe\Stripe::setApiKey($api_key);
			$account = \Stripe\Account::retrieve($connected_account);
			$bank_account = $account->external_accounts;
			foreach ($bank_account->data as $bank_detail) {
				$bank_last4 = $bank_detail->last4;
				$bank_routing = $bank_detail->routing_number;
				$bank_name = $bank_detail->bank_name;
				$bank_last4="xxxxxxxx".$bank_last4;
	
					}
			$bank_details=array(
				"bank_last4"=>$bank_last4,
				"bank_routing"=>$bank_routing,
				"bank_name"=>$bank_name,
				"bank_last4"=>$bank_last4
			);
			return $bank_details;
		}
		
		function create_stripe_managed($acct_token=0, $user_id=0) {
		
			require 'stripe/vendor/autoload.php';
			$api_key = $this->secret_key;
			$user_meta = array_map( function( $a ){ return $a[0]; }, get_user_meta( $user_id ) );
			
			\Stripe\Stripe::setApiKey($api_key);
			try{
			$stripe_account = \Stripe\Account::create(array(
			  "managed" => true,
			  "country" => "US",
			  "email" => $current_user->user_email,
			  "business_name"=> $user_meta['company'],
			  "statement_descriptor"=> $user_meta['acct_descriptor'],
			  "legal_entity"=> array(
					"type"=>$user_meta['org_type'], 
					"business_name"=>$user_meta['company'],
					"first_name"=>$user_meta['first_name'], 
					"last_name"=>$user_meta['last_name'],
					"address"=>array(
						"city"=>$user_meta['city'],
						"line1"=>$user_meta['street'],
						"postal_code"=>$user_meta['zip_code'],
						"state"=>$user_meta['state'],
						"country"=>"US"
						),
					"business_tax_id"=>$user_meta['tax_id'],
					"ssn_last_4"=>$user_meta['ssn_last4'],
					"dob"=>array(
						"day"=>$user_meta['dob_day'], 
						"month"=>$user_meta['dob_month'], 
						"year"=>$user_meta['dob_year']
					)
				),
			   "tos_acceptance"=> array(
					"date"=>time(),
					"ip"=>$_SERVER['REMOTE_ADDR']
				),
			   "external_account"=>$acct_token
			   ));
			   } catch(\Stripe\Error\Card $e) {
					// Since it's a decline, \Stripe\Error\Card will be caught
					$body = $e->getJsonBody();
					$err  = $body['error'];
					$error=1;
					$error_message = $err['message'];
					} catch (\Stripe\Error\RateLimit $e) {
					// Too many requests made to the API too quickly
					$body = $e->getJsonBody();
					$err  = $body['error'];
					$error=1;
					$error_message = $err['message'];
					} catch (\Stripe\Error\InvalidRequest $e) {
					// Invalid parameters were supplied to Stripe's API
					$body = $e->getJsonBody();
					$err  = $body['error'];
					$error=1;
					$error_message = $err['message'];
					} catch (\Stripe\Error\Authentication $e) {
					// Authentication with Stripe's API failed
					// (maybe you changed API keys recently)
					$body = $e->getJsonBody();
					$err  = $body['error'];
					$error=1;
					$error_message = $err['message'];
					} catch (\Stripe\Error\ApiConnection $e) {
					// Network communication with Stripe failed
					$body = $e->getJsonBody();
					$err  = $body['error'];
					$error=1;
					$error_message = $err['message'];
					} catch (\Stripe\Error\Base $e) {
					// Display a very generic error to the user, and maybe send
					// yourself an email
					$body = $e->getJsonBody();
					$err  = $body['error'];
					$error=1;
					$error_message = $err['message'];
					} catch (Exception $e) {
					// Something else happened, completely unrelated to Stripe
					$body = $e->getJsonBody();
					$err  = $body['error'];
					$error=1;
					$error_message = $err['message'];
				}
			$stripe_account_arr = $stripe_account->__toArray(true);

		
			if($error != 1)
			{
				$resp = array (
					"stripe_user_id"=>$stripe_account_arr['id']
				);
			
			$this->add_stripe_connected($resp);	
			delete_user_meta($user_id, 'ssn_last4');
			wp_redirect( home_url()."/customer-dashboard/" );
			exit;
			}
			else {
			return $error_message;
			}
		}
		
		function update_pmt_settings() {
		
											global $current_user;
											$error='';
											$stripe_redirect='';
											$stripe_keys = $this->get_stripe_keys();
											$api_key = $stripe_keys['stripe_secret_key'];
											$user_id = $current_user->ID;
											$connected_account = $this->get_connected_stripe_account($current_user->ID);
											require 'stripe/vendor/autoload.php';
											
													$mailchimp_api = $_POST['mailchimp_api'];
													($mailchimp_api!=''?update_user_meta($user_id, 'mailchimp_api', $mailchimp_api):'');
													$org_type = $_POST['org_type'];
													($org_type!=''?update_user_meta($user_id, 'org_type', $org_type):'');
													$tax_id = $_POST['tax_id'];
													($tax_id!=''?update_user_meta($user_id, 'tax_id', $tax_id):'');
													$dob_year = $_POST['dob_year'];
													($dob_year!=''?update_user_meta($user_id, 'dob_year', $dob_year):'');
													$dob_month = $_POST['dob_month'];
													($dob_month!=''?update_user_meta($user_id, 'dob_month', $dob_month):'');
													$dob_day = $_POST['dob_day'];
													($dob_day!=''?update_user_meta($user_id, 'dob_day', $dob_day):'');

													$acct_descriptor = wp_strip_all_tags($_POST['acct_descriptor']);
													($acct_descriptor!=''?update_user_meta($user_id, 'acct_descriptor', $acct_descriptor):'');

													\Stripe\Stripe::setApiKey($api_key);
													//if account already exists and not updating the bank account update other things
													if($connected_account){
														$company = get_user_meta($user_id, 'company', true);
														$acct_descriptor = get_user_meta($user_id, 'acct_descriptor', true);
														$company = get_user_meta($user_id, 'company', true);
														$street = get_user_meta($user_id, 'street', true);
														$city = get_user_meta($user_id, 'city', true);
														$state = get_user_meta($user_id, 'state', true);
														$zip = get_user_meta($user_id, 'zip_code', true);
				
														$stripe_account = \Stripe\Account::retrieve($connected_account);
															$stripe_account->business_name = $company;
															$stripe_account->statement_descriptor = $acct_descriptor;
															$stripe_account->legal_entity->business_tax_id = $tax_id;
															$stripe_account->legal_entity->business_name = $company;
															$stripe_account->legal_entity->address->city = $city;
															$stripe_account->legal_entity->address->state = $state; 
															$stripe_account->legal_entity->address->postal_code = $zip; 
															$stripe_account->legal_entity->address->line1 = $street; 
															$stripe_account->legal_entity->dob->year = $dob_year; 
															$stripe_account->legal_entity->dob->month = $dob_month; 
															$stripe_account->legal_entity->dob->day = $dob_day; 
														$stripe_account->save();
													}
				
													if(isset($_POST['piitoken'])){
														$pii_token = $_POST['piitoken'];
														\Stripe\Stripe::setApiKey($api_key);
															if($connected_account){
																try {
																	$stripe_account = \Stripe\Account::retrieve($connected_account);
																	  $stripe_account->legal_entity->personal_id_number = $pii_token; 
																	$stripe_account->save();
																}
																catch(\Stripe\Error\Card $e) {
																	// Since it's a decline, \Stripe\Error\Card will be caught
																	$body = $e->getJsonBody();
																	$err  = $body['error'];
																	$error=1;
																	$error_message = $err['message'];
																	} catch (\Stripe\Error\RateLimit $e) {
																	// Too many requests made to the API too quickly
																	$body = $e->getJsonBody();
																	$err  = $body['error'];
																	$error=1;
																	$error_message = $err['message'];
																	} catch (\Stripe\Error\InvalidRequest $e) {
																	// Invalid parameters were supplied to Stripe's API
																	$body = $e->getJsonBody();
																	$err  = $body['error'];
																	$error=1;
																	$error_message = $err['message'];
																	} catch (\Stripe\Error\Authentication $e) {
																	// Authentication with Stripe's API failed
																	// (maybe you changed API keys recently)
																	$body = $e->getJsonBody();
																	$err  = $body['error'];
																	$error=1;
																	$error_message = $err['message'];
																	} catch (\Stripe\Error\ApiConnection $e) {
																	// Network communication with Stripe failed
																	$body = $e->getJsonBody();
																	$err  = $body['error'];
																	$error=1;
																	$error_message = $err['message'];
																	} catch (\Stripe\Error\Base $e) {
																	// Display a very generic error to the user, and maybe send
																	// yourself an email
																	$body = $e->getJsonBody();
																	$err  = $body['error'];
																	$error=1;
																	$error_message = $err['message'];
																	} catch (Exception $e) {
																	// Something else happened, completely unrelated to Stripe
																	$body = $e->getJsonBody();
																	$err  = $body['error'];
																	$error=1;
																	$error_message = $err['message'];
																	}
														if($error==1){
															wp_redirect( home_url()."/customer-dashboard/?tab=payment?message=pii" );
														}
													}
													}
													
				
												if(isset($_POST['accttoken'])) {
													$acct_token = $_POST['accttoken'];
													$first_name = get_user_meta($user_id, 'first_name', true);
													$last_name = get_user_meta($user_id, 'last_name', true);
													$email = $current_user->user_email;
													$company = get_user_meta($user_id, 'company', true);
													$org_type = get_user_meta($user_id, 'org_type', true);
													$ssn_last4 = $_POST['ssn_last4'];
													$dob_year = get_user_meta($user_id, 'dob_year', true);
													$dob_month = get_user_meta($user_id, 'dob_month', true);
													$dob_day = get_user_meta($user_id, 'dob_day', true);
													$street = get_user_meta($user_id, 'street', true);
													$city = get_user_meta($user_id, 'city', true);
													$state = get_user_meta($user_id, 'state', true);
													$zip = get_user_meta($user_id, 'zip_code', true);
													$tax_id = get_user_meta($user_id, 'tax_id', true);
													$acct_descriptor = get_user_meta($user_id, 'acct_descriptor', true);
													$acceptance_date = time();
													$tos_ip = $_SERVER['REMOTE_ADDR'];
				
													if($connected_account){
					
															\Stripe\Stripe::setApiKey($api_key);
															$stripe_account = \Stripe\Account::retrieve($connected_account);
															  $stripe_account->external_account = $acct_token; 
															$stripe_account->save();
															wp_redirect( home_url()."/customer-dashboard/?tab=payment" );
													}
													else {
						
														\Stripe\Stripe::setApiKey($api_key);
														try {
															$stripe_account = \Stripe\Account::create(array(
															  "managed" => true,
															  "country" => "US",
															  "email" => $email,
															  "business_name"=> $company,
															   "statement_descriptor"=> $acct_descriptor,
															  "legal_entity"=> array(
																	"type"=>$org_type, 
																	"business_name"=>$company,
																	"first_name"=>$first_name, 
																	"last_name"=>$last_name,
																	"address"=>array(
																		"city"=>$city,
																		"line1"=>$street,
																		"postal_code"=>$zip,
																		"state"=>$state,
																		"country"=>"US"
																		),
																	"business_tax_id"=>$tax_id,
																	"ssn_last_4"=>$ssn_last4,
																	"dob"=>array(
																		"day"=>$dob_day, 
																		"month"=>$dob_month, 
																		"year"=>$dob_year
																	)
																),
															   "tos_acceptance"=> array(
																	"date"=>$acceptance_date,
																	"ip"=>$tos_ip
																),
															   "external_account"=>$acct_token
															   ));
														   }
															catch(\Stripe\Error\Card $e) {
																	// Since it's a decline, \Stripe\Error\Card will be caught
																	$body = $e->getJsonBody();
																	$err  = $body['error'];
																	$error=1;
																	$error_message = $err['message'];
																	} catch (\Stripe\Error\RateLimit $e) {
																	// Too many requests made to the API too quickly
																	$body = $e->getJsonBody();
																	$err  = $body['error'];
																	$error=1;
																	$error_message = $err['message'];
																	} catch (\Stripe\Error\InvalidRequest $e) {
																	// Invalid parameters were supplied to Stripe's API
																	$body = $e->getJsonBody();
																	$err  = $body['error'];
																	$error=1;
																	$error_message = $err['message'];
																	} catch (\Stripe\Error\Authentication $e) {
																	// Authentication with Stripe's API failed
																	// (maybe you changed API keys recently)
																	$body = $e->getJsonBody();
																	$err  = $body['error'];
																	$error=1;
																	$error_message = $err['message'];
																	} catch (\Stripe\Error\ApiConnection $e) {
																	// Network communication with Stripe failed
																	$body = $e->getJsonBody();
																	$err  = $body['error'];
																	$error=1;
																	$error_message = $err['message'];
																	} catch (\Stripe\Error\Base $e) {
																	// Display a very generic error to the user, and maybe send
																	// yourself an email
																	$body = $e->getJsonBody();
																	$err  = $body['error'];
																	$error=1;
																	$error_message = $err['message'];
																	} catch (Exception $e) {
																	// Something else happened, completely unrelated to Stripe
																	$body = $e->getJsonBody();
																	$err  = $body['error'];
																	$error=1;
																	$error_message = $err['message'];
															}
															
													$stripe_account_arr = $stripe_account->__toArray(true);
										
													if(get_option('rzr_stripe_test_mode')=="on") {
														$live_mode="";
													} else {
													$live_mode="1";
													}
													$resp = array (
														"stripe_user_id"=>$stripe_account_arr['id'],
														"scope"=>"",
														"livemode"=>$live_mode
													);
													$this->add_stripe_connected($resp);
													
													}
	
											
												}		
		wp_redirect( home_url()."/customer-dashboard/?tab=payment" );
				
		}
		
		function add_rzr_user() {

		global $current_user;
		$user_id = $current_user->ID;
		require 'stripe/vendor/autoload.php';

			if ( empty($_POST) || !wp_verify_nonce($_POST['rzr_nonce'],'add_rzr_user') ) {
			$error_message = 'Your form submission could not be processed. Please refresh the page and try again.';
			return $error_message;
			die();
			} 
			else {

				if ( email_exists($_POST['email']) == false ) {
				$user_name = $_POST['email'];
				$user_email = $_POST['email'];
				$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
				$user_id = wp_create_user( $user_name, $random_password, $user_email );
				wp_set_current_user($user_id);
				wp_set_auth_cookie($user_id);
				$user_creation = 1;
				}
	
				if($user_id){
					($_POST['first_name']!=''?update_user_meta($user_id, 'first_name', wp_strip_all_tags($_POST['first_name'])):'');
					($_POST['last_name']!=''?update_user_meta($user_id, 'last_name', wp_strip_all_tags($_POST['last_name'])):'');
					($_POST['phone']!=''?update_user_meta($user_id, 'phone', wp_strip_all_tags($_POST['phone'])):'');
					($_POST['company']!=''?update_user_meta($user_id, 'company', wp_strip_all_tags($_POST['company'])):'');
					($_POST['street']!=''?update_user_meta($user_id, 'street', wp_strip_all_tags($_POST['street'])):'');
					($_POST['city']!=''?update_user_meta($user_id, 'city', wp_strip_all_tags($_POST['city'])):'');
					($_POST['state']!=''?update_user_meta($user_id, 'state', wp_strip_all_tags($_POST['state'])):'');
					($_POST['zip_code']!=''?update_user_meta($user_id, 'zip_code', wp_strip_all_tags($_POST['zip_code'])):'');
					($_POST['twitter_handle']!=''?update_user_meta($user_id, 'twitter_handle', wp_strip_all_tags($_POST['twitter_handle'])):'');
					if($_POST['website']!=''){
						wp_update_user( array( 'ID' => $user_id, 'user_url'=> wp_strip_all_tags($_POST['website']) ) );	
					}
					$org_type = wp_strip_all_tags($_POST['org_type']);
					($org_type!=''?update_user_meta($user_id, 'org_type', $org_type):'');
					$tax_id = wp_strip_all_tags($_POST['tax_id']);
					($tax_id!=''?update_user_meta($user_id, 'tax_id', $tax_id):'');
					$dob_year = wp_strip_all_tags($_POST['dob_year']);
					($dob_year!=''?update_user_meta($user_id, 'dob_year', $dob_year):'');
					$dob_month = wp_strip_all_tags($_POST['dob_month']);
					($dob_month!=''?update_user_meta($user_id, 'dob_month', $dob_month):'');
					$dob_day = wp_strip_all_tags($_POST['dob_day']);
					($dob_day!=''?update_user_meta($user_id, 'dob_day', $dob_day):'');
					if(isset($_POST['ssn_last4'])){$ssn_last4 = wp_strip_all_tags($_POST['ssn_last4']);
					($ssn_last4!=''?update_user_meta($user_id, 'ssn_last4', $ssn_last4):'');
					}
					$acct_descriptor = wp_strip_all_tags($_POST['acct_descriptor']);
					($acct_descriptor!=''?update_user_meta($user_id, 'acct_descriptor', $acct_descriptor):'');
					if($user_creation==1){
						$this-> send_user_registration_details($user_email, $user_id, $random_password);
					}

					if(isset($_POST['accttoken'])) {
						$acct_token=$_POST['accttoken'];
						$this->create_stripe_managed($acct_token,$user_id);
					}
				}
				else { 
					if(email_exists($_POST['email']) == true) 
							{
							$error_message = "An account already exists with this e-mail. Please login with that account or try another e-mail.";
							return $error_message;
							}
				}
			}
		}
  		
  		function save_rzr_project() {
			if(get_post_type() == 'project' || $_POST['post_type'] == 'project')
			{

				remove_action('save_post', array(&$this, 'save_rzr_project'));
		
					if($_POST['submit']=='Submit for Review') {
						if(!wp_verify_nonce($_POST['rzr_nonce'],'add_rzr_project')) {
						$error_message = 'Your form submission could not be processed. Please refresh the page and try again.';
						return $error_message;
						die();
						}
						else {
						$my_post = array(
						  'post_title'    => wp_strip_all_tags( $_POST['project_title'] ),
						  'post_type'     => 'project',
						  'post_content'  => '',
						  'post_status'   => 'pending',
						  'posts_per_page' => -1,
						  'post_author'   => $current_user->ID
						  );
						$post_id = wp_insert_post($my_post);
						$this->send_creation_notification(wp_strip_all_tags( $_POST['project_title']));
						}
					}
					else {
							$post_id = $_POST['post_id'];
							$my_post = array(
								 'ID'           => $post_id,
								 'post_title'    => wp_strip_all_tags( $_POST['project_title'] ),
								  );

							wp_update_post($my_post);
					}
				add_action('save_post', array(&$this, 'save_rzr_project'));
		
				global $wpdb;
				if($post_id == ''){
					$post_id = get_the_ID();
				}

					$manual_pledge = wp_strip_all_tags($_POST['manual_pledge']);
					$manual_pledge = str_replace([':', '\\', '/', '*','$',','], '', $manual_pledge);
					if ($manual_pledge != '') {
						$user = wp_get_current_user();
						$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_projects_meta(`project_id`, `level_id`, `user_id`, `meta_key`, `meta_value`, `created_at`, `modified_at`) VALUES('".$post_id."', '99', '".$user->ID."', 'project_pledge', '".$manual_pledge."', NOW(), NOW())");  
					}
		
					$goal = wp_strip_all_tags($_POST['goal']);
					$goal = str_replace([':', '\\', '/', '*','$',','], '', $goal);
						if($goal ==''){ $goal = 0;}
						if(metadata_exists('post', $post_id, 'project_goal')){
						update_post_meta($post_id, 'project_goal', $goal);}
						else
						{add_post_meta($post_id, 'project_goal', $goal);}

					$enddate = wp_strip_all_tags($_POST['EndDate']);
						if(metadata_exists('post', $post_id, 'project_enddate'))
						update_post_meta($post_id, 'project_enddate', $enddate);
						else
							if ($enddate!='') {
						add_post_meta($post_id, 'project_enddate', $enddate);}
		
					$mailchimp_id = wp_strip_all_tags($_POST['mailchimp_id']);
						if(metadata_exists('post', $post_id, 'mailchimp_id'))
						update_post_meta($post_id, 'project_mailchimp', $mailchimp_id);
						else
						add_post_meta($post_id, 'project_mailchimp', $mailchimp_id);

					$project_location = wp_strip_all_tags($_POST['project_location']);
						if(metadata_exists('post', $post_id, 'project_location'))
						update_post_meta($post_id, 'project_location', $project_location);
						else
							if ($project_location!='') {
						add_post_meta($post_id, 'project_location', $project_location);}
		
					$short_description = $_POST['short_description'];
						if(metadata_exists('post', $post_id, 'project_short_description'))
						update_post_meta($post_id, 'project_short_description', $short_description);
						else
							if ($short_description!='') {
						add_post_meta($post_id, 'project_short_description', $short_description);}
		
					$project_description = $_POST['project_description'];
						if(metadata_exists('post', $post_id, 'project_long_description'))
						update_post_meta($post_id, 'project_long_description', $project_description);
						else
						add_post_meta($post_id, 'project_long_description', $project_description);

					for ($x=1; $x<9; $x++){
		
					$subscriptions = $wpdb->get_var("SELECT `id` FROM ".$wpdb->prefix."rzr_stripe_subscriptions WHERE project_id='".$post_id."' AND level_id='".$x."'");

					if (is_null($subscriptions)) {
			
						$level_amount = wp_strip_all_tags($_POST['level'.$x.'_amount']);
						$level_amount = str_replace([':', '\\', '/', '*','$',','], '', $level_amount);
						if(metadata_exists('post', $post_id, 'project_level_amount'.$x))
						   update_post_meta($post_id, 'project_level_amount'.$x, $level_amount);
						   else
							if ($level_amount!='') {
								add_post_meta($post_id, 'project_level_amount'.$x, $level_amount);}
					
						$leveltype = wp_strip_all_tags($_POST['leveltype'.$x]);
						if($leveltype != '' & $level_amount!='')
						{
						if(metadata_exists('post', $post_id, 'project_leveltype'.$x))
						   update_post_meta($post_id, 'project_leveltype'.$x, $leveltype);
						   else
							if ($leveltype!='') {
								add_post_meta($post_id, 'project_leveltype'.$x, $leveltype);}
						}

					
						$level_match_amount = wp_strip_all_tags($_POST['level'.$x.'_match_amount']);
						$level_match_amount = str_replace([':', '\\', '/', '*','$',','], '', $level_match_amount);
						if(metadata_exists('post', $post_id, 'project_level_match_amount'.$x))
						   update_post_meta($post_id, 'project_level_match_amount'.$x, $level_match_amount);
						   else
								add_post_meta($post_id, 'project_level_match_amount'.$x, $level_match_amount);


					if($leveltype == 'recurring')
					{
						$level_pmts = wp_strip_all_tags($_POST['level'.$x.'_pmts']);
						if(metadata_exists('post', $post_id, 'project_level_pmts'.$x))
						update_post_meta($post_id, 'project_level_pmts'.$x, $level_pmts);
						else
							if ($level_pmts!='') {
								add_post_meta($post_id, 'project_level_pmts'.$x, $level_pmts);}

					  $recurring_amount = $level_amount/$level_pmts;
						if(metadata_exists('post', $post_id, 'project_level_recurring'.$x))
						update_post_meta($post_id, 'project_level_recurring'.$x, $recurring_amount);
					 else
							if ($recurring_amount!='') {
								add_post_meta($post_id, 'project_level_recurring'.$x, $recurring_amount);}
					
						$level_future_start = wp_strip_all_tags($_POST['level'.$x.'_future_start']);
						if($level_future_start != '')
						if(metadata_exists('post', $post_id, 'project_level_future_start'.$x))
						update_post_meta($post_id, 'project_level_future_start'.$x, $level_future_start);
						  else
							if ($level_future_start!='') {
								add_post_meta($post_id, 'project_level_future_start'.$x, $level_future_start);}
			 
						$level_frequency = wp_strip_all_tags($_POST['level'.$x.'_frequency']);
						if(metadata_exists('post', $post_id, 'project_level_frequency'.$x))
						update_post_meta($post_id, 'project_level_frequency'.$x, $level_frequency);
						else
							if ($level_frequency!='') {
								add_post_meta($post_id, 'project_level_frequency'.$x, $level_frequency);}
					}
						$level_title = wp_strip_all_tags($_POST['level'.$x.'_title']);
						if(metadata_exists('post', $post_id, 'project_level_title'.$x))
					  update_post_meta($post_id, 'project_level_title'.$x, $level_title);
						   else
							if ($level_title!='') {
								add_post_meta($post_id, 'project_level_title'.$x, $level_title);}

						$level_description = wp_strip_all_tags($_POST['level'.$x.'_description']);
						if(metadata_exists('post', $post_id, 'project_level_description'.$x))
					 update_post_meta($post_id, 'project_level_description'.$x, $level_description);
						 else
							if ($level_description!='') {
								add_post_meta($post_id, 'project_level_description'.$x, $level_description);}
					
						$level_checkout_stmt = wp_strip_all_tags($_POST['level'.$x.'_checkout_stmt']);
						if(metadata_exists('post', $post_id, 'project_checkout_stmt'.$x))
					 update_post_meta($post_id, 'project_checkout_stmt'.$x, $level_checkout_stmt);
						 else
							add_post_meta($post_id, 'project_checkout_stmt'.$x, $level_checkout_stmt);}

					else {}
		
				}
	
					 for ($i=1; $i<21; $i++)
				 {
						$question = wp_strip_all_tags($_POST['question'.$i]);
						if(metadata_exists('post', $post_id, 'project_question'.$i))
						update_post_meta($post_id, 'project_question'.$i, $question);
							else
							if ($question!='') {
								add_post_meta($post_id, 'project_question'.$i, $question);}

							$answer = wp_strip_all_tags($_POST['answer'.$i]);
						if(metadata_exists('post', $post_id, 'project_answer'.$i))
						update_post_meta($post_id, 'project_answer'.$i, trim($answer));
					  else
							if ($answer!='') {
								add_post_meta($post_id, 'project_answer'.$i, trim($answer));}
				 }
				$embed_video =  $_POST['project_embed'];
						if(metadata_exists('post', $post_id, 'project_embed_video'))
						update_post_meta($post_id, 'project_embed_video', $embed_video);
						else
						add_post_meta($post_id, 'project_embed_video', $embed_video);
					
				if ( ! function_exists( 'wp_handle_upload' ) ) {
				    require_once( ABSPATH . 'wp-admin/includes/file.php' );
				}

				$uploadedfile = $_FILES['file_upload'];

				$upload_overrides = array( 'test_form' => false );

				$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

				if ( $movefile && ! isset( $movefile['error'] ) ) {

				    $parent_post_id = $post_id;
					$filename = $movefile['file'];
					// Check the type of file. We'll use this as the 'post_mime_type'.
					$filetype = wp_check_filetype( basename( $filename ), null );

					// Get the path to the upload directory.
					$wp_upload_dir = wp_upload_dir();

					// Prepare an array of post data for the attachment.
					$attachment = array(
						'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
						'post_mime_type' => $filetype['type'],
						'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
						'post_content'   => '',
						'post_status'    => 'inherit'
					);

					// Insert the attachment.
					$attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );

					// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
					require_once( ABSPATH . 'wp-admin/includes/image.php' );

					// Generate the metadata for the attachment, and update the database record.
					$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
					wp_update_attachment_metadata( $attach_id, $attach_data );

					set_post_thumbnail( $parent_post_id, $attach_id );

				} else {

				}

			  } 

			  if($_POST['submit']=='Submit for Review') {
					$dashboard_url = $this->get_template_url("customer-dashboard");
					wp_redirect($dashboard_url.'?message=created');
				}
				else {
					if($_POST['submit']=='Update Project') {							
						$project_url = $this->get_template_url("add_project");	  
						if (is_null($subscriptions)) {
							wp_redirect($project_url.'?message=updated&edit='.$post_id);
						} else {
							wp_redirect($project_url.'?message=lnu&edit='.$post_id);
						}
					}
				}
		  
		}
  		
  		function add_social_meta_tags() {
			global $post;
			if ( is_single() ) {
				$fb_app_id = get_option('rzr_social_facebook_app_id');
				$post_url = get_permalink($post->ID);
				$thumb_id = get_post_thumbnail_id($post);
				$thumb_url_array = wp_get_attachment_image_src($thumb_id, 'full', true);
				$thumb_url = $thumb_url_array[0];
				$thumb_width = get_option( 'thumbnail_size_w' );
				$thumb_height = get_option( 'thumbnail_size_h' );
				$social_text = get_option('rzr_social_share_text');
				$social_text = str_replace(['[project_title]'],$post->post_title,$social_text);
				$project_short_description = '';
				if($social_text == ''){
					$social_text = get_post_meta($post->ID, 'project_short_description', true);
				}

				if($fb_app_id != ''){
					echo '<meta property="fb:app_id" content="' .$fb_app_id. '" />'. "\n";
				}
				echo '<meta property="og:url" content="' .$post_url. '" />'. "\n";
				echo '<meta property="og:image" content="' .$thumb_url. '" />'. "\n";
				echo '<meta property="og:image:width" content="' .$thumb_width. '" />'. "\n";
				echo '<meta property="og:image:height" content="' .$thumb_height. '" />'. "\n";
				echo '<meta property="og:title" content="' .$post->post_title. '" />'. "\n";
				echo '<meta property="og:description" content="' .$social_text. '" />';
			}
		}
		
		function rzr_export_transfer() {
			global $wpdb;
			global $current_user;
			$user_id = $current_user->ID;
			$transfer_id = $_POST['transfer_id'];
		
			require 'stripe/vendor/autoload.php';
			$stripe_keys = $this->get_stripe_keys();
			$api_key = $stripe_keys['stripe_secret_key'];
			\Stripe\Stripe::setApiKey($api_key);
			$connected_account = $this->get_connected_stripe_account($user_id);
					 if($connected_account) {
						$transfer_detail = \Stripe\BalanceTransaction::all(
						array("transfer" => $transfer_id,
						"expand" => array("data.source")),
						array("stripe_account" => $connected_account));
						}
					  else {
						$transfer_detail = \Stripe\BalanceTransaction::all(
						array("transfer" => $transfer_id,
						"expand" => array("data.source")));
						}
			header("Content-type: text/csv");
			header("Content-Disposition: attachment; filename=monthly_transfer.csv");
			header("Pragma: no-cache");
			header("Expires: 0");
			echo 'Created,Customer Name,Customer Email,Description,Type,Amount,Fee,Net'."\n";
			$total_amount = 0;
			$total_fee = 0;
			$total_net = 0;
			foreach ($transfer_detail->data as $transfer_transaction)
			{ 
			$created = $transfer_transaction->created;
			$id = $transfer_transaction->id; 
			$description = $transfer_transaction->description;
			$type = $transfer_transaction->type;
			$amount = $transfer_transaction->amount/100; 
			$fee = $transfer_transaction->fee/100;
			$net = $transfer_transaction->net/100;
			$customer = $transfer_transaction->source->customer;
			$wp_user_id = $wpdb->get_var("SELECT `user_id` FROM ".$wpdb->prefix."rzr_stripe_users WHERE `stripe_user_id`='".$customer."'");	
			$user = get_userdata($wp_user_id);
			$customer_name = get_user_meta($wp_user_id, 'first_name', true).' '.get_user_meta($wp_user_id, 'last_name', true);
			$customer_email = $user->user_email;
		
			if($description != "STRIPE TRANSFER") {
				echo date('m/d/Y',$created).', '.$customer_name.', '.$customer_email.', '.$description.', '.$type.', $'.$amount.', $'.$fee.', $'.$net."\n";
						$total_amount = $amount + $total_amount;
						$total_fee = $total_fee + $fee;
						$total_net = $total_net + $net;
				}
			}
			echo ', , , , Totals, $'.$total_amount.', $'.$total_fee.', $'.$total_net;
			die;
			
			$report_dashboard_url = $this->get_template_url("reporting-dashboard");
			wp_redirect($report_dashboard_url);
		}
		
		function update_card_details(){
		
					$token= $_POST['stripeToken'];
					$redirect= $_POST['redirect'];
					$error='';
					$connected_account = $_POST['connected_account'];
					$customer_id = $_POST['customer'];
					require 'stripe/vendor/autoload.php';
					$stripe_keys = $this->get_stripe_keys();
					$api_key = $stripe_keys['stripe_secret_key'];
					\Stripe\Stripe::setApiKey($api_key);
					
					if ($connected_account) {
						$cu = \Stripe\Customer::retrieve(
							array("id" => $customer_id, "expand" => array("default_source")),
					    	array("stripe_account" => $connected_account)
						);
						$cu->source = $token; // obtained with Stripe.js
						$cu->save();
						$message= "success";
					}
					
					else {
						try {
							$cu = \Stripe\Customer::retrieve($customer_id);
							$cu->source = $token; // obtained with Stripe.js
							$cu->save();
							$message = "success";
						} catch(\Stripe\Error\Card $e) {
  								// Since it's a decline, \Stripe\Error\Card will be caught
  								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\RateLimit $e) {
								// Too many requests made to the API too quickly
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\InvalidRequest $e) {
								// Invalid parameters were supplied to Stripe's API
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\Authentication $e) {
								// Authentication with Stripe's API failed
								// (maybe you changed API keys recently)
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\ApiConnection $e) {
								// Network communication with Stripe failed
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\Base $e) {
								// Display a very generic error to the user, and maybe send
								// yourself an email
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (Exception $e) {
								// Something else happened, completely unrelated to Stripe
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
							}
							
					}
				if($error!=1) {
					$message="success";
					$redirect_url = $this->get_template_url($redirect);
					if($redirect=="customer-dashboard"){
					wp_redirect($redirect_url."?tab=payment&message=".$message);
					}
					else {
					wp_redirect($redirect_url."?message=".$message);
					}
				}
				else {
					$message="error";
					$redirect_url = $this->get_template_url($redirect);
					if($redirect=="customer-dashboard"){
					wp_redirect($redirect_url."?tab=payment&message=".$message);
					}
					else {
					wp_redirect($redirect_url."?message=".$message);
					}
				}
		}
		
		function get_template_url($template=0){
			$args = [
			'post_type' => 'page',
			'fields' => 'ids',
			'nopaging' => true,
			'meta_key' => '_wp_page_template',
			'meta_value' => 'templates/'. $template .'.php'
			];
			$pages = get_posts( $args );
			$url = null;
			if(isset($pages[0])) {
			foreach ( $pages as $page ) 
			$url = get_page_link($page);
			}
			
			return $url;
		}
		
		function get_stripe_transfers(){
			global $current_user;
			$user_id = $current_user->ID;
				require 'stripe/vendor/autoload.php';
				$stripe_keys = $this->get_stripe_keys();
				$api_key = $stripe_keys['stripe_secret_key'];
				\Stripe\Stripe::setApiKey($api_key);
				$connected_account = $this->get_connected_stripe_account($user_id);
		         if($connected_account) {
					$listOfTransfers = \Stripe\Transfer::all(
					array("limit" => 3),
					array("stripe_account" => $connected_account));
					}
				  else {
				    $listOfTransfers = \Stripe\Transfer::all(
					array("limit" => 3));
				    }
			return $listOfTransfers;
		}
		
		function get_new_donors () {
			global $wpdb;
			global $current_user;
			$user_id = $current_user->ID;
			$args = array(
				'post_type' => 'project',
				'post_status' => 'publish',
				'author' 	  => $user_id,
				'numberposts' => -1
			);
			$donor_posts = get_posts($args);

			$donor_project_id = array();
			foreach($donor_posts as $donor_single_post)
			{
				$donor_project_id[] = $donor_single_post->ID;
			}
			if(count($donor_project_id)>0)
			{
				$donor_projects = implode(',',$donor_project_id);
			}
			else
			{
				$donor_projects = 0;
			}
			$new_donors = $wpdb->get_results("SELECT `user_id`, `project_id`, `level_id` FROM (SELECT * FROM `".$wpdb->prefix."rzr_projects_meta` WHERE `meta_key`='project_pledge' AND `project_id` IN (".$donor_projects.") ORDER BY `created_at` DESC) AS tmp_table LIMIT 4");
			
			return $new_donors;
		}
		
		function get_donor_payment($project_id=0, $user_id=0){
			global $wpdb;
			$donor_payment = $wpdb->get_var("SELECT `meta_value` FROM ".$wpdb->prefix."rzr_projects_meta WHERE `project_id`='".$project_id."' AND `user_id`='".$user_id."' AND `meta_key`='project_payment_date'");
			return $donor_payment;
		}
		
		function get_stripe_balanced () {
			global $current_user;
			$user_id = $current_user->ID;
			$stripe_keys = $this->get_stripe_keys();
			$api_key = $stripe_keys['stripe_secret_key'];
			require 'stripe/vendor/autoload.php';
			\Stripe\Stripe::setApiKey($api_key);
			$connected_account = $this->get_connected_stripe_account($user_id);
		         if($connected_account) {
					$stripe_balance = \Stripe\BalanceTransaction::all(
					array("expand" => array("data.source")),
					array("stripe_account" => $connected_account));
					}
				  else {
				    $stripe_balance = \Stripe\BalanceTransaction::all(
					array("expand" => array("data.source")));
				    }
			return $stripe_balance;
		}
		
		function get_customer_subscriptions($customer=0){
			global $wpdb;
			$subscriptions = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."rzr_stripe_subscriptions WHERE `customer`='".$customer."'");
			return $subscriptions;
		}
		
		function get_customer_wpuser($customer=0){
			global $wpdb;
			$wp_user_id = $wpdb->get_var("SELECT `user_id` FROM ".$wpdb->prefix."rzr_stripe_users WHERE `stripe_user_id`='".$customer."'");	
			return $wp_user_id;
		}
		
		function get_stripe_subscriptions(){
				global $current_user;
				$user_id = $current_user->ID;
				require 'stripe/vendor/autoload.php';
				$stripe_keys = $this->get_stripe_keys();
				$api_key = $stripe_keys['stripe_secret_key'];
				\Stripe\Stripe::setApiKey($api_key);
				
				$connected_account = $this->get_connected_stripe_account($user_id);
		         if($connected_account) {
					$subscriptions = \Stripe\Subscription::all (
					array("expand" => array("data.customer")),
					array("stripe_account" => $connected_account));
					}
				  else {
				    $subscriptions = \Stripe\Subscription::all (
					array("expand" => array("data.customer")));
				    }
			  return $subscriptions;
		}
		
		function update_customer_profile () {
			global $current_user;
			$user_id=$current_user->ID;
			($_POST['first_name']!=''?update_user_meta($user_id, 'first_name', $_POST['first_name']):'');
			($_POST['last_name']!=''?update_user_meta($user_id, 'last_name', $_POST['last_name']):'');
			($_POST['phone']!=''?update_user_meta($user_id, 'phone', $_POST['phone']):'');
			($_POST['company']!=''?update_user_meta($user_id, 'company', $_POST['company']):'');
			($_POST['display_name_type']!=''?update_user_meta($user_id, 'display_name_type', $_POST['display_name_type']):'');
			($_POST['street']!=''?update_user_meta($user_id, 'street', $_POST['street']):'');

			if(!empty($_FILES))
			{

				if ( ! function_exists( 'wp_handle_upload' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/file.php' );
				}

				$uploadedfile = $_FILES['profile_upload'];

				$upload_overrides = array( 'test_form' => false );

				$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

				if ( $movefile && ! isset( $movefile['error'] ) ) {
					//echo "File is valid, and was successfully uploaded.\n";
					$filename = $movefile['file'];
					// Check the type of file. We'll use this as the 'post_mime_type'.
					$filetype = wp_check_filetype( basename( $filename ), null );

					// Get the path to the upload directory.
					$wp_upload_dir = wp_upload_dir();

					// Prepare an array of post data for the attachment.
					$guid_link = $wp_upload_dir['url'] . '/' . basename( $filename );
					$attachment = array(
						'guid'           => $guid_link, 
						'post_mime_type' => $filetype['type'],
						'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
						'post_content'   => '',
						'post_status'    => 'inherit'
					);

					// Insert the attachment.
					$attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );

					// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
					require_once( ABSPATH . 'wp-admin/includes/image.php' );

					// Generate the metadata for the attachment, and update the database record.
					$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
					wp_update_attachment_metadata( $attach_id, $attach_data );

					$cupp_meta = $_FILE['profile_upload']['name'];
					$cupp_upload_meta = $guid_link;
					$cupp_upload_edit_meta = home_url().'/wp-admin/post.php?post='+$attach_id+'&action=edit&image-editor';

				} else {
					/**
					 * Error generated by _wp_handle_upload()
					 * @see _wp_handle_upload() in wp-admin/includes/file.php
					 */
					//echo $movefile['error'];
				}

			}


			if($_POST['email']!='')
			{
				$args = array(
					'ID' => $user_id,
					'user_email' => $_POST['email']
				);
				wp_update_user($args);
			}
			($_POST['city']!=''?update_user_meta($user_id, 'city', $_POST['city']):'');
			($_POST['state']!=''?update_user_meta($user_id, 'state', $_POST['state']):'');
			($_POST['zip_code']!=''?update_user_meta($user_id, 'zip_code', $_POST['zip_code']):'');
			($_POST['twitter_handle']!=''?update_user_meta($user_id, 'twitter_handle', $_POST['twitter_handle']):'');
			if($_POST['website']!=''){
				wp_update_user( array( 'ID' => $user_id, 'user_url'=> $_POST['website'] ) );	
			}
			// user profile code
			$values = array(
				// String value. Empty in this case.
				'cupp_meta'             => $cupp_meta,
				'cupp_upload_meta'      => $cupp_upload_meta,
				'cupp_upload_edit_meta' => '123',
			);

			foreach ( $values as $key => $value ) {
				update_user_meta( $user_id, $key, $value );
			}
			wp_redirect(home_url()."/customer-dashboard/?tab=profile");
			die;
		}

		function cancel_subscription($id=0, $connected_account=0) {
			global $wpdb;
			$subscription_id = $wpdb->get_var("SELECT `subscription_id` FROM ".$wpdb->prefix."rzr_stripe_subscriptions WHERE id=".$id);
			if($subscription_id!='')
			{	
				require 'stripe/vendor/autoload.php';
				$stripe_keys = $this->get_stripe_keys();
				$api_key = $stripe_keys['stripe_secret_key'];
				\Stripe\Stripe::setApiKey($api_key);
				if($connected_account){
					$subscription = \Stripe\Subscription::retrieve(array("id" => $subscription_id), array("stripe_account" => $connected_account));
					$subscription->cancel();
					$wpdb->query("DELETE FROM ".$wpdb->prefix."rzr_stripe_subscriptions WHERE id=".$id);
					if($wpdb->rows_affected >0)
					return "success"; }
				else {
					$subscription = \Stripe\Subscription::retrieve($subscription_id);
					$subscription->cancel();
					$wpdb->query("DELETE FROM ".$wpdb->prefix."rzr_stripe_subscriptions WHERE id=".$id);
					if($wpdb->rows_affected >0)
					return "success"; }
			}
		}
		
		function get_recurring (){
			global $current_user;
			global $wpdb;
			$user_id=$current_user->ID;
			$recurring = $wpdb->get_results("SELECT sp.id, `level_id`, `project_id`, post_author FROM ".$wpdb->prefix."rzr_stripe_subscriptions sp LEFT JOIN ".$wpdb->prefix."posts p ON sp.project_id=p.id WHERE `user_id`=".$user_id." ORDER BY `created_on` DESC");
			
			return $recurring;
		}
		
		function process_checkout_step_2(){

			if(!empty($_POST) & !is_user_logged_in()){
		          $prid = $_POST['prid'];
		          $level_id = $_POST['level_id'];
		          $fname = (isset($_POST['fname'])?$_POST['fname']:'');
		          $lname = (isset($_POST['lname'])?$_POST['lname']:'');
		          $trial_end = (isset($_POST['date'])?strtotime($_POST['date']):'');
		          $email = (isset($_POST['email'])?$_POST['email']:'');
		          $phone = (isset($_POST['phone'])?$_POST['phone']:'');
		          $password  = (isset($_POST['password'])?$_POST['password']:'');
		          $confirm_password = (isset($_POST['confirm_password'])?$_POST['confirm_password']:'');
		          $street = (isset($_POST['street'])?$_POST['street']:'');
		          $city = (isset($_POST['city'])?$_POST['city']:'');
		          $state = (isset($_POST['state'])?$_POST['state']:'');
		          $zip = (isset($_POST['zip'])?$_POST['zip']:'');
		          
		          $user_id = username_exists( $email );
		          if ( !$user_id and email_exists($email) == false ) {
		            	$user_id = wp_create_user( $email, $password, $email );
		            	if($user_id)
						{
						  update_user_meta($user_id, 'first_name', $fname);
						  update_user_meta($user_id, 'last_name', $lname);
						  add_user_meta($user_id, 'phone', $phone);
						  add_user_meta($user_id, 'street', $street);
						  add_user_meta($user_id, 'city', $city);
						  add_user_meta($user_id, 'state', $state);
						  add_user_meta($user_id, 'zip', $zip);

						  $creds = array();
						  $creds['user_login'] = $email;
						  $creds['user_password'] = $password;
						  $creds['remember'] = true;
						  $user = wp_signon( $creds, false );
						  if ( is_wp_error($user) )
							echo $user->get_error_message();
						  else
						  {
							wp_redirect(get_permalink($prid).'?level='.$level_id.'&checkout=step3&trial_end='.$trial_end);
							exit();
						  }
		            	}
		          } else {
		            $error = __('User already exists.  Password inherited.');
		          }
		          
		        }
		        else {

		        if(!empty($_POST) & is_user_logged_in()){
		        	$prid = $_POST['prid'];
		        	$trial_end = (isset($_POST['date'])?strtotime($_POST['date']):'');
		        	wp_redirect(get_permalink($prid).'?level='.$level_id.'&checkout=step3&trial_end='.$trial_end);
		        	exit();
		        	}
		    }
		}
		
		function process_checkout_step_3(){
			if(isset($_POST['stripeToken']) || isset($_POST['public_token']))
		    {	
		    	if(isset($_POST['public_token'])){
						$public_token = $_POST['public_token'];
						$account_id = $_POST['account_id'];
						if(get_option('rzr_stripe_test_mode') == "on"){
								$plaid_url = 'https://tartan.plaid.com/exchange_token';
						} else {
								$plaid_url = 'https://api.plaid.com/exchange_token';
						}
						$ch = curl_init();
						curl_setopt_array($ch, array(
							CURLOPT_RETURNTRANSFER => true,
							//swap out tartan for production based on test mode
							CURLOPT_URL => $plaid_url,
							CURLOPT_POST => true,
							CURLOPT_HTTPHEADER, array(
								'Content-Type: application/x-www-form-urlencoded',
							),
							CURLOPT_POSTFIELDS => http_build_query(array(
								'client_id' => get_option('rzr_plaid_client_id'),
								'secret' => get_option('rzr_plaid_secret'),
								'public_token' => $public_token,
								'account_id' => $account_id,
							)),
						));
						$response = json_decode(curl_exec($ch),true);
						curl_close($ch);
						$token = $response['stripe_bank_account_token'];
				} 
				else {
						$token = $_POST['stripeToken'];
				}
				
				global $current_user;
				global $wpdb;
				$level_id = $_POST['level_id'];
				$prid = $_POST['prid'];
				$pr = get_post($prid);
				$level_title = get_post_meta($prid, 'project_level_title'.$level_id, true);
				$level_checkout_stmt = get_post_meta($prid, 'project_checkout_stmt'.$level_id, true);
				$level_description = get_post_meta($prid, 'project_level_description'.$level_id, true);
				$level_amount = (get_post_meta($prid, 'project_level_amount'.$level_id, true)!=''?get_post_meta($prid, 'project_level_amount'.$level_id, true):0);
				$level_type = (get_post_meta($prid, 'project_leveltype'.$level_id, true)!=''?get_post_meta($prid, 'project_leveltype'.$level_id, true):'one-time');
				if($level_type == 'recurring')
				{
				  $recurring = get_post_meta($prid, 'project_level_recurring'.$level_id, true);
				  $level_pmts = get_post_meta($prid, 'project_level_pmts'.$level_id, true);
				  $level_freq = get_post_meta($prid, 'project_level_frequency'.$level_id, true);
				}

				$fn = get_user_meta($current_user->ID, 'first_name', true);
				$ln = get_user_meta($current_user->ID, 'last_name', true);
				$connected_fees = get_option('rzr_stripe_fee_amount');
				
				require 'stripe/vendor/autoload.php';
				$stripe_keys = $this->get_stripe_keys();
				$api_key = $stripe_keys['stripe_secret_key'];
				$publishable_key = $stripe_keys['stripe_publishable_key'];	
				\Stripe\Stripe::setApiKey($api_key);
				
		    	
		        $level_amount = ($_POST['level_amount']!=''?$_POST['level_amount']:0);
		        $first_name = get_user_meta($current_user->ID, 'first_name', true);
		        $last_name = get_user_meta($current_user->ID, 'last_name', true);
		         
		         if($_POST['display_name_type']!='') {
		         $display_name = get_user_meta($current_user->ID, 'display_name_type', true);
		         
					 if($display_name){
						update_user_meta($current_user->ID, 'display_name_type', $_POST['display_name_type']);
						}
						else {
						add_user_meta($current_user->ID, 'display_name_type', $_POST['display_name_type']);
						}
		         }
		         
		         for ($x=1; $x<7; $x++)
				 { 
		         	if(isset($_POST['rzr_custom_meta_'.$x])) {
		         	$rzr_custom_meta = get_user_meta($current_user->ID, 'rzr_custom_meta_'.$x, true);
		         
		         	if($rzr_custom_meta){
		         		update_user_meta($current_user->ID, 'rzr_custom_meta_'.$x, $_POST['rzr_custom_meta_'.$x]);
		         		}
		         		else {
		         		add_user_meta($current_user->ID, 'rzr_custom_meta_'.$x, $_POST['rzr_custom_meta_'.$x]);
		         		}
		         	}
				 }
		         $connected_account = $this->get_connected_stripe_account($pr->post_author);
		         if($connected_account)
		         {
		         	$stripe_user_id = $wpdb->get_var("SELECT `stripe_user_id` FROM ".$wpdb->prefix."rzr_stripe_users WHERE `user_id`=".$current_user->ID." AND `connected`='".$connected_account."'");	
		         }
		         else
		         {
		         	$stripe_user_id = $wpdb->get_var("SELECT `stripe_user_id` FROM ".$wpdb->prefix."rzr_stripe_users WHERE `user_id`=".$current_user->ID);		
		         }
		         
		         if($_POST['recurring']!='')
		          {
		            $level_pmts = get_post_meta($prid, 'project_level_pmts'.$level_id, true);
		            $trial_end = ($_GET['trial_end']!=''?$_GET['trial_end']:'');
		            $recurring = $_POST['recurring'];

					if($level_freq == 'yearly') {
						$interval = 'year'; } 
						else { 
							if($level_freq == 'monthly'){
							$interval = 'month'; }
							else {
							$interval = 'week'; }
						}
		            if($connected_account)
		            {
		            	$strip_plan_id = $wpdb->get_var("SELECT `stripe_plan_id` FROM ".$wpdb->prefix."rzr_stripe_plans WHERE project_id='".$prid."' AND level_id='".$level_id."' AND connected='".$connected_account."'");
		            }
		            else
		            {
		            	$strip_plan_id = $wpdb->get_var("SELECT `stripe_plan_id` FROM ".$wpdb->prefix."rzr_stripe_plans WHERE project_id='".$prid."' AND level_id='".$level_id."'");
		            }

		            if($stripe_user_id=='')
		            {
		              if($connected_account)
		              {
		              	try{
							$customer = \Stripe\Customer::create(array(
							"source" => $token,
							'description' => $first_name." ".$last_name,
							"email" => $current_user->data->user_email),
							array("stripe_account" => $connected_account)
							);	
						}
		              	catch(\Stripe\Error\Card $e) {
  								// Since it's a decline, \Stripe\Error\Card will be caught
  								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\RateLimit $e) {
								// Too many requests made to the API too quickly
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\InvalidRequest $e) {
								// Invalid parameters were supplied to Stripe's API
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\Authentication $e) {
								// Authentication with Stripe's API failed
								// (maybe you changed API keys recently)
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\ApiConnection $e) {
								// Network communication with Stripe failed
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\Base $e) {
								// Display a very generic error to the user, and maybe send
								// yourself an email
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (Exception $e) {
								// Something else happened, completely unrelated to Stripe
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								}
		              	
		              }
		              else
		              {
		              	try {
							$customer = \Stripe\Customer::create(array(
							"source" => $token,
							'description' => $first_name." ".$last_name,
							"email" => $current_user->data->user_email)
							);	
							}
		              	catch(\Stripe\Error\Card $e) {
  								// Since it's a decline, \Stripe\Error\Card will be caught
  								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\RateLimit $e) {
								// Too many requests made to the API too quickly
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\InvalidRequest $e) {
								// Invalid parameters were supplied to Stripe's API
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\Authentication $e) {
								// Authentication with Stripe's API failed
								// (maybe you changed API keys recently)
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\ApiConnection $e) {
								// Network communication with Stripe failed
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\Base $e) {
								// Display a very generic error to the user, and maybe send
								// yourself an email
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (Exception $e) {
								// Something else happened, completely unrelated to Stripe
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								}
		              }
		             
		              if($error!=1){ $customer_arr = $customer->__toArray(true); }
		              if(!empty($customer_arr))
		              {
		                if($customer_arr['id']!='')
		                {
		                  $strip_plan_id = $pr->post_name.'_level'.$level_id;
				          try {
				          if($strip_plan_id == '')
		              	  {
		              	  	if($connected_account)
		              	  	{
		              	  		$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_plans(`project_id`, `level_id`, `stripe_plan_id`, `connected`, `created_on`) VALUES('".$prid."', '".$level_id."', '".$strip_plan_id."', '".$connected_account."', NOW())");
		              	  	}
		              	  	else
		              	  	{
		              	  		$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_plans(`project_id`, `level_id`, `stripe_plan_id`, `created_on`) VALUES('".$prid."', '".$level_id."', '".$strip_plan_id."', NOW())");
		              	  	}
		              	  	
		              	  }

		              	  if($connected_account)
		              	  {
		              	  	$args_stripe = array(
				                "customer" => $customer_arr['id'],
		                    	"plan" => $strip_plan_id,
		                    	'application_fee_percent' => $connected_fees,
		                    	"metadata" => array("project_id" => $prid, "level_id"=>$level_id)
				              );
							if($trial_end!='' && date('d-m-Y')!=date('d-m-Y', $trial_end))
							{
								$args_stripe['trial_end'] = $trial_end;
							}	              	  	
		              	  	$return = \Stripe\Subscription::create($args_stripe,
		                    	array("stripe_account" => $connected_account)
		              	  	);	
		              	  }
		              	  else
		              	  {
		              	  	$args = array(
				                "customer" => $customer_arr['id'],
		                    	"plan" => $strip_plan_id,
		                    	"metadata" => array("project_id" => $prid, "level_id"=>$level_id));

		                    if($trial_end!='' && date('d-m-Y')!=date('d-m-Y', $trial_end))
							{
								$args['trial_end'] = $trial_end;
							}
		              	  	$return = \Stripe\Subscription::create($args);
		              	  }
			              

				           $subscription_return = $return->__toArray(true);
				          
				            }
				            catch (Exception $e) {
				            	if($connected_account)
				            	{
				            		$plan = \Stripe\Plan::create(array(
					                "amount" => round($recurring,2)*100,
					                "interval" => $interval,
					                "name" => $pr->post_title.' : level '.$level_id,
					                "currency" => "usd",
					                "id" => $pr->post_name.'_level'.$level_id),
					                array("stripe_account" => $connected_account)
					              );
				            	}
				            	else
				            	{
				            		$plan = \Stripe\Plan::create(array(
					                "amount" => round($recurring,2)*100,
					                "interval" => $interval,
					                "name" => $pr->post_title.' : level '.$level_id,
					                "currency" => "usd",
					                "id" => $pr->post_name.'_level'.$level_id)
					              );
				            	}

				            	
					              $plan_arr = $plan->__toArray(true);

						           if (!empty($plan_arr)) {
						           	if($connected_account)
						           	{
						             $wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_plans(`project_id`, `level_id`, `stripe_plan_id`, `connected`, `created_on`) VALUES('".$prid."', '".$level_id."', '".$plan_arr['id']."', '".$connected_account."', NOW())");
						           	}
						           	else
						           	{
						           		$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_plans(`project_id`, `level_id`, `stripe_plan_id`, `created_on`) VALUES('".$prid."', '".$level_id."', '".$plan_arr['id']."', NOW())");	
						           	}
					          	 }

				             	if($connected_account)
				              	  {
				              	  	$args_stripe = array(
						                "customer" => $customer_arr['id'],
				                    	"plan" => $strip_plan_id,
				                    	'application_fee_percent' => $connected_fees,
				                    	"metadata" => array("project_id" => $prid, "level_id"=>$level_id)
						              );
				              	  	if($trial_end!='' && date('d-m-Y')!=date('d-m-Y', $trial_end))
				              	  	{
				              	  		$args_stripe['trial_end'] = $trial_end;
				              	  	}
				              	  	$return = \Stripe\Subscription::create($args_stripe,
				                    	array("stripe_account" => $connected_account)
				              	  	);	
				              	  }
				              	  else
				              	  {
				              	  	$args = array(
						                "customer" => $customer_arr['id'],
				                    	"plan" => $strip_plan_id,
				                    	"metadata" => array("project_id" => $prid, "level_id"=>$level_id)
				                    	);
				              	  	if($trial_end!='' && date('d-m-Y')!=date('d-m-Y', $trial_end))
				              	  	{
				              	  		$args['trial_end'] = $trial_end;
				              	  	}
				              	  	$return = \Stripe\Subscription::create($args);
				              	  }
					              
								$subscription_return = $return->__toArray(true);

				           }
				           if($connected_account)
				           {
				          		$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_users(`user_id`, `stripe_user_id`, `connected`, `created_on`) VALUES(".$current_user->ID.", '".$customer_arr['id']."', '".$connected_account."', NOW())"); 	
				           }
				           else
				           {
				           		$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_users(`user_id`, `stripe_user_id`, `created_on`) VALUES(".$current_user->ID.", '".$customer_arr['id']."', NOW())"); 
				           }
							$project_level_amt = get_post_meta($prid, 'project_level_amount'.$level_id, true);
							$project_level_match_amount = get_post_meta($prid, 'project_level_match_amount'.$level_id, true);
							$pledge_recurring = $subscription_return['plan']['amount']/100;
							$time = ($trial_end!=''?$trial_end:time());
							$stripe_user_id = $customer_arr['id'];
							
							//recurring pledge new customer project meta rzrprojectmeta
							$this-> add_project_pledge($prid, $current_user->ID, 'project_pledge', $project_level_amt, $level_id);
				           	$this-> add_project_pledge($prid, $current_user->ID, 'project_pledge_recurring', $pledge_recurring, $level_id);
				           	$this->update_project_meta($prid, $current_user->ID, 'project_payment_date', $time, $level_id);
		                   	$this->update_project_meta($prid, $current_user->ID, 'project_pledge_start', $time, $level_id);
		                   	$this->update_project_meta($prid, $current_user->ID, 'project_remaining_payments', --$level_pmts, $level_id);
		                   	$this-> send_owner_customer_checkout($pr->post_author, $pr->post_title, $level_title, $project_level_amt);
		                   	if ($project_level_match_amount != '') {$this->add_project_pledge($prid, $current_user->ID, 'project_match', $project_level_match_amount, $level_id);}
		                   	
		                   	if($connected_account)
		                  {
		                  	$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_subscriptions(`level_id`, `level_title`, `level_amount`, `user_id`, `project_id`, `subscription_id`, `object`, `application_fee_percent`, `cancel_at_period_end`, `canceled_at`, `created`, `current_period_end`, `current_period_start`, `customer`, `discount`, `ended_at`, `quantity`, `start`, `status`, `tax_percent`, `trial_end`, `trial_start`, `connected`, `created_on`) VALUES('".$level_id."', '".$subscription_return['plan']['object']."', '".$subscription_return['plan']['amount']."', ".$current_user->ID.",  '".$prid."',  '".$subscription_return['id']."', '".$subscription_return['object']."', '".$subscription_return['application_fee_percent']."', '".$subscription_return['cancel_at_period_end']."', '".$subscription_return['canceled_at']."', '".$subscription_return['created']."', '".$subscription_return['current_period_end']."', '".$subscription_return['current_period_start']."', '".$subscription_return['customer']."', '".$subscription_return['discount']."', '".$subscription_return['ended_at']."', '".$subscription_return['quantity']."', '".$subscription_return['start']."', '".$subscription_return['status']."', '".$subscription_return['tax_percent']."', '".$subscription_return['trial_end']."', '".$subscription_return['trial_start']."', '".$connected_account."', NOW())");
		                  }
		                  else
		                  {
		                  	$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_subscriptions(`level_id`, `level_title`, `level_amount`, `user_id`, `project_id`, `subscription_id`, `object`, `application_fee_percent`, `cancel_at_period_end`, `canceled_at`, `created`, `current_period_end`, `current_period_start`, `customer`, `discount`, `ended_at`, `quantity`, `start`, `status`, `tax_percent`, `trial_end`, `trial_start`, `created_on`) VALUES('".$level_id."', '".$subscription_return['plan']['object']."', '".$subscription_return['plan']['amount']."', ".$current_user->ID.",  '".$prid."',  '".$subscription_return['id']."', '".$subscription_return['object']."', '".$subscription_return['application_fee_percent']."', '".$subscription_return['cancel_at_period_end']."', '".$subscription_return['canceled_at']."', '".$subscription_return['created']."', '".$subscription_return['current_period_end']."', '".$subscription_return['current_period_start']."', '".$subscription_return['customer']."', '".$subscription_return['discount']."', '".$subscription_return['ended_at']."', '".$subscription_return['quantity']."', '".$subscription_return['start']."', '".$subscription_return['status']."', '".$subscription_return['tax_percent']."', '".$subscription_return['trial_end']."', '".$subscription_return['trial_start']."', NOW())");
		                  }
		                   
		                   // Query to insert the stripe payment details
		                   if($connected_account)
		                   {
		                   	$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_payments(`user_id`, `project_id`, `level_id`, `payment`, `connected`, `created_on`) VALUES('".$current_user->ID."', '".$prid."', '".$level_id."', '".($subscription_return['plan']['amount']/100)."', '".$connected_account."', NOW())");
		                   }
		                   else
		                   {
							$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_payments(`user_id`, `project_id`, `level_id`, `payment`, `created_on`) VALUES('".$current_user->ID."', '".$prid."', '".$level_id."', '".($subscription_return['plan']['amount']/100)."', NOW())");

		                   }
		                   	
				            $this-> add_mailchimp_subscriber($prid, $current_user->user_email);
				            
				           wp_redirect(get_permalink($prid).'?level='.$level_id.'&checkout=final');
		                   die;
		                }
		              }

		            }
		            else
		            {
						//else for customer ID exists recurring
						
		              	  if($strip_plan_id == '')
		              	  {
		              	  	
		              	  	$strip_plan_id = $pr->post_name.'_level'.$level_id;

		              	  	if($connected_account)
		              	  	{
		              	  		$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_plans(`project_id`, `level_id`, `stripe_plan_id`, `connected`, `created_on`) VALUES('".$prid."', '".$level_id."', '".$strip_plan_id."', '".$connected_account."', NOW())");
		              	  	}
		              	  	else
		              	  	{
		              	  		$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_plans(`project_id`, `level_id`, `stripe_plan_id`, `created_on`) VALUES('".$prid."', '".$level_id."', '".$strip_plan_id."', NOW())");
		              	  	}
		              	  	
		              	  }
		              	  
		              	  try
		              	  {
		            	  
		              	  	  if($connected_account)
			              	  {
			              	  	//die($stripe_user_id);
			              	  	
			              	  	$args_stripe = array(
					                "customer" => $stripe_user_id,
					                "plan" => $strip_plan_id,
					                'application_fee_percent' => $connected_fees,
					                "metadata" => array("project_id" => $prid, "level_id"=>$level_id)
					              );
			              	  	if($trial_end!='' && date('d-m-Y')!=date('d-m-Y', $trial_end))
			              	  	{
			              	  		$args_stripe['trial_end'] = $trial_end;
			              	  	}
			              	  	//echo ""
			              	  	$return = \Stripe\Subscription::create($args_stripe,
			              	  		array("stripe_account" => $connected_account)
			              	  	);

			              	  }
			              	  else
			              	  {
			              	  	$args = array(
					                "customer" => $stripe_user_id,
					                "plan" => $strip_plan_id,
					                "metadata" => array("project_id" => $prid, "level_id"=>$level_id)
					              );

			              	  	if($trial_end!='' && date('d-m-Y')!=date('d-m-Y', $trial_end))
			              	  	{
			              	  		$args['trial_end'] = $trial_end;
			              	  	}
			              	  	$return = \Stripe\Subscription::create($args);
			              	  }
		              	  }
		              	  catch(Exception $e)
		              	  {
		              	  	if($connected_account)
		              	  	{
		              	  		$args_stripe = array(
					                "amount" => round($recurring,2)*100,
					                "interval" => $interval,
					                "name" => $pr->post_title.' : level '.$level_id,
					                "currency" => "usd",
					                "id" => $pr->post_name.'_level'.$level_id
					                );

		              	  		$plan = \Stripe\Plan::create($args_stripe,
		              	  			array("stripe_account" => $connected_account)
					              );
		              	  	}
		              	  	else
		              	  	{
		              	  		$args = array(
					                "amount" => round($recurring,2)*100,
					                "interval" => $interval,
					                "name" => $pr->post_title.' : level '.$level_id,
					                "currency" => "usd",
					                "id" => $pr->post_name.'_level'.$level_id
					                );

		              	  		$plan = \Stripe\Plan::create($args);
		              	  	}
		              	  	
					              $plan_arr = $plan->__toArray(true);

						           if (!empty($plan_arr)) 
						           {
							           	if($connected_account)
							           	{
								             $wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_plans(`project_id`, `level_id`, `stripe_plan_id`, `connected`, `created_on`) VALUES('".$prid."', '".$level_id."', '".$plan_arr['id']."', '".$connected_account."', NOW())");
							           	}
							           	else
							           	{
							           		$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_plans(`project_id`, `level_id`, `stripe_plan_id`, `created_on`) VALUES('".$prid."', '".$level_id."', '".$plan_arr['id']."', NOW())");	
							           	}
					          	   }
									//subscription create for recurring
					          	   if($connected_account)
					              	  {
					              	  	$args_stripe = array(
							                "customer" => $stripe_user_id,
							                "plan" => $strip_plan_id,
							                'application_fee_percent' => $connected_fees,
							                "metadata" => array("project_id" => $prid, "level_id"=>$level_id)
							              );

					              	  	if($trial_end!='' && date('d-m-Y')!=date('d-m-Y', $trial_end))
					              	  	{
					              	  		$args_stripe['trial_end'] = $trial_end;
					              	  	}

					              	  	$return = \Stripe\Subscription::create($args_stripe,
					              	  		array("stripe_account" => $connected_account)
					              	  	);	
					              	  }
					              	  else
					              	  {
					              	  	$args = array(
							                "customer" => $stripe_user_id,
							                "plan" => $strip_plan_id,
							                "metadata" => array("project_id" => $prid, "level_id"=>$level_id)
							              );

					              	  	if($trial_end!='' && date('d-m-Y')!=date('d-m-Y', $trial_end))
					              	  	{
					              	  		$args['trial_end'] = $trial_end;
					              	  	}
					              	  	$return = \Stripe\Subscription::create($args);
					              	  }


		              	  }
			              
			               $subscription_return = $return->__toArray(true);

			           }
			           
			           
			           
		              if(!empty($subscription_return))
		              {
		                if($subscription_return['id']!='')
		                {

						  $this-> add_mailchimp_subscriber($prid, $current_user->user_email);

		                  if($connected_account)
		                  {
		                  	$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_subscriptions(`level_id`, `level_title`, `level_amount`, `user_id`, `project_id`, `subscription_id`, `object`, `application_fee_percent`, `cancel_at_period_end`, `canceled_at`, `created`, `current_period_end`, `current_period_start`, `customer`, `discount`, `ended_at`, `quantity`, `start`, `status`, `tax_percent`, `trial_end`, `trial_start`, `connected`, `created_on`) VALUES('".$level_id."', '".$subscription_return['plan']['object']."', '".$subscription_return['plan']['amount']."', ".$current_user->ID.",  '".$prid."',  '".$subscription_return['id']."', '".$subscription_return['object']."', '".$subscription_return['application_fee_percent']."', '".$subscription_return['cancel_at_period_end']."', '".$subscription_return['canceled_at']."', '".$subscription_return['created']."', '".$subscription_return['current_period_end']."', '".$subscription_return['current_period_start']."', '".$subscription_return['customer']."', '".$subscription_return['discount']."', '".$subscription_return['ended_at']."', '".$subscription_return['quantity']."', '".$subscription_return['start']."', '".$subscription_return['status']."', '".$subscription_return['tax_percent']."', '".$subscription_return['trial_end']."', '".$subscription_return['trial_start']."', '".$connected_account."', NOW())");
		                  }
		                  else
		                  {
		                  	$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_subscriptions(`level_id`, `level_title`, `level_amount`, `user_id`, `project_id`, `subscription_id`, `object`, `application_fee_percent`, `cancel_at_period_end`, `canceled_at`, `created`, `current_period_end`, `current_period_start`, `customer`, `discount`, `ended_at`, `quantity`, `start`, `status`, `tax_percent`, `trial_end`, `trial_start`, `created_on`) VALUES('".$level_id."', '".$subscription_return['plan']['object']."', '".$subscription_return['plan']['amount']."', ".$current_user->ID.",  '".$prid."',  '".$subscription_return['id']."', '".$subscription_return['object']."', '".$subscription_return['application_fee_percent']."', '".$subscription_return['cancel_at_period_end']."', '".$subscription_return['canceled_at']."', '".$subscription_return['created']."', '".$subscription_return['current_period_end']."', '".$subscription_return['current_period_start']."', '".$subscription_return['customer']."', '".$subscription_return['discount']."', '".$subscription_return['ended_at']."', '".$subscription_return['quantity']."', '".$subscription_return['start']."', '".$subscription_return['status']."', '".$subscription_return['tax_percent']."', '".$subscription_return['trial_end']."', '".$subscription_return['trial_start']."', NOW())");
		                  }
		                   
		                   
		                   $time = ($trial_end!=''?$trial_end:time());
		                   // Query to insert the stripe payment details
		                   if($connected_account)
		                   {
		                   	$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_payments(`user_id`, `project_id`, `level_id`, `payment`, `connected`, `created_on`) VALUES('".$current_user->ID."', '".$prid."', '".$level_id."', '".($subscription_return['plan']['amount']/100)."', '".$connected_account."', NOW())");
		                   }
		                   else
		                   {
							$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_payments(`user_id`, `project_id`, `level_id`, `payment`, `created_on`) VALUES('".$current_user->ID."', '".$prid."', '".$level_id."', '".($subscription_return['plan']['amount']/100)."', NOW())");

		                   }
		                   
		                   $project_pledge = get_post_meta($prid, 'project_pledge', true);

		                   $project_pledge = ($project_pledge!=''?$project_pledge:0);
		                   $pledge_recurring = $subscription_return['plan']['amount']/100;

		                   	$project_level_amt = get_post_meta($prid, 'project_level_amount'.$level_id, true);
		                   	$project_level_match_amount = get_post_meta($prid, 'project_level_match_amount'.$level_id, true);
		                   	$stripe_user_id = $customer_arr['id'];
		                  	
		                  	//recurring pledge existing customer update project meta rzrprojectmeta
		                 	$this-> add_project_pledge($prid, $current_user->ID, 'project_pledge', $project_level_amt, $level_id);
		                  	$this-> add_project_pledge($prid, $current_user->ID, 'project_pledge_recurring', $pledge_recurring, $level_id);
		                   	$this->update_project_meta($prid, $current_user->ID, 'project_payment_date', $time, $level_id);
		                   	$this->update_project_meta($prid, $current_user->ID, 'project_pledge_start', $time, $level_id);
							$this->update_project_meta($prid, $current_user->ID, 'project_remaining_payments', --$level_pmts, $level_id);
		                  	$this-> send_pledge_receipt($current_user->user_email, $pr->post_title, $level_title, $project_level_amt);
		                 	$this-> send_owner_customer_checkout($pr->post_author, $pr->post_title, $level_title, $project_level_amt);
		                 	if ($project_level_match_amount != '') {$this->add_project_pledge($prid, $current_user->ID, 'project_match', $project_level_match_amount, $level_id);}
		                 	$project_level_pmts = get_post_meta($prid, 'project_level_pmts'.$level_id, true);
		                 	add_post_meta($prid, 'project_pledge_by', $current_user->ID);
		                 	$remaining_payments = get_user_meta($current_user->ID, 'project_remaining_payments', true);
		                 	$remaining_pmts = ($remaining_payments!=''?$remaining_payments--:$project_level_pmts);
		                 	update_user_meta($current_user->ID, 'project_remaining_payments', $remaining_pmts);
		                 	
		                 wp_redirect(get_permalink($prid).'?level='.$level_id.'&checkout=final');
		                 die;
		                }

		              }
		          	}
		          else
		          {
		          	$fixed_connected_fees = '';
		          	if($connected_fees != '')
		            	$fixed_connected_fees = round(($connected_fees * $level_amount), 2);
		            if($stripe_user_id=='')
		            {
			              if($connected_account)
			              {
			              	try {
			              		$customer = \Stripe\Customer::create(array(
			                  "source" => $token,
			                  'description' => $first_name." ".$last_name,
			                  "email" => $current_user->data->user_email),
			              		array("stripe_account" => $connected_account)
			                  );
			                  } catch(\Stripe\Error\Card $e) {
  								// Since it's a decline, \Stripe\Error\Card will be caught
  								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\RateLimit $e) {
								// Too many requests made to the API too quickly
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\InvalidRequest $e) {
								// Invalid parameters were supplied to Stripe's API
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\Authentication $e) {
								// Authentication with Stripe's API failed
								// (maybe you changed API keys recently)
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\ApiConnection $e) {
								// Network communication with Stripe failed
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\Base $e) {
								// Display a very generic error to the user, and maybe send
								// yourself an email
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (Exception $e) {
								// Something else happened, completely unrelated to Stripe
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								}
			              }
			              else
			              {
			              	try {$customer = \Stripe\Customer::create(array(
			                  "source" => $token,
			                  'description' => $first_name." ".$last_name,
			                  "email" => $current_user->data->user_email)
			                  );
			                  	} catch(\Stripe\Error\Card $e) {
  								// Since it's a decline, \Stripe\Error\Card will be caught
  								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\RateLimit $e) {
								// Too many requests made to the API too quickly
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\InvalidRequest $e) {
								// Invalid parameters were supplied to Stripe's API
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\Authentication $e) {
								// Authentication with Stripe's API failed
								// (maybe you changed API keys recently)
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\ApiConnection $e) {
								// Network communication with Stripe failed
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (\Stripe\Error\Base $e) {
								// Display a very generic error to the user, and maybe send
								// yourself an email
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								} catch (Exception $e) {
								// Something else happened, completely unrelated to Stripe
								$body = $e->getJsonBody();
								$err  = $body['error'];
								$error=1;
								$error_message = $err['message'];
								}
			              }

			               if($error!=1) { $customer_arr = $customer->__toArray(true); }
			              if(!empty($customer_arr)&&$error!=1)
			              {
			                if($customer_arr['id']!='')
			                {

			                  if($connected_account)
			                  {
			                  	$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_users(`user_id`, `stripe_user_id`, `connected`, `created_on`) VALUES(".$current_user->ID.", '".$customer_arr['id']."', '".$connected_account."', NOW())");
			                  }
			                  else
			                  {
			                  	$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_users(`user_id`, `stripe_user_id`, `created_on`) VALUES(".$current_user->ID.", '".$customer_arr['id']."', NOW())");
			                  }
			                  
			                  $stripe_user_id = $customer_arr['id'];
			                }

			              }
		            }
		            //else for customer exists on recurring

					$args = array(
						"amount" => $level_amount*100, // amount in cents, again
		                "currency" => "usd",
		                "customer" => $stripe_user_id,
		                "metadata" => array(
		                    'level_id' => $level_id,
		                    'level_name' => $level_title,
		                    'level_amount' => $level_amount,
		                    'project_id' => $prid
		                  )
						);
					if($connected_account &&$error!=1)
					{
						$args['application_fee'] = $fixed_connected_fees;
					}
		              if($error!=1) {
		              	if($connected_account )
		              	{
		              	//charge for non recurring levels
		              	$charge = \Stripe\Charge::create($args, array("stripe_account" => $connected_account));	
		              	}
		              	else
		              	{
     					$charge = \Stripe\Charge::create($args);	
		              	}

		              $charge_arr = $charge->__toArray(true);

		              if(!empty($charge_arr))
		              {

		                $sql = $wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_transactions(`level_id`, `level_title`, `level_amount`, `trans_id`, `object`, `amount`, `amount_refunded`, `application_fee`, `balance_transaction`, `captured`, `created`, `currency`, `customer`, `description`, `destination`, `dispute`, `failure_code`, `failure_message`, `invoice`, `livemode`, `stripe_order`, `paid`, `receipt_email`, `receipt_number`, `refunded`, `shipping`, `source_transfer`, `statement_descriptor`, `status`, `created_on`) VALUES('".$charge_arr['metadata']['level_id']."', '".$charge_arr['metadata']['level_name']."', '".$charge_arr['metadata']['level_amount']."', '".$charge_arr['id']."', '".$charge_arr['object']."', '".$charge_arr['amount']."', '".$charge_arr['amount_refunded']."', '".$charge_arr['application_fee']."', '".$charge_arr['balance_transaction']."', '".$charge_arr['captured']."', '".$charge_arr['created']."', '".$charge_arr['currency']."', '".$charge_arr['customer']."', '".$charge_arr['description']."', '".$charge_arr['destination']."', '".$charge_arr['dispute']."', '".$charge_arr['failure_code']."', '".$charge_arr['failure_message']."', '".$charge_arr['invoice']."', '".$charge_arr['livemode']."', '".$charge_arr['order']."', '".$charge_arr['paid']."', '".$charge_arr['receipt_email']."', '".$charge_arr['receipt_number']."', '".$charge_arr['refunded']."', '".$charge_arr['shipping']."', '".$charge_arr['source_transfer']."', '".$charge_arr['statement_descriptor']."', '".$charge_arr['status']."', NOW())");

		                $wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_payments(`user_id`, `project_id`, `level_id`, `payment`, `created_on`) VALUES('".$current_user->ID."', '".$prid."', '".$level_id."', '".($charge_arr['amount']/100)."', NOW())");
		            	
		            	$stripe_user_id = $customer_arr['id'];
		                
		                $project_pledge = get_post_meta($prid, 'project_pledge', true);
		                $project_pledge = ($project_pledge!=''?$project_pledge:0);
		                $project_level_amt = get_post_meta($prid, 'project_level_amount'.$level_id, true);
		                $project_level_match_amount = get_post_meta($prid, 'project_level_match_amount'.$level_id, true);
		                
		                //one time pledge new or existing customer rzrprojectmeta
		                $this->add_project_pledge($prid, $current_user->ID, 'project_pledge', $project_level_amt, $level_id);
		                $this->update_project_meta($prid, $current_user->ID, 'project_payment_date', time(), $level_id);
		                add_post_meta($prid, 'project_pledge_by', $current_user->ID);
		                if ($project_level_match_amount != '') {$this->add_project_pledge($prid, $current_user->ID, 'project_match', $project_level_match_amount, $level_id);}
		                $this-> send_pledge_receipt($current_user->user_email, $pr->post_title, $level_title, $project_level_amt);
						$this-> send_owner_customer_checkout($pr->post_author, $pr->post_title, $level_title, $project_level_amt);
						$this-> add_mailchimp_subscriber($prid, $current_user->user_email);

		                wp_redirect(get_permalink($prid).'?level='.$level_id.'&checkout=final');
		                die;
		              }
		            }
		            }
		    }
		}
	}
?>

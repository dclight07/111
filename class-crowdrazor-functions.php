<?php
		function create_stripe_charge($customer=0,$level_id=0,$prid=0, $connected_account=0){
			$level_amount = get_post_meta($prid, 'project_level_amount'.$level_id, true);
			$level_title = get_post_meta($prid, 'project_level_title'.$level_id, true);
			$connected_fees = get_option('rzr_stripe_fee_amount');
			$fixed_connected_fees = round(($connected_fees * $level_amount), 2);
			$args = array(
				"amount" => $level_amount*100, // amount in cents, again
		                "currency" => "usd",
		                "customer" => $customer,
		                "metadata" => array(
		                    'level_id' => $level_id,
		                    'level_name' => $level_title,
		                    'level_amount' => $level_amount,
		                    'project_id' => $prid
		                  )
			);
			try{
				if($connected_account)
				{
					$args['application_fee'] = $fixed_connected_fees;
					$result = \Stripe\Charge::create($args, array("stripe_account" => $connected_account));	
				}
				else{
     					$result = \Stripe\Charge::create($args);	
				}
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
				$error=1;res
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
			$response=array(
				"error"=>$error,
				"error_message"=>$error_message,
				"result"=>$result
				);
			return $response;
		}

		function create_stripe_customer($user_id=0, $connected_account=0, $token=0){
			$first_name = get_user_meta($user_id, 'first_name', true);
		        $last_name = get_user_meta($user_id, 'last_name', true);
			$user_info = get_userdata($user_id);
  			$user_email = $user_info->user_email; 
			require 'stripe/vendor/autoload.php';
			$stripe_keys = $this->get_stripe_keys();
			$api_key = $stripe_keys['stripe_secret_key'];
			\Stripe\Stripe::setApiKey($api_key);
			try{
				if($connected_account){
					$result = \Stripe\Customer::create(array(
						"source" => $token,
						'description' => $first_name." ".$last_name,
						"email" => $user_email),
						array("stripe_account" => $connected_account)
					);	
				}
				else {
					$result = \Stripe\Customer::create(array(
						"source" => $token,
						'description' => $first_name." ".$last_name,
						"email" => $user_email)
					);	
				}
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

			if($error!=1){
				$customer_arr = $result->__toArray(true); 
				if($connected_account){
				        $wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_users(`user_id`, `stripe_user_id`, `connected`, `created_on`) VALUES(".$user_id.", '".$customer_arr['id']."', '".$connected_account."', NOW())"); 	
				}
				else{
				        $wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_users(`user_id`, `stripe_user_id`, `created_on`) VALUES(".$user_id.", '".$customer_arr['id']."', NOW())"); 
				}
			}
			$response=array(
				"error"=>$error,
				"error_message"=>$error_message,
				"customer_id"=>$customer_arr['id']
				);
			return $response;
		}

		function create_stripe_plan($level_id=0, $prid=0, $connected_account=0){
			global $wpdb;
			$pr=get_post($prid);
			$recurring = get_post_meta($prid, 'project_level_recurring'.$level_id, true);
			$level_freq = get_post_meta($prid, 'project_level_frequency'.$level_id, true);
			$stripe_plan_id = $pr->post_name.' : level '.$level_id;
			$stripe_plan_name = $pr->post_title.' : level '.$level_id;
			if($level_freq == 'yearly') {
				$interval = 'year'; 
				} 
				else { 
					if($level_freq == 'monthly'){
					$interval = 'month'; 
				}
				else {
					$interval = 'week'; 
				}
			}
			try{
				if($connected_account){
					$result = \Stripe\Plan::create(array(
						"amount" => round($recurring,2)*100,
						"interval" => $interval,
						"name" => $stripe_plan_name,
						"currency" => "usd",
						"id" => $stripe_plan_id,
						array("stripe_account" => $connected_account)
					);
				}
				else
				{
					$result = \Stripe\Plan::create(array(
						"amount" => round($recurring,2)*100,
						"interval" => $interval,
						"name" => $stripe_plan_name,
						"currency" => "usd",
						"id" => $stripe_plan_id)
					);
				}
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
			$response=array(
				"error"=>$error,
				"error_message"=>$error_message,
				"result"=>$result
				);
		if($connected_account && $error != 1)
		              	  	{
		              	  		$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_plans(`project_id`, `level_id`, `stripe_plan_id`, `connected`, `created_on`) VALUES('".$prid."', '".$level_id."', '".$strip_plan_id."', '".$connected_account."', NOW())");
		              	  	}
		              	  	else
		              	  	{ 
						if($error!=1) {
		              	  			$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_plans(`project_id`, `level_id`, `stripe_plan_id`, `created_on`) VALUES('".$prid."', '".$level_id."', '".$strip_plan_id."', NOW())");
						}
					}
			return $response;
		}

		function create_stripe_subscription($customer=0, $plan_id=0, $prid=0, $level_id=0, $trial_end=0, $connected_account=0){
			$connected_fees = get_option('rzr_stripe_fee_amount');
			try{
				if($connected_account){
		              	  	$args_stripe = array(
				                "customer" => $customer,
		                    		"plan" => $plan_id,
		                    		'application_fee_percent' => $connected_fees,
		                    		"metadata" => array("project_id" => $prid, "level_id"=>$level_id)
				         );
					if($trial_end!='' && date('d-m-Y')!=date('d-m-Y', $trial_end))
						{
							$args_stripe['trial_end'] = $trial_end;
						}	              	  	
		              	  	$result = \Stripe\Subscription::create($args_stripe,
		                    		array("stripe_account" => $connected_account)
		              	  	);	
		         	} 
				else{
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
		              	  	$result = \Stripe\Subscription::create($args_stripe);	
				}
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
			$response=array(
				"error"=>$error,
				"error_message"=>$error_message,
				"result"=>$result
				);
			return $response;
		}

///update customer card function that already exists may work for card and bank

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
		         
			//get stripe customer id
			if($connected_account)
		         {
		         	$customer_id = $wpdb->get_var("SELECT `stripe_user_id` FROM ".$wpdb->prefix."rzr_stripe_users WHERE `user_id`=".$current_user->ID." AND `connected`='".$connected_account."'");	
		         }
		         else
		         {
		         	$customer_id = $wpdb->get_var("SELECT `stripe_user_id` FROM ".$wpdb->prefix."rzr_stripe_users WHERE `user_id`=".$current_user->ID);		
		         }
				
			//create stripe customer if blank
		        if($customer_id=='')
		        {
				$response = $this->create_stripe_customer($current_user->ID, $connected_account, $token);
				    	$error = $response['error'];
		              	if($error!=1){ 
					$error_message = $response['error_message'];
		             		$customer_id = $response['customer_id';
				}
			}
		         
			//one time charge
			if($_POST['recurring']=='' && $customer_id){
				//charge customer
				$response = $this->create_stripe_charge($customer_id, $level_id, $prid, $connected_account);
				$error = $response['error'];
				//update tables for one time
				if($error!=1){
					$error_message = $response['error_message'];
					$charge = $response['result'];
					
					//get attributes for meta data
					$project_pledge = get_post_meta($prid, 'project_pledge', true);
		               		$project_pledge = ($project_pledge!=''?$project_pledge:0);
		                	$project_level_amt = get_post_meta($prid, 'project_level_amount'.$level_id, true);
		                	$project_level_match_amount = get_post_meta($prid, 'project_level_match_amount'.$level_id, true);
		                
		                	//one time pledge new or existing customer rzrprojectmeta
		                	$this->add_project_pledge($prid, $current_user->ID, 'project_pledge', $project_level_amt, $level_id);
		                	$this->update_project_meta($prid, $current_user->ID, 'project_payment_date', time(), $level_id);
		                	add_post_meta($prid, 'project_pledge_by', $current_user->ID);
		                	if ($project_level_match_amount != '') {$this->add_project_pledge($prid, $current_user->ID, 'project_match', $project_level_match_amount, $level_id);}
		                	//send emails
					$this-> send_pledge_receipt($current_user->user_email, $pr->post_title, $level_title, $project_level_amt);
					$this-> send_owner_customer_checkout($pr->post_author, $pr->post_title, $level_title, $project_level_amt);
					//add to mailchimp
					$this-> add_mailchimp_subscriber($prid, $current_user->user_email);
				}
			}
			
			//if recurring  and customer then
			//check for plan in wp and stripe and create if not exists
			//subscribe the customer to the plan with trial date if applicable
			//update tables for recurring and send emails
			
			//if no error redirect
			//else go back to step 3 with error
			
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
				$response = $this->create_stripe_customer($current_user->ID, $connected_account, $token);
				    	$error = $response['error'];
					$error_message = $response['error_message'];
					$customer = $response['result'];
		             
		              	if($error!=1){ $customer_arr = $customer->__toArray(true); 
					if($connected_account){
				        	 $wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_users(`user_id`, `stripe_user_id`, `connected`, `created_on`) VALUES(".$current_user->ID.", '".$customer_arr['id']."', '".$connected_account."', NOW())"); 	
					}
					else{
				         	$wpdb->query("INSERT INTO ".$wpdb->prefix."rzr_stripe_users(`user_id`, `stripe_user_id`, `created_on`) VALUES(".$current_user->ID.", '".$customer_arr['id']."', NOW())"); 
					}
				}
			    }
				    
				
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

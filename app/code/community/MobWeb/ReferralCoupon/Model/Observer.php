<?php
class MobWeb_ReferralCoupon_Model_Observer
{
	// This function is run on every page load. It checks if the "ref"
	// parameter is appended to the URL, and if yes, it places a cookie
	// in the user's browser so that when he signs up or places an order,
	// the referral is attributed to the referrer
    public function captureReferral(Varien_Event_Observer $observer)
    {
    	$controller = $observer->getEvent()->getFront();
    	if($referrer = $controller->getRequest()->getParam('ref', false)) {
    		// If a referrer has been detected, save it in a cookie
    		Mage::getSingleton('core/cookie')->set(
				Mage::helper('referralcoupon')->cookie_name,
				$referrer,
				60*60*24*365*10, // 10 years
				'/'
			);

			// Create a log entry
			Mage::helper('referralcoupon')->log('Referral parameter captured: ' . $referrer);
    	}
    }

    // After creating an account, check if the newly registered customer
    // was referred by another customer by checking if the "referral"
    // cookie exists. If yes, store that information as part of the customer
    // data
    public function captureRegistration(Varien_Event_Observer $observer)
    {
    	if($referrer_id = Mage::getModel('core/cookie')->get(Mage::helper('referralcoupon')->cookie_name)) {
    		// Update the newly registered customer's account with the
    		// customer ID of its referrer
    		$customer = $observer->getCustomer();
    		$customer->setData('mobweb_referralcoupon_referrer', $referrer_id);

    		// And remove the cookie
			Mage::getModel('core/cookie')->delete(Mage::helper('referralcoupon')->cookie_name);

			// Create a log entry
			Mage::helper('referralcoupon')->log('Referral registration captured, referred by: ' . $referrer_id);
    	}
    }

    // If an order is placed, check if the customer is registered.
    // If yes, check if his account is linked to a referrer. If not,
    // check if he has registered recently and if yes if a referrer cookie
    // is present. If the user isn't using a registered account at all, also
    // check if the referrer cookie is present. If either of these are true,
    // send the referring customer his coupon code
    public function captureOrder(Varien_Event_Observer $observer)
    {
		$order = $observer->getEvent()->getOrder();

		// Check if the order was placed by a registered account or a
		// guest
		if($user_id = $order->getCustomerId()) {
			// Get a reference to the user
			$user = Mage::getModel('customer/customer')->load($user_id);

			// Check if the user has a "mobweb_referralcoupon_referrer"
			// attribute, which contains the user ID of the referrer, AND
			// if the "mobweb_referralcoupon_claimed" attribute is not set
			// to 1, because if it is, the coupon has already been sent to
			// the referrer
			if(($referrer_id = $user->getData('mobweb_referralcoupon_referrer')) && $user->getData('mobweb_referralcoupon_claimed') !== '1') {
				// Send the referrer a discount coupon
				$this->sendCoupon($referrer_id);

				// And update the "mobweb_referralcoupon_claimed" attribute to
				// indicate that the the referrer has already recieved a coupon
				// for this referral
				$user->setData('mobweb_referralcoupon_claimed', '1');
				$user->save();

				// Create a log entry
				Mage::helper('referralcoupon')->log(sprintf('Order captured by registered referred user %s, referred by %s', $user_id, $referrer_id));
			} else {
				// If the user doesn't have that attribute, check if
				// his account was created in the last 10 minutes, meaning
				// he registered while checking out. Unfortunately the
				// "customer account created" event is not fired if the
				// registration happens during the checkout, so we have to use 
				// this workaround
				if($created_at_utc = $user->getData('created_at')) {
					// The "Created At" attribute always returns the creation
					// date in the UTC timezone, so we have to convert it to
					// the store's timezone  to avoid bugs due to different
					// timezones on the server and Magento
					$created_at = Mage::getModel('core/date')->timestamp(strtotime($created_at_utc));
					$current = Mage::getModel('core/date')->timestamp(time());

					// If the account was created during the last 10 minutes,
					// the registration was during checkout
					if(($current-$created_at) < 60*10) {
						// Check if the "referrer" cookie exists
						if($referrer_id = Mage::getModel('core/cookie')->get(Mage::helper('referralcoupon')->cookie_name)) {

							// Send the referrer a discount coupon
							$this->sendCoupon($referrer_id);

							// Destroy the "refferer" cookie
							Mage::getModel('core/cookie')->delete(Mage::helper('referralcoupon')->cookie_name);

							// And update both the "referrer" and
							// "coupon_claimed" attributes on the referral
							$user->setData('mobweb_referralcoupon_referrer', $referrer_id);
							$user->setData('mobweb_referralcoupon_claimed', '1');
							$user->save();

							// Create a log entry
							Mage::helper('referralcoupon')->log(sprintf('Order captured by newly registerd referred user %s, referred by %s', $user->getId(), $referrer_id));
						}
					}
				}
			}
		} else {
			// Check if the "referrer" cookie exists
			if($referrer_id = Mage::getModel('core/cookie')->get(Mage::helper('referralcoupon')->cookie_name)) {

				// Send the referrer a discount coupon
				$this->sendCoupon($referrer_id);

				// Destroy the "refferer" cookie
				Mage::getModel('core/cookie')->delete(Mage::helper('referralcoupon')->cookie_name);

				// Create a log entry
				Mage::helper('referralcoupon')->log(sprintf('Order captured by guest user with referral cookie, referred by %s', $referrer_id));
			}
		}
    }

    // Sends a coupon to the customer specified as $referrer_id
    public function sendCoupon($referrer_id) {
    	$referrer_id = 2;
    	// Load the user object of the referrer
    	if($referrer = Mage::getModel('customer/customer')->load($referrer_id)) {

    		// Get the referrer's email address
    		$referrer_email = $referrer->getEmail();

    		// Create the coupon code
    		$coupon_code = Mage::helper('referralcoupon')->createCoupons(1, $referrer_id);
    		if($coupon_code = $coupon_code[0]) {
    			// Load the transactional email specified in the config
    			$transactional_email_id = Mage::getStoreConfig('referralcoupon/configuration/transactional_email_id');
    			$transactional_email = Mage::getModel('core/email_template')->load($transactional_email_id);

    			// Check if the transactional email exists
    			if($transactional_email->isObjectNew()) {
    				// Create a log entry
    				Mage::helper('referralcoupon')->log('Invalid or unknown transactional email ID specified: ' . $transactional_email_id);
    				return false;
    			}


	    		// Send the coupon to the referrer
			    Mage::getModel('core/email_template')->sendTransactional(
			    	$transactional_email->getId(),
			    	array(
			    		'name' => Mage::getStoreConfig('trans_email/ident_support/name'),
			    	    'email' =>  Mage::getStoreConfig('trans_email/ident_support/email')
			    	),
			    	$referrer_email,
			    	$referrer_email,
			    	array('coupon_code' => $coupon_code),
			    	Mage::app()->getStore()->getId()
			  );

			    // Create a log entry
			    Mage::helper('referralcoupon')->log('Sending coupon code to user: ' . $referrer_id);
			}
    	}
    }
}

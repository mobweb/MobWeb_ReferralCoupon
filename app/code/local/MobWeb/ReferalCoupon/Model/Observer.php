<?php
class MobWeb_ReferalCoupon_Model_Observer
{
    public function captureReferral( Varien_Event_Observer $observer )
    {
    	$controller = $observer->getEvent()->getFront();
    	if( $referrer = $controller->getRequest()->getParam('ref', false) ) {
    		// If a referrer has been detected, save it in a cookie
    		Mage::getSingleton( 'core/cookie' )->set(
				Mage::helper( 'referalcoupon' )->cookie_name,
				$referrer,
				60*60*24*365*10, // 10 years
				'/'
			);
    	}
    }

    public function captureRegistration( Varien_Event_Observer $observer )
    {
    	// After creating an account, check if the newly registered customer
    	// was referred by another customer by checking if the "referral"
    	// cookie exists
    	if( $referrer_id = Mage::getModel( 'core/cookie' )->get( Mage::helper( 'referalcoupon' )->cookie_name ) ) {

    		// Update the newly registered customer's account with the
    		// customer ID of its referrer
    		$customer = $observer->getCustomer();
    		$customer->setData( 'mobweb_referalcoupon_referrer', $referrer_id );

    		// And remove the cookie
			Mage::getModel( 'core/cookie' )->delete( Mage::helper( 'referalcoupon' )->cookie_name );
    	}
    }

    public function captureOrder( Varien_Event_Observer $observer )
    {
		$order = $observer->getEvent()->getOrder();

		// Check if the order was placed by a registered account or a
		// guest
		if( $user_id = $order->getCustomerId() ) {

			// Get a reference to the user
			$user = Mage::getModel( 'customer/customer' )->load( $user_id );

			// Check if the user has a "mobweb_referalcoupon_referrer"
			// attribute, which contains the user ID of the referrer, AND
			// if the "mobweb_referalcoupon_claimed" attribute is not set
			// to 1, because if it is, the coupon has already been sent to
			// the referrer
			if( $referrer_id = $user->getData( 'mobweb_referalcoupon_referrer' ) && $user->getData( 'mobweb_referalcoupon_claimed' ) !== '1' ) {

				// Send the referrer a discount coupon
				$this->sendCoupon( $referrer_id );

				// And update the "mobweb_referalcoupon_claimed" attribute to
				// indicate that the the referrer has already recieved a coupon
				// for this referal
				$user->setData( 'mobweb_referalcoupon_claimed', '1' );
				$user->save();
			} else {

				// If the user doesn't have that attribute, check if
				// his account was created in the last 10 minutes, meaning
				// he registered while checking out. Unfortunately the
				// "customer account created" event is not fired if the
				// registration happens during the checkout, so we have to use 
				// this workaround
				if( $created_at_utc = $user->getData( 'created_at' ) ) {

					// The "Created At" attribute always returns the creation
					// date in the UTC timezone, so we have to convert it to
					// the store's timezone  to avoid bugs due to different
					// timezones on the server and Magento
					$created_at = Mage::getModel( 'core/date' )->timestamp( strtotime( $created_at_utc ) );
					$current = Mage::getModel( 'core/date' )->timestamp( time() );

					// If the account was created during the last 10 minutes,
					// the registration was during checkout
					if( ($current-$created_at) < 60*10 ) {

						// Check if the "referrer" cookie exists
						if( $referrer_id = Mage::getModel( 'core/cookie' )->get( Mage::helper( 'referalcoupon' )->cookie_name ) ) {

							// Send the referrer a discount coupon
							$this->sendCoupon( $referrer_id );

							// Destroy the "refferer" cookie
							Mage::getModel( 'core/cookie' )->delete( Mage::helper( 'referalcoupon' )->cookie_name );

							// And update both the "referrer" and
							// "coupon_claimed" attributes on the referal
							$user->setData( 'mobweb_referalcoupon_referrer', $referrer_id );
							$user->setData( 'mobweb_referalcoupon_claimed', '1' );
							$user->save();
						}
					}
				}
			}
		} else {

			// Check if the "referrer" cookie exists
			if( $referrer_id = Mage::getModel( 'core/cookie' )->get( Mage::helper( 'referalcoupon' )->cookie_name ) ) {

				// Send the referrer a discount coupon
				$this->sendCoupon( $referrer_id );

				// Destroy the "refferer" cookie
				Mage::getModel( 'core/cookie' )->delete( Mage::helper( 'referalcoupon' )->cookie_name );
			}
		}
    }

    public function sendCoupon( $referrer_id ) {

    	// Load the user object of the referrer
    	if( $referrer = Mage::getModel( 'customer/customer' )->load( $referrer_id ) ) {

    		// Get the referrer's email address
    		$referrer_email = $referrer->getEmail();

    		// Create the coupon code
    		$coupon_code = Mage::helper( 'referalcoupon' )->createCoupons( 1, $referrer_id )[ 0 ];

    		// Send the coupon to the referrer
		    Mage::getModel( 'core/email_template' )->sendTransactional(
		    	Mage::helper( 'referalcoupon' )->transactional_email_id,
		    	array(
		    		'name' => Mage::getStoreConfig( 'trans_email/ident_support/name' ),
		    	    'email' =>  Mage::getStoreConfig( 'trans_email/ident_support/email' )
		    	),
		    	$referrer_email,
		    	$referrer_email,
		    	array( 'coupon_code' => $coupon_code ),
		    	Mage::app()->getStore()->getId()
		    );

		    // Update the referal's account to indicate that the coupon
		    // has already been sent to the referrer



    		Mage::log( 'Sending coupon code: ' . $coupon_code . ' to ' . $referrer_email );
    	}
    }
}

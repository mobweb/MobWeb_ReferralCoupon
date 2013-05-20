<?php

class MobWeb_ReferalCoupon_Helper_Data extends Mage_Core_Helper_Abstract
{
	public $cookie_name = 'mobweb_referalcoupon_cookie';
	public $transactional_email_id = 1; // The ID of the transactional email template that will be used to send the coupon to the referrer

	public function createCoupons( $count = 1, $string = '' )
	{
		// Get the rule in question
		$rule = Mage::getModel( 'salesrule/rule' )->load( 1 );

		// Define a coupon code generator model instance
		// Look at Mage_SalesRule_Model_Coupon_Massgenerator for options
		$generator = Mage::getModel( 'salesrule/coupon_massgenerator' );

		$parameters = array(
		    'count' => $count,
		    'format' => 'alphanumeric',
		    'dash_every_x_characters' => 4,
		    'prefix' => $string . '-',
		    'suffix' => '',
		    'length' => 4
		);

		if( !empty( $parameters[ 'format' ] ) ){
		  switch( strtolower($parameters['format']) ){
		    case 'alphanumeric':
		    case 'alphanum':
		      $generator->setFormat( Mage_SalesRule_Helper_Coupon::COUPON_FORMAT_ALPHANUMERIC );
		      break;
		    case 'alphabetical':
		    case 'alpha':
		      $generator->setFormat( Mage_SalesRule_Helper_Coupon::COUPON_FORMAT_ALPHABETICAL );
		      break;
		    case 'numeric':
		    case 'num':
		      $generator->setFormat( Mage_SalesRule_Helper_Coupon::COUPON_FORMAT_NUMERIC );
		      break;
		  }
		}

		$generator->setDash( !empty( $parameters[ 'dash_every_x_characters' ] )? ( int ) $parameters[ 'dash_every_x_characters' ] : 0 );
		$generator->setLength( !empty( $parameters[ 'length' ] ) ? (int) $parameters[ 'length' ] : 6);
		$generator->setPrefix( !empty( $parameters[ 'prefix' ] ) ? $parameters[ 'prefix' ] : '' );
		$generator->setSuffix( !empty( $parameters[ 'suffix' ] ) ? $parameters[ 'suffix' ] : '' );

		// Set the generator, and coupon type so it's able to generate
		$rule->setCouponCodeGenerator( $generator );
		$rule->setCouponType( Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO );

		// Get as many coupons as you required
		$count = !empty( $parameters[ 'count' ] ) ? (int) $parameters[ 'count' ] : 1;
		$codes = array();

		for( $i = 0; $i < $count; $i++ ) {
			$coupon = $rule->acquireCoupon();
			$code = $coupon->getCode();
			$codes[] = $code;
		}

		return $codes;
	}
}
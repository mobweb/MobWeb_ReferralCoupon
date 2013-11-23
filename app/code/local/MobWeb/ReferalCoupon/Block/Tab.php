<?php
class MobWeb_ReferalCoupon_Block_Tab extends Mage_Core_Block_Template
{
	public function getReferrals()
	{
		// Get the current customer's ID
		$customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();

		if(!$customerId || !Mage::getSingleton('customer/session')->isLoggedIn()) {
			Mage::helper('referalcoupon')->log('Unable to retreive customer referrals, either the customer isnt logged in or another error occured');
			return;
		}

		// Get all the customers reffered by the current customer
		$referrals = Mage::getModel('customer/customer')
						->getCollection()
		              	->addAttributeToSelect('mobweb_referalcoupon_referrer')
		              	->addAttributeToSelect('mobweb_referalcoupon_claimed')
		              	->addAttributeToFilter('mobweb_referalcoupon_referrer', $customerId)
		              	->addAttributeToSort('created_at', 'DESC')
		              	->load();

		return $referrals;
	}

	public function getReferralLink()
	{
		// Get the current customer's ID
		$customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();

		if($customerId && Mage::getSingleton('customer/session')->isLoggedIn()) {
			return Mage::getBaseUrl() . '?ref=' . $customerId;
		}
	}
}
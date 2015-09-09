<?php
class MobWeb_ReferralCoupon_Block_Tab extends Mage_Core_Block_Template
{
	public function getReferrals()
	{
		// Get the current customer's ID
		$customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();

		if(!$customerId || !Mage::getSingleton('customer/session')->isLoggedIn()) {
			Mage::helper('referralcoupon')->log('Unable to retreive customer referrals, either the customer isnt logged in or another error occured');
			return;
		}

		// Get all the customers reffered by the current customer
		$referrals = Mage::getModel('customer/customer')
						->getCollection()
		              	->addAttributeToSelect('mobweb_referralcoupon_referrer')
		              	->addAttributeToSelect('mobweb_referralcoupon_claimed')
		              	->addAttributeToFilter('mobweb_referralcoupon_referrer', $customerId)
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

	public function getInstructions()
	{
		return Mage::getStoreConfig('referralcoupon/instruction_text/instruction_text');
	}
}
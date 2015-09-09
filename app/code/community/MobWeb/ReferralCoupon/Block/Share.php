<?php
class MobWeb_ReferralCoupon_Block_Share extends AddThis_SharingTool_Block_Share
{
    public function getCustomUrl(){
        // If a custom URL is defined in the settings, use that
        if($custom_url = Mage::getStoreConfig('sharing_tool/custom_share/custom_url')) {
            return $custom_url;
        }
 
        // Otherwise, check if the user is logged in and if they are, return
        // the current product URL with the "ref" param
        $url = Mage::registry('current_product')->getUrlInStore();
        if(($customerId = Mage::getSingleton('customer/session')->getCustomer()->getId()) && Mage::getSingleton('customer/session')->isLoggedIn()) {
            $url .= strpos($url, '?') ? '&ref=' : '?ref=';
            $url .= $customerId;
             
            return $url;
        } else {
            return;
        }
    }
}
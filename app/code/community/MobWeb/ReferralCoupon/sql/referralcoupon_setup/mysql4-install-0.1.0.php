<?php
/*
 *
 * This file installs the custom attributes into the DB. To see if it has been
 * executed properly, see the "core_resource" table in the DB. To run the
 * script again, simply delete the corresponding row from "core_resources"
 *
 */
$installer = $this;
$installer->startsetup();

// Create the custom attribute
$installer->addAttribute(
    'customer', // Some identifiers are different, e.g. 'catalog_product',
    'mobweb_referralcoupon_referrer',
    array(
        'group' => 'Default',
        'type' => 'varchar',
        'label' => 'Referred By (Customer ID)',
        'input' => 'text',
        'required' => 0,
        'default' => '',
    )
);

// Add the custom attribute to the adminhtml_customer form in the backend.
// Check the table customer_form_attribute to see if the attribute was properly
// added to the form (remove the next line for debugging)
/*
Mage::getSingleton('eav/config')
    ->getAttribute('customer', 'mobweb_referralcoupon_referrer')
    ->setData('used_in_forms', array('adminhtml_customer'))
    ->save();
// */

// Create the custom attribute
$installer->addAttribute(
    'customer', // Some identifiers are different, e.g. 'catalog_product',
    'mobweb_referralcoupon_claimed',
    array(
        'group' => 'Default',
        'type' => 'varchar',
        'label' => 'Referral Claimed',
        'input' => 'text',
        'required' => 0,
        'default' => '0',
    )
);

// Add the custom attribute to the adminhtml_customer form in the backend.
// Check the table customer_form_attribute to see if the attribute was properly
// added to the form (remove the next line for debugging)
/*
Mage::getSingleton('eav/config')
    ->getAttribute('customer', 'mobweb_referralcoupon_claimed')
    ->setData('used_in_forms', array('adminhtml_customer'))
    ->save();
// */

$installer->endSetup();
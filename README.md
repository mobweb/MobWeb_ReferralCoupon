# MobWeb_ReferalCoupon extension for Magento

Send a coupon code to a customer after he refers a new customer to your store and this referred user places an order. Works with anonymous checkout as well if the user creates an account before or during checkout. Each referal will get the referrer only one coupon code, altough an abusive user could easily bypass this limitation by just checking out without creating an account.

## Installation

Install using [colinmollenhour/modman](https://github.com/colinmollenhour/modman/).

Afterwards, create a new «Shopping Cart Price Rule» in your Magento Admin Panel under «Promotions». This Price Rule will then be used to create the coupon codes. Enter the newly created Price Rule's ID in Helper/Data.php ($shopping_cart_rule_id). Next, create a new Transactional Email under System -> Transactional Emails. You may use the «coupon_code» variable to enter the coupon code into the Email. Enter the newly created Transactional Email's ID in Helper/Data.php.
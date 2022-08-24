# CASHPRESSO 
 [Follow the link for more details about cashpresso.](https://www.cashpresso.com/)
 
### Table of Contents
**[1. Installation Instructions](#installation-instructions)**<br>
**[2. Configuration](#configuration)**<br>
**[3. How it works](#howto)**<br>
**[4. FAQ](#faq)**<br>
**[5. Links](#links)**<br>

## 1. Installation Instructions (Composer installation)
Add Github repository to composer config

	composer config repositories.cashpresso vcs https://github.com/credi2/plugins-magento-2

then run the composer command in the folder where the composer.json file lies

	composer require limesoda/cashpresso2:dev-master
	
or run the composer command for a specific version:

    composer require limesoda/cashpresso2:v1.1.11

	
and then run 

    composer update

## 2.Configuration

1. Logout your current admin session and then login again.
2. All settings are here: 
    
    ```Magento Admin Menu / Stores / Settings-Configuration / Sales / Payment methods / Cashpresso```
    
   By default the cashpresso Payment Module is not activated. You need to get an API key and Secret Key, which you can find in your cashpresso account.
   Fill the fields API KEY and Secret Key in the magento settings and save your settings. (You can fill other settings also, but don't activate the payment method "Cahspresso" until you have not saved your API Key and Secret Key).
   Now you should receive the information of the settings in your cashpresso account:
   
   ![Step 1](configuration.png)
   
   The option ```Target account``` will be available only, if target accounts exists in your cashpresso account. 
    
3. Options table 
  
   Option | Description | Dependency
   ------ | ----------- | ---
   Account | Only needed, if you want to receive payments to different bank accounts on a per purchase basis. If not specified the purchase is paid to the main bank account. Please contact your account manager for more information on using multiple target bank accounts. Notice: You cannot create, edit or remove accounts in this module. |
   Mode | You can test the payment method "cashpresso" using the test mode. Its recommended to use the test mode at the beginning. |
   Title | This is the title of the payment method on the checkout page |
   Payment from Applicable Countries | You can set the filter to restrict the availability of the payment method "cashpresso" for specific countries. |
   Payment from Specific Countries | If restricted availability is selected in the step before, select here the countries, where the payment method "cashpresso" is available | 
   Instructions | This is the description of the payment method, that appears on the checkout page |
   Product label status | Switch it to YES if you want to show information about cashpresso rates on the product page or on category pages |
   Product label integration | You can choose between "Product level integration" (recommended) and "Static Label Integration". This means you use the cashpresso Javascript or your custom text for the displayed rates. The Static Label Version has several disadvantages: <br> - No detection of returning cashpresso customers <br> - No indication for a successful risk check <br> - Server side calculation of rates is necessary. | Product label integration
   Show checkout button | Show the checkout button on the cashpresso popup, if you selected ```Show checkout button``` for the Product label integration. | Show checkout button, Product label status
   Checkout url | The URL of your checkout page |
   Place to show | The place, where to show the cashpresso rates information | Place to show, Product label status  
   Template | The template for the cashpresso rates if you selected ```Static Label Integration``` for the Product label integration | Product label status, Product label integration
   The timeout for the order | Time in hours to wait for the approvement of the payment from cashpresso, after placing the order. |
   Sign contract text | The text on the success page for the following order approvement |
   Write log | Choose YES, if the api requests should be written to the log files. |
   Sort Order | Sets the order of the payment methods in the list on the checkout page| 

3. Options table

    When you save the configuration, do not forget to clean the cache.
    
## 3. How it works

- customers can calculate automatically their cashpresso rates on a product page. 
- customers can add one or more products to their cart 
- on the checkout, in the payment step, customers can choose "cashpresso" as their payment method and recalculate their rates for the order.
- after a successful purchase, customers receive a success page, where the cashpresso widget is triggered and if they are first time customers, they are asked to start a videocall with cashpresso to approve their account. If they are already registered, cashpresso approves the rate.
- after the approvement cashpresso sends the status of the transaction to your store (success, canceled/timeout). If the status is "canceled/timeout", the order will be canceled automatically. The status "success" will assign the status "in process" to the related order.

## 4. FAQ

 - Why I do not see cashpresso payment method in the list?
 
There could be a few reasons. First check total sum limitation. It should be less than the value in your cashpresso account.
The second reason is that digital products are in the cart. It's not possible to use cashpresso for the digital products.

 - Why I do not see cashpresso price around the product price?
 
Check if the product less than 10 Euro or more than total sum limit in your casspresso account settings. Otherwise check if its a digital product. In these cases cashpresso price is not applied. 
  

## 5. Links
 - [CashPresso API](https://test.cashpresso.com/urlreferral/api/ecommerce/v2?1)
 - [CashPresso](https://www.cashpresso.com/)
 - [Developer contacts](https://www.kawa-commerce.com/kontakt/)

# easyLTI


A simple library to help integrate LTI into webapps. Uses the Blti library with the addition of a saved nonce to help make you tool a simple LTI 1.0 consumer. 



##Using easyLTI

Change the config.php file to match your LTI Key and Secret as well as provide a DB to save the nonces to avoid replay attacks.

In your tool simply require easyLTI.php and then call connectLTI() to be returned an oauth secured object of information passed from the LTI consumer. See exampleProvider.php for sample code.

Db structure will be need to be added before this tool will function. You will need to import LTI.sql into your mySql DB.



##Acknowledgments

Blti library created by Andy Smith and can be found at: https://code.google.com/p/basiclti4wordpress/source/browse/trunk/producer/mu-plugins/IMSBasicLTI/ims-blti/LICENSE.txt?r=2

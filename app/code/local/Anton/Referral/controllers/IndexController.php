<?php
class Anton_Referral_IndexController extends Mage_Core_Controller_Front_Action
{
	
	/*redirect url*/
	public function getrUrl(){
		return Mage::getBaseUrl() . "checkout/cart";
	}	
		
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function getProductsIds(){
    	/* get the cart products */
		$checkoutsession = Mage::getSingleton('checkout/session');
		
		/* get cart products id */
		$productsid = array();
		foreach ($checkoutsession->getQuote()->getAllItems() as $item) {
			$getproductId[] = $item->getProductId();
		}
		return $getproductId;
    }
    
    public function getSubtotal(){
    	/* get the cart subtotal */
		$lastQid = Mage::getSingleton('checkout/session')->getQuoteId();
		$customerQuote = Mage::getModel('sales/quote')->load($lastQid);
		return $subTotal = $customerQuote->getSubtotal();
    }
    
    public function twredirectAction(){
    	
    	$twitter = Mage::getModel('referral/referral')->getTwitterOauth();
		$request_token = $twitter->getRequestToken(Mage::getBlockSingleton('referral/referral')->getResponseUrl('tw'));
		
		/* Save temporary credentials to session. */
		$_SESSION['oauth_token'] = $request_token['oauth_token'];
		$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

    	if($twitter->http_code == 200){
    		$url = Mage::getModel('referral/referral')->getTwUrl();
    		//echo $url;exit;
		    return $this->_redirectUrl($url);
    	} else {
    		Mage::getSingleton('core/session')->addError($this->__('Could not connect to Twitter. Refresh the page or try again later.'));
			return $this->_redirectUrl($this->getrUrl());
    	}
    }
	
	public function twrespondAction(){
		/*if (isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
		  $_SESSION['oauth_status'] = 'oldtoken';
		  echo 'error**'.$_REQUEST['oauth_token'];
		  print_r($_SESSION);
		  exit;
		}*/
		$twitter = Mage::getModel('referral/referral')->getUserTwitterOauth();
		$access_token = $twitter->getAccessToken($_REQUEST['oauth_verifier']);;
		$_SESSION['access_token'] = $access_token;
		
		/* get facebook configuration discount */
		$fb_discount_amount = Mage::getModel('referral/referral')->getFbDiscountamount();
		$fb_order_amount = Mage::getModel('referral/referral')->getFbOrderamount();
		$basemin_order_amount = Mage::helper('core')->currency($fb_order_amount, true, true);
		$basediscount_amount = $fb_discount_amount;
		$fb_discount_amount = Mage::helper('core')->currency($fb_discount_amount, false, false);
		
		$subTotal = $this->getSubtotal();
		if(isset($_SESSION['oauth_token']))unset($_SESSION['oauth_token']);
		if(isset($_SESSION['oauth_token_secret']))unset($_SESSION['oauth_token_secret']);
//print_r($twitter);
//echo $twitter->http_code.'##';
//echo $twitter->http_code . "**" .$fb_discount_amount.'**subTotal=>'.$subTotal.'##order_amount=>'.$fb_order_amount;exit;
		if (200 == $twitter->http_code && $subTotal >= $fb_discount_amount && $subTotal >= $fb_order_amount) {
			/*set the twitter user status*/

			Mage::getModel('referral/referral')->setReferraluser($access_token['user_id']);
			
			/* The user has been verified and the access tokens can be saved for future use */
			//$_SESSION['status'] = 'verified';
			$twitter->post('statuses/update', array('status' => Mage::getModel('referral/referral')->getFbFeedmassage().date(DATE_RFC822)));
			
			Mage::getSingleton('core/session')->addSuccess($this->__('Congratulations! You have redeemed ' . ' ' . Mage::helper('core')->currency($basediscount_amount, true, true) . ' ' . ' for sharing product(s) to your friends.'));
	
			return $this->_redirectUrl($this->getrUrl());
		} else if($subTotal < $fb_order_amount) {
			/*Error message on cart page*/
			Mage::getSingleton('core/session')->addError($this->__('Your order amount should be minimum' . ' ' . $basemin_order_amount . ' ' . 'to get Referral discount!'));
			return $this->_redirectUrl($this->getrUrl());
		} else {
			/*Error message on cart page*/
			Mage::getSingleton('core/session')->addError($this->__('Could not connect to Twitter. Refresh the page or try again later.'));
			return $this->_redirectUrl($this->getrUrl());
		}
		return $this->_redirectUrl($this->getrUrl());
	}
	    
	public function fbrespondAction(){
		
		/* get facebook */
		$facebook = Mage::getModel('referral/referral')->getFacebook();
		$fbuser = $facebook->getUser();
		
		/* get facebook configuration discount */
		$fb_discount_amount = Mage::getModel('referral/referral')->getFbDiscountamount();
		$fb_order_amount = Mage::getModel('referral/referral')->getFbOrderamount();
		$basemin_order_amount = Mage::helper('core')->currency($fb_order_amount, true, true);
		$basediscount_amount = $fb_discount_amount;
		$fb_discount_amount = Mage::helper('core')->currency($fb_discount_amount, false, false);

		$subTotal = $this->getSubtotal();

		if($fbuser && $subTotal >= $fb_discount_amount && $subTotal >= $fb_order_amount){
			$getproductId = self::getProductsIds();
			try {
				foreach($getproductId as $pid){
					$product = Mage::getModel('catalog/product')->load($pid);
					$feedinfo = array(
						'message'		=> Mage::getModel('referral/referral')->getFbFeedmassage(),
						'name'			=> $product->getName(),
						'link'			=> $product->getProductUrl(),
						'picture'		=> $product->getImageUrl(),
						'description'	=> $product->getShortDescription()
					);
					$facebook->api("/$fbuser/feed", 'post', $feedinfo);
				}
				
			} catch (FacebookApiException $e) {
				/* error message */
				Mage::getSingleton('core/session')->addError($this->__('You declined the facebook app permission'));

				return $this->_redirectUrl($this->getrUrl());
			}
			
			/*set the facebook user status*/
			Mage::getModel('referral/referral')->setReferraluser($fbuser);
			/*get the facebook user status*/
			$status = Mage::getModel('referral/referral')->getReferraluserCount($fbuser);
			
			
			if ($status <= 1) {
				/* success message first time */
				Mage::getSingleton('core/session')->addSuccess($this->__('Congratulations! You have redeemed ' . ' ' . Mage::helper('core')->currency($basediscount_amount, true, true) . ' ' . ' for sharing product(s) to your friends.'));

				return $this->_redirectUrl($this->getrUrl());
			} else {
				/* success message more time */
				Mage::getSingleton('core/session')->addSuccess($this->__('Thanks for sharing product(s) to your friends.'));

				return $this->_redirectUrl($this->getrUrl());
			}
			
			return $this->_redirectUrl($this->getrUrl());
		} else {
			/*Error message on cart page*/
			Mage::getSingleton('core/session')->addError($this->__('Your order amount should be minimum' . ' ' . $basemin_order_amount . ' ' . 'to get Referral discount!'));
			return $this->_redirectUrl($this->getrUrl());
		}
	}
	
	public function gprespondAction(){
		
	}
	
}
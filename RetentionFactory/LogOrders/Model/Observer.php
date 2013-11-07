<?php
/**
 * Our class name should follow the directory structure of
 * our Observer.php model, starting from the namespace,
 * replacing directory separators with underscores.
 * i.e. app/code/local/SmashingMagazine/
 *                     LogProductUpdate/Model/Observer.php
 */
class RetentionFactory_LogOrders_Model_Observer
{
    /**
     * Magento passes a Varien_Event_Observer object as
     * the first parameter of dispatched events.
     */
     /*
	  * see: (sales_order_invoice_pay) http://www.magentocommerce.com/boards/v/viewthread/220809/
	  * see: (sales_order_save_commit_after, sales_order_save_after) http://stackoverflow.com/questions/7927618/magento-observer-for-order-state-is-complete
	  * http://stackoverflow.com/questions/14526728/in-the-magento-sales-order-save-commit-after-hook-why-am-i-getting-duplicate-ite
	  * see: (sales_order_place_after or sales_order_payment_place_end) http://www.magentocommerce.com/boards/viewthread/232682/
	  * see: (checkout_submit_all_after) 
	  */
    public function logOrder(Varien_Event_Observer $observer)
    {
    	Mage::log("RetentionFactory Observer Got An Order");
		try {
	        // Retrieve the product being updated from the event observer
	        $order = $observer->getEvent()->getOrder();
			if (isset($order)){
		    	Mage::log("order id is {$order->getId()} and state {$order->getState()} and status {$order->getStatus()} and real id {$order->getRealOrderId()}");
				//I'd really like to look for STATE_COMPLETE, but when that event is fired then $order->getCustomer() is null.  So I have no choice but to use STATE_NEW
				if($order->getState() == Mage_Sales_Model_Order::STATE_NEW){
					$customer = $order->getCustomer();
					if (isset($customer)){
						if ($customer->getId()){
							$customer_id = $customer->getId();
							if ($customer->getEmail()){
								$customer_email = $customer->getEmail();
							} else {
						        foreach ($order->getAddressesCollection() as $address) {
						            if ($address->getEmail()) {
										$customer_email = $address->getEmail();
										break;
						            }
						        }
							}
							$customer_created_at = $customer->getCreatedAt();
						} else {
					        foreach ($order->getAddressesCollection() as $address) {
					            if ($address->getEmail()) {
									$customer_email = $address->getEmail();
									break;
					            }
					        }
							$customer_id = 'guest-'.$customer_email;
							$customer_created_at = null;
						}
						$to_post = array(
									'tracking_site_id'=>'tk2h32',
									'order_lines'=>array(),
									'customer'=>array('email'=>$customer_email,
													'org_id'=>$customer_id,
													'org_created_at'=>$customer_created_at),
									'org_created_at'=>$order->getCreatedAt(),
									'org_id'=>$order->getRealOrderId()
									);
						$items = $order->getAllVisibleItems();
					    foreach($items as $item) {
					        $product = $item->getProduct();
							if (isset($product)){
						    	$line_to_post = array(
						    				'quantity'=>$item->getQtyOrdered(),
						    				'price'=>$item->getPrice(),
						    				'product'=>array('org_id'=>($product->getSku() ? $product->getSku() : $product->getId()),
														'name'=>$product->getName(),
														'org_created_at'=>$product->getCreatedAt()));
								$to_post['order_lines'][] = $line_to_post;
							} else {
								Mage::log("product is null, ignoring");
							}//isset($product)
					    }
						$json = json_encode($to_post);
						Mage::log("Posting JSON: {$json}");
						$this->postJSONToRetentionFactory($json);
					} else {
						Mage::log("customer is null, ignoring");
					}//isset($customer)
				} else {
					Mage::log("state is not ".Mage_Sales_Model_Order::STATE_NEW.", ignoring");
				}//status complete
			} else {
				Mage::log("order is null, ignoring");
			}//isset($order)
		} catch (Exception $e) {
			Mage::log("RetentionFactory Observer Caught An Exception: ".$e->getMessage());
		}		
    }

	private function postJSONToRetentionFactory($json){
		// Create the context for the request
		$context = stream_context_create(array(
		    'http' => array(
		        // http://www.php.net/manual/en/context.http.php
		        'method' => 'POST',
		        'header' => "Content-Type: application/json\r\n",
		        //'header' => "Authorization: {$authToken}\r\nContent-Type: application/json\r\n",
		        'content' => $json
		    )
		));
		
		Mage::Log("sending post");
		// Send the request
		$response = file_get_contents('http://localhost:5000/api/v1/order/track', FALSE, $context);
		Mage::Log("got response ".$response);
		
		// Check for errors
		if($response === FALSE){
		    Mage::Log("Got an error response from retentionfactory.com: ".$response);
		}
		
		// Decode the response
		//$responseData = json_decode($response, TRUE);		
	}
}
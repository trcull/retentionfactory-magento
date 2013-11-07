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
        // Retrieve the product being updated from the event observer
        $order = $observer->getEvent()->getOrder();
    	Mage::log("order id is {$order->getId()} and state {$order->getState()} and status {$order->getStatus()} and real id {$order->getRealOrderId()}");
		$customer = $order->getCustomer();
		$to_post = array('order_lines'=>array(),
					'customer'=>array('email'=>$customer->getEmail(),
									'org_id'=>$customer->getId(),
									'org_created_at'=>$customer->getCreatedAt()),
					'org_created_at'=>$order->getCreatedAt(),
					'org_id'=>$order->getId()
					);
		//if($order->getState() == Mage_Sales_Model_Order::STATE_COMPLETE){
			$items = $order->getAllVisibleItems();
		    foreach($items as $item) {
		        $product = $item->getProduct();
		    	$line_to_post = array(
		    				'quantity'=>$item->getQtyOrdered(),
		    				'price'=>$item->getPrice(),
		    				'product'=>array('org_id'=>$product->getId(),
										'name'=>$product->getName(),
										'org_created_at'=>$product->getCreatedAt()));
				$to_post['order_lines'][] = $line_to_post;
		    }
   		//}
		
		$json = json_encode($to_post);
		Mage::log("Posting JSON: {$json}");
		
    }
}
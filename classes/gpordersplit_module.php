<?php

class GPOrderSplit_Module extends Core_ModuleBase
{
	/**
	 * Creates the module information object
	 * @return Core_ModuleInfo
	 */
	protected function createModuleInfo()
	{
		return new Core_ModuleInfo(
			'Order split',
			'Allows you to split orders from the backend of LemonStand',
			'GreatPotato',
			'http://www.mrld.co'
		);
	}
	
	
	/**
	 * Events
	 * 
	 * @access public
	 */
	public function subscribeEvents()
	{
		// Order splitting button
		Backend::$events->addEvent('shop:onExtendOrderPreviewToolbar', $this, 'order_toolbar');
	
		// Indicate support of invoices
		Backend::$events->addEvent('shop:onInvoiceSystemSupported', $this, 'process_invoice_system_supported');
	
		// Add tab to preview screen to list child orders
		Backend::$events->addEvent('shop:onExtendOrderPreviewTabs', $this, 'order_tab');
	}
	

	/**
	 * Add 'split order' button to preview
	 * 
	 * @access public
	 * @param Backend_Controller $controller
	 * @param Shop_Order $order
	 */
    public function order_toolbar($controller, $order) {
		echo '<div class="separator"></div>';
		$url = url("/gpordersplit/split_order/index/".$order->id);
		echo '<a class="imageLink ungroup_product" href="'.$url.'">Split Order</a>';
    }
    
    
	/**
	 * Indicates the module uses order invoices
	 * 
	 * @access public
	 * @return boolean
	 */
	public function process_invoice_system_supported()
	{
		return false;
	}
	
	
	/**
	 * View child orders if any
	 * 
	 * @access public
	 * @param Shop_Orders $model
	 * @param Shop_Order $order
	 * @return array
	 */
	public function order_tab($model, $order)
	{
		$return = array();
	
		if ( count($order->list_invoices()) > 0 ) {
			$return = array(
				'Invoices' => PATH_APP . '/modules/goordersplit/partials/_invoices.htm'
			);
		}
	
		return $return;
	}

}
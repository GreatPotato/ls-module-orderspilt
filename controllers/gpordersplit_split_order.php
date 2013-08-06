<?php

class GPOrderSplit_Split_Order extends Backend_Controller {

	/**
	 * Indicate implementation
	 *
	 * @access public
	 * @var string
	 */
	public $implement = 'Db_ListBehavior';

	/**
	 * Class of our RMA model
	 *
	 * @access public
	 * @var string
	 */
	public $list_model_class = 'Shop_OrderItem';

	/**
	 * We don't want pagination
	 *
	 * @access public
	 * @var bool
	 */
	public $list_no_pagination = true;

	/**
	 * Filter the results to only select items based on an order ID
	 *
	 * @access public
	 * @var string
	 */
	public $list_custom_prepare_func = 'filterByOrderID';

	/**
	 * Update the URLs
	 *
	 * @access public
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Split order
	 *
	 * @access public
	 * @throws Phpr_ApplicationException
	 */
	public function index($recordId)
	{
		$this->app_page_title = 'Split order';
		$order_id = $this->order_id = $recordId;
		try
		{
			if ( !$order_id )
				throw new Phpr_ApplicationException('Invalid order selected');

			$obj = Shop_Order::create()->find($order_id);

			if ( !$obj )
				throw new Phpr_ApplicationException('Invalid order selected');

			// If we get this far an order has been found
			$this->viewData['order'] = $obj;
		}
		catch (Phpr_ApplicationException $ex)
		{
			$this->handlePageError($ex);
		}
	}

	/**
	 * Confirmation form before splitting orders
	 *
	 * @access public
	 * @throws Phpr_ApplicationException
	 */
	public function index_onLoadSplitOrdersForm()
	{
		try
		{
			$order_item_ids = post('list_ids', array());

			if (!count($order_item_ids))
				throw new Phpr_ApplicationException('Please select order items to split.');

			$this->viewData['order_item_count'] = count($order_item_ids);
		}
		catch (Exception $ex)
		{
			$this->handlePageError($ex);
		}

		$this->renderPartial('split_order_items_form');
	}

	/**
	 * Perform the splitting of orders
	 *
	 * @access public
	 * @throws Phpr_ApplicationException
	 */
	public function index_onSplitOrders($order_id)
	{
		// Running count of how many items we have split
		$items_processed = 0;

		// List of the order items we are splitting
		$order_items_ids = post('list_ids', array());

		// List of Shop_OrderItem s
		$order_items = array();

		// Amount of records changed purely for viewing purposes
		$this->viewData['list_checked_records'] = $order_items_ids;

		$this->viewData['order'] = Shop_Order::create()->find($order_id);


		if($this->viewData['order']->items->count == 1)
			throw new Phpr_ApplicationException('This order does not have enough items to split');

		// Loop IDs and check they all exist
		foreach ( $order_items_ids as $order_item_id) {
			$order_item_id = trim($order_item_id);

			$order_item = null;

			try
			{
				// Find the single order item
				$order_item = Shop_OrderItem::create()->find($order_item_id);

				if (!$order_item)
					throw new Phpr_ApplicationException('Order item with identifier '.$order_item_id.' not found.');

				// If we have found the order item, add it to our array
				$order_items[] = $order_item;

				$items_processed++;
			}
			catch (Exception $ex)
			{
				if (!$order_item)
					Phpr::$session->flash['error'] = $ex->getMessage();
				else
					Phpr::$session->flash['error'] = 'Splitting item "'.$order_item->id.'": '.$ex->getMessage();
				break;
			}
		}

		// We check to make sure ALL are ok (all or nothing)
		if ( count($order_items_ids) == $items_processed ) {
			// Grab the parent order in a sloppy way
			$order = $order_items[0]->parent_order;

			// Create a sub order
			if ( $new_order = $order->create_sub_order($order_items) ) {

				// Loop through and delete order items from old order split from
				foreach ( $order_items as $item ) {
					$order->items->delete($item);
				}

				// Need to update totals for the original order
				$discount = 0;
				$subtotal = 0;
				$total_cost = 0;
				foreach ($order->items as $item)
				{
					$subtotal += $item->subtotal;
					$discount += $item->discount;
					$total_cost += $item->product->cost*$item->quantity;
				}

				$order->total_cost = $total_cost;
				$order->discount = $discount;
				$order->subtotal = $subtotal;
				$order->total = $order->goods_tax + $order->subtotal + $order->shipping_quote + $order->shipping_tax;
				$order->save();

				// Set shipping quote to 0 on the new order and update totals
				$new_order->status_update_datetime = Phpr_Date::userDate(Phpr_DateTime::now());
				$new_order->shipping_quote = 0;
				$new_order->shipping_tax = 0;
				$new_order->total = $new_order->goods_tax + $new_order->subtotal + $new_order->shipping_quote + $new_order->shipping_tax;
				$new_order->save();
			}

		}

		if ( $items_processed ) {
			if ($items_processed > 1)
				Phpr::$session->flash['success'] = $items_processed.' items have successfully been split into a seperate order';
			else
				Phpr::$session->flash['success'] = '1 item has been successfully been split into its own order.';
		}

		Phpr::$response->redirect(url('shop/orders/'));
	}

	/**
	 * Filter by Order ID
	 *
	 * @access public
	 * @param Shop_OrderItem $model
	 * @return Shop_Order
	 */
	public function filterByOrderID($model, $options)
	{
		$order_id = (int) preg_replace('/[^0-9]/', '', Phpr::$request->getCurrentUri());

		$model->where('shop_order_id = ?', $order_id);

		return $model;
	}
}

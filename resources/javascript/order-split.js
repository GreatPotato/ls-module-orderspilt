/**
 * Load prompt to split order items
 */
function tdordersplit_split_selected()
{
	if (!tdordersplit_order_items_selected())
	{
		alert('Please select items to split.');
		return false;
	}
	
	new PopupForm('index_onLoadSplitOrdersForm', {
		ajaxFields: $('listTDOrderSplit_Split_Order_index_list_body').getForm()
	});

	return false;
}

/**
 * Get selected order items
 */
function tdordersplit_order_items_selected()
{
	return $('listTDOrderSplit_Split_Order_index_list_body').getElements('tr td.checkbox input').some(function(element) {
		return element.checked;
	});
}

<?php
namespace BearClaw\Warehousing;

class PurchaseOrderService
{
	
	const CCCREDENTIALS = 'interview-test@cartoncloud.com.au:test123456';
	const CCAPIURLSTART = 'https://api.cartoncloud.com.au/CartonCloud_Demo/PurchaseOrders/';
	const CCAPIURLFINISH = '?version=5&associated=true';
	const ERROR = 'ERROR';
	const WEIGHTPRODUCTS = [1,3];
	const VOLUMEPRODUCTS = [2];

	/**
	 * Retrieve and caclulate all purchase orders
	 *
     * @param array $ids
     *
     * @return array $response
     */
	public function calculateTotals(array $ids) {
		$productTotals = [];
		$purchaseOrders = $this->getOrders($ids);

		foreach($purchaseOrders as $purchaseOrder) {
			if($purchaseOrder->info == self::ERROR){
				// Skip, no data was available for order
				continue;
			}
			$purchaseOrderProducts =  $purchaseOrder->data->PurchaseOrderProduct;
			foreach ($purchaseOrderProducts as $purchaseOrderProduct) {

				if(in_array($purchaseOrderProduct->product_type_id, self::WEIGHTPRODUCTS)) {
					$unitAmount = $purchaseOrderProduct->unit_quantity_initial * $purchaseOrderProduct->Product->weight;
					$this->calculateProductUnitAmount($purchaseOrderProduct->product_type_id, $unitAmount, $productTotals);
				} else if (in_array($purchaseOrderProduct->product_type_id, self::VOLUMEPRODUCTS)) {
					$unitAmount = $purchaseOrderProduct->unit_quantity_initial * $purchaseOrderProduct->Product->volume;
					$this->calculateProductUnitAmount($purchaseOrderProduct->product_type_id, $unitAmount, $productTotals);
				} else {
					// Skip, unknown product type
				}
			}
		}

		$response = $this->formatData($productTotals);
		return $response;
	}

	/**
	 * Retrieve purchase orders from Carton Cloud API
	 *
     * @param array $ids
     *
     * @return array $responses
     */
	public function getOrders(array $ids) {
		$responses = [];
		$credentials = base64_encode(self::CCCREDENTIALS);

		// Start all the request calls asynchronously
		foreach ($ids as $id) {
			$url = self::CCAPIURLSTART . $id . self::CCAPIURLFINISH;
			$opts = array(
			  'http'=>array(
			    'method'=>"GET",
			    'header' => "Authorization: Basic " . $credentials                 
			  )
			);

			$context = stream_context_create($opts);
			$contents = @file_get_contents($url, false, $context);

			if($contents === FALSE) {
				$data = new \stdClass();
				$data->info = self::ERROR;
				$responses[] = $data;
			} else {
				$data = json_decode($contents);
				$responses[] = $data;
			}
			
		}
		return $responses;
	}

	/**
	 * @param int $productTypeId
	 * @param float $unitAmount
     * @param array &$totals
     */
	public function calculateProductUnitAmount(int $productTypeId, float $unitAmount, array &$productTotals) {
		if(!isset($productTotals[$productTypeId])){
	        $productTotals[$productTypeId] = $unitAmount;
	    } else {
	    	$productTotals[$productTypeId] += $unitAmount;
	    }
	}

	/**
     * @param array $totals
     *
     * @return array $response
     */
	public function formatData(array $totals) {
		$response = [];
		foreach ($totals as $id => $value) {
			$response[$i]['product_type_id'] = $id;
			$response[$i++]['total'] = $value;
		}
		return $response;
	}
}
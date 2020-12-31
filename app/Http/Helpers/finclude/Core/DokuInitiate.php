<?php
/**
 * Initiate Doku data's
 */
namespace App\Http\Helpers\finclude\Core;
class DokuInitiate {
	 
	//local
	// live ubah menjadi https://pay.doku.com
	// mallid = "1554"
	const prePaymentUrl = 'http://staging.doku.com/api/payment/PrePayment';
	const paymentUrl = 'http://staging.doku.com/api/payment/paymentMip';
	const directPaymentUrl = 'http://staging.doku.com/api/payment/PaymentMIPDirect';
	const generateCodeUrl = 'https://staging.doku.com/api/payment/DoGeneratePaycodeVA';
	const redirectPaymentUrl = 'http://staging.doku.com/api/payment/doInitiatePayment';
	const captureUrl = 'http://staging.doku.com/api/payment/DoCapture';

	public static $sharedKey = 'fh7Au8FwUL73'; //doku's merchant unique key
	public static $mallId= "3443"; //doku's merchant id
}



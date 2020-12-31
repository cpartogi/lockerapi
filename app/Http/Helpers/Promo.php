<?php
/**
 * Created by PhpStorm.
 * User: arief
 * Date: 25/08/2017
 * Time: 14.19
 */

namespace App\Http\Helpers;


use App\Campaign;

class Promo
{
    /**
     * Check Promo
     * @param string $phone
     * @param  string $promoType
     * @param array $param
     * @return \stdClass
     */
    public static function checkPromo($phone = '',$promoType='potongan',$param=[]){
        $response = new \stdClass();
        $response->isSuccess = false;
        $response->errorMsg = null;
        $response->totalPrice = 0;
        $response->totalDiscount = 0;
        $response->campaignName = null;

        if (empty($phone)){
            $response->errorMsg = 'Empty Phone';
            return $response;
        }

        $param['cust_phone'] = $phone;
        $param['user_phone'] = $phone;
        $issetPromoCode = 0;
        if (!empty($param['promo_code'])) $issetPromoCode = 1;

        // get all active campaign, in date start / end, order by priority and date created
        $activeCampaign = Campaign::getActiveCampaign($issetPromoCode,$promoType);
        $isGet = false;
        $campaignData = null;
        if (count($activeCampaign)==0){
            $response->errorMsg = 'No Active Campaign';
            return $response;
        }

        // check only promo code
        if ($issetPromoCode==1){
            // check promo code on table
            $voucherDb = Campaign::getVoucherActive($param['promo_code'],$promoType);
            if (count($voucherDb)>0){
                foreach ($voucherDb as $campaign) {
                    $checkCampaign = Campaign::checkRuleCampaign($campaign->tb_campaign_id,$param);
                    $campaignData = $checkCampaign;
                    if ($checkCampaign->isSuccess==true){
                        $isGet = true;
                        break;
                    }
                }
            } else {
                $errorMsg = 'Promo Code Not Found';
                $response->errorMsg = $errorMsg;
                return $response;
            }
        } else {
            // check rule foreach campaign
            foreach ($activeCampaign as $campaign) {
                $checkCampaign = Campaign::checkRuleCampaign($campaign->id,$param);
                $campaignData = $checkCampaign;
                if ($checkCampaign->isSuccess==true){
                    $isGet = true;
                    break;
                }
            }
        }

        // if not get any campaign
        if ($isGet==false){
            $errorMsg = $campaignData->campaignName.' = '.$campaignData->errorMsg;
            $response->errorMsg = $errorMsg;
            return $response;
        }
        $result = array();
        $response->totalPrice = $campaignData->totalPrice;
        $response->totalDiscount = $campaignData->totalDisc;
        $response->campaignName = $campaignData->campaignName;
        $response->isSuccess = true;
        return $response;
    }

    /**
     * Book Promo
     * @param string $phone
     * @param string $promoType
     * @param string $invoiceId
     * @param string $timeLimit
     * @param array $param
     * @return \stdClass
     */
    public static function bookPromo($phone='',$promoType='potongan',$invoiceId = '', $timeLimit='', $param=[]){
        $response = new \stdClass();
        $response->isSuccess = false;
        $response->errorMsg = null;
        $response->totalPrice = 0;
        $response->totalDiscount = 0;
        $response->campaignName = null;
        $response->timeLimit = null;

        if (empty($phone) || empty($invoiceId)){
            $response->errorMsg = 'Empty Phone or Invoice Id';
            return $response;
        }

        $param['cust_phone'] = $phone;
        $param['user_phone'] = $phone;
        $issetPromoCode = 0;
        if (!empty($param['promo_code'])) $issetPromoCode = 1;

        // get all active campaign, in date start / end, order by priority and date created
        $activeCampaign = Campaign::getActiveCampaign($issetPromoCode,$promoType);
        if (count($activeCampaign)==0){
            $response->errorMsg = 'Campaign Not Available';
            return $response;
        }
        $isGet = false;
        $campaignData = null;
        // check only promo code
        if ($issetPromoCode==1){
            // check promo code on table
            $voucherDb = Campaign::getVoucherActive($param['promo_code'],$promoType);
            if (count($voucherDb)>0){
                foreach ($voucherDb as $campaign) {
                    $checkCampaign = Campaign::checkRuleCampaign($campaign->tb_campaign_id,$param);
                    $campaignData = $checkCampaign;
                    if ($checkCampaign->isSuccess==true){
                        $isGet = true;
                        break;
                    }
                }
            } else {
                $errorMsg = 'Promo Code Not Found';
                $response->errorMsg = $errorMsg;
                return $response;
            }
        } else {
            // check rule foreach campaign
            foreach ($activeCampaign as $campaign) {
                $checkCampaign = Campaign::checkRuleCampaign($campaign->id,$param);
                $campaignData = $checkCampaign;
                if ($checkCampaign->isSuccess==true){
                    $isGet = true;
                    break;
                }
            }
        }

        // if not get any campaign
        if ($isGet==false){
            $errorMsg = $campaignData->campaignName.' = '.$campaignData->errorMsg;
            $response->errorMsg = $errorMsg;
            return $response;
        }

        // book campaign
        $bookCampaign = Campaign::bookCampaignUsage($campaignData,$invoiceId,$phone,$timeLimit);
        if (!$bookCampaign->isSuccess){
            $response->errorMsg = 'Failed to Book Promo';
            return $response;
        }

        $response->totalPrice = $campaignData->totalPrice;
        $response->totalDiscount = $campaignData->totalDisc;
        $response->campaignName  = $campaignData->campaignName;
        $response->timeLimit = $bookCampaign->timeLimit;
        $response->isSuccess = true;
        return $response;
    }

    /**
     * Claim Promo
     * @param $phone
     * @param $invoiceId
     * @return \stdClass
     */
    public static function claimPromo($phone,$invoiceId){
        $response = new \stdClass();
        $response->isSuccess = false;
        $response->errorMsg = null;

        $claimCampaign = Campaign::claimCampaignUsage($invoiceId,$phone);
        if ($claimCampaign->isSuccess == false){
            $response->errorMsg = $claimCampaign->errorMsg;
            return $response;
        }
        $result = array();
        $response->isSuccess = true;
        return $response;
    }
}
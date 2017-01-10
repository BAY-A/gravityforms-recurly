<?php

class Recurly_UniqueCouponCodeList extends Recurly_Pager
{

  public static function get($couponCode, $params = null, $client = null) {
    $uri = self::_uriWithParams(Recurly_Client::PATH_COUPONS . '/' . rawurlencode($couponCode) . Recurly_Client::PATH_UNIQUE_COUPONS, $params);
    return new self($uri, $client);
  }

  protected function getNodeName() {
    return 'unique_coupon_codes';
  }
}

<?php

// It's not clear to me whether or not Punycode means that hostnames
// do not have canonical forms anymore. As far as I can tell, it's
// not a problem (punycoding should be identity when no Unicode
// points are involved), but I'm not 100% sure
class HTMLPurifier_URIFilter_HostWhitelist extends HTMLPurifier_URIFilter
{
    public $name = 'HostWhitelist';
    protected $whitelist = array();
    public function prepare($config) {
        $this->whitelist = $config->get('URI.HostWhitelist');
        return true;
    }
    public function filter(&$uri, $config, $context) {
        $token = $context->get('CurrentToken', true);
        $parent_token = $context->get('ParentToken',true);
//        print_r($parent_token);
        if($token){
            if(!in_array($token->name,array('embed','object'))) {
                if($token->name=='param' && $parent_token->name=='object'){
                    return true;
                }
                foreach($this->whitelist as $whitelisted_host_fragment) {
                    if ($uri->host == $whitelisted_host_fragment ) {
                        return true;
                    }
                }
            }
            else {
                return true;
            }
        }
        return false;
    }
}

// vim: et sw=4 sts=4

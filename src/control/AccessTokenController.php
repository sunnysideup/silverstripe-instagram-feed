<?php

class AccessTokenController extends Page_Controller
{
    private static $url_segment = 'update-access-token';


    public function init()
    {
        parent::init();
    }

    /**
     * called when no other action is called
     */
    public function index($request)
    {
        $devTask = Injector::inst()->get('UpdateInstagramAccessTokenTask');
        $response = $devTask->run($request);
        return $response;
    }

}

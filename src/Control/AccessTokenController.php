<?php

namespace Sunnysideup\InstagramFeed\Control;



use SilverStripe\Core\Injector\Injector;
use Sunnysideup\InstagramFeed\Tasks\UpdateInstagramAccessTokenTask;
use PageController;



class AccessTokenController extends PageController
{
    private static $url_segment = 'update-access-token';



/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * OLD:     public function init() (ignore case)
  * NEW:     protected function init() (COMPLEX)
  * EXP: Controller init functions are now protected  please check that is a controller.
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
    protected function init()
    {
        parent::init();
    }

    /**
     * called when no other action is called
     */
    public function index($request)
    {
        $devTask = Injector::inst()->get(UpdateInstagramAccessTokenTask::class);
        $response = $devTask->run($request);
        return $response;
    }

}


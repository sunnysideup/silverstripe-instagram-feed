<?php

//useful links:
//https://www.instagram.com/developer/authentication/
//https://stackoverflow.com/questions/39142374/request-instagram-api-acces-token-with-curl
//https://github.com/adrianengine/jquery-spectragram/wiki/How-to-get-Instagram-API-access-token-and-fix-your-broken-feed
//https://developers.facebook.com/docs/instagram-basic-display-api/reference/refresh_access_token


class UpdateInstagramAccessTokenTask extends BuildTask
{
    protected $title = "Instgram Access Token Expiry";

    protected $description = "Updates or refreshes and access token for the Instagram Basic Display API";

    private static $client_id = '';

    private static $client_secret = '';

    protected $redirectURI = 'update-access-token';

    public function run($request)
    {
        $returnMessage = 'Site config has been edited in the last ten minutes';
        $baseURL = Director::absoluteBaseURL();
        $this->redirectURI = $baseURL . $this->redirectURI;

        $siteConfig = SiteConfig::current_site_config();
        $lastEdited = strtotime($siteConfig->LastEdited);
        $tenMinutesAgo = time() - 600;

        $code = $request->getVar('code');
        //only do this if the SiteConfig hasn't been edited in the last 10 minutes or the code var is set
        if($code || $lastEdited < $tenMinutesAgo){
            $accessToken = '';
            if(is_null($code)){
                $accessToken = $this->refreshAccessToken();
                $returnMessage = 'Attempting to refresh access token';
            }
            else {
                $accessToken = $this->createAccessToken($code);
                $returnMessage = 'Attempting to create access token';
            }
            if($accessToken){
                $siteConfig->InstagramAccessToken = $accessToken;
                $returnMessage .= ' successful.';
            }
            $siteConfig->write(false, false, true);
        }

        echo $returnMessage;
        return $returnMessage;
    }

    /* gets a short live access token (only lasts one hour) */
    public function createAccessToken($code){
        $url = "https://api.instagram.com/oauth/access_token";
        
        $params = [
            'client_id'                =>     Config::inst()->get(self::class, 'client_id'),
            'client_secret'            =>     Config::inst()->get(self::class, 'client_secret'),
            'grant_type'               =>     'authorization_code',
            'redirect_uri'             =>     $this->redirectURI,
            'code'                     =>     $code
        ];

        $curl = curl_init($url);
        curl_setopt($curl,CURLOPT_POST,true);
        curl_setopt($curl,CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($result);

        if(isset($result->access_token)){
            return $this->exchangeForLongLivedToken($result->access_token);
        }
        else {
            return false;
        }
    }

    /*
     * https://developers.facebook.com/docs/instagram-basic-display-api/reference/access_token
     */
    public function exchangeForLongLivedToken($accessToken){
        $url = "https://graph.instagram.com/access_token?";

        $params = [
            'grant_type'               =>     'ig_exchange_token',
            'client_secret'            =>     Config::inst()->get(self::class, 'client_secret'),
            'access_token'             =>     $accessToken
        ];

        $url = $url . http_build_query($params);
 
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($result);

        
        if(isset($result->access_token)){
            return $result->access_token;
        }
        else if(isset($result->error)){
            $this->sendAdminErrorEmail($result->error);
            return false;
        }
        else {
            return false;
        }

    }


    public function refreshAccessToken(){
        $url = "https://graph.instagram.com/refresh_access_token?";

        $siteConfig = SiteConfig::current_site_config();

        if(!$siteConfig->InstagramAccessToken){
            user_error('There is currently no Instgram Access Token availble in the settings, a token must be created before it can be refreshed.');
        }

        $params = [
            'grant_type'               =>     'ig_refresh_token',
            'access_token'             =>     $siteConfig->InstagramAccessToken
        ];

        $url = $url . http_build_query($params);
 
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($result);

        
        if(isset($result->access_token)){
            return $result->access_token;
        }
        else if(isset($result->error)){
            $this->sendAdminErrorEmail($result->error);
            return false;
        }
        else {
            return false;
        }

    }

    public function sendAdminErrorEmail($errorObj){
        $from = Config::inst()->get('Email', 'admin_email');
        $to = Config::inst()->get('Email', 'important_error_email');

        $email = new Email(
            $from,
            $to,
            'Refreshing Instagram Access Token has Failed!'
        );

        $message = 'There was an error refreshing the access token:<br>';

        foreach ($errorObj as $key => $value) {
            $message .= $key . ' => ' . $value . '<br>';
        }

        $email->setBody(
            $message
        );

        $email->send();
    }

}

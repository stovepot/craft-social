<?php
namespace Craft;

use Exception;
use Facebook\FacebookSession;
use Facebook\FacebookRequest;

class Social_FacebookService extends BaseApplicationComponent
{
    private $session = null;

    private function getFacebookSession()
    {
        $settings = craft()->plugins->getPlugin('social')->getSettings();

        if (!$settings->facebook_app_id || !$settings->facebook_app_secret) {
            return false;
        }

        FacebookSession::setDefaultApplication(
            $settings->facebook_app_id,
            $settings->facebook_app_secret
        );

        $response = file_get_contents(
            'https://graph.facebook.com/oauth/access_token?'
            . http_build_query([
                'client_id' => $settings->facebook_app_id,
                'client_secret' => $settings->facebook_app_secret,
                'grant_type' => 'client_credentials'
            ])
		);

        if (strpos($response, 'access_token=') !== 0) {
            throw new Exception('Facebook authentication failed.');
        }

        $token = substr($response, strlen('access_token='));

        $this->session = new FacebookSession($token);

        return $this->session;
    }

    public function findPosts($user_id='160848247281909')
    {
        if ($user_id === null) {
            //TODO make a setting for default facebook user ID
        }

        $session = $this->getFacebookSession();

        if ($session === false) {
            return [];
        }

        $request = new FacebookRequest(
            $session,
            'GET',
            "/$user_id/posts"
        );
        $response = $request->execute();

        $graph_objects = $response->getGraphObjectList();

        $posts = [];

        $f = 'http://www.facebook.com/';

        foreach ($graph_objects as $graph_object) {
            $native = $graph_object->asArray();
            $posts[] = [
                'network'     => 'Facebook',
                'message'     => $native['message'],
                'link'        => isset($native['link']) ? $native['link'] : ($f . $native['id']),
                'picture'     => isset($native['picture']) ? $native['picture'] : false,
                'author'      => $native['from']->name,
                'author_link' => $f . $native['from']->id,
                'created'     => strtotime($native['created_time']),

                'print_r'     => print_r($native, true),
                'native'      => $native
            ];
        }

        return $posts;
    }
}

https://www.facebook.com/WSI.WeatherServicesInternational/posts/948094638557262

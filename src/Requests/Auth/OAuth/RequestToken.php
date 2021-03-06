<?php

namespace Atlassian\JiraRest\Requests\Auth\OAuth;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Atlassian\JiraRest\Requests\AbstractRequest;

class RequestToken extends AbstractRequest
{

    protected $skipAuthentication = true;

    /**
     * Get the resource to call
     *
     * @return string
     */
    public function getResource()
    {
        return 'oauth/request-token?oauth_callback=' . route('oauth.callback');
    }

    /**
     * Get the Api to call agains
     *
     * @return string
     */
    public function getApi()
    {
        return 'plugins/servlet';
    }

    public function beforeClientCreate($options)
    {
        $stack = HandlerStack::create();

        $middleware = new Oauth1([
            'consumer_key'     => config('atlassian.jira-rest.auth.oauth.consumer_key'),
            'consumer_secret'  => config('atlassian.jira-rest.auth.oauth.consumer_secret'),
            'token' => null,
            'token_secret' => null,
            'private_key_file' => config('atlassian.jira-rest.auth.oauth.private_key'),
            'private_key_passphrase' => config('atlassian.jira-rest.auth.oauth.private_key_passphrase'),
            'signature_method' => Oauth1::SIGNATURE_METHOD_RSA,
        ]);
        $stack->push($middleware);

        $options['handler'] = $stack;
        $options['auth'] = 'oauth';

        return $options;
    }

    public function handleResponse($response)
    {
        $token = [];
        parse_str($response, $token);

        if (empty($token)) {
            throw new \Exception("An error occurred while requesting oauth token credentials");
        }
        session(['oauth_token' => $token]);

        return redirect()->to(config('atlassian.jira-rest.host') . '/plugins/servlet/oauth/authorize?oauth_token=' . $token['oauth_token']);
    }

    /**
     * Get the available methods
     * The request will throw an exception if a method is called that is not available
     *
     * @return array
     */
    public function getAvailableMethods()
    {
        return [
            'post'
        ];
    }

}
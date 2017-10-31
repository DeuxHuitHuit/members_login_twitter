<?php

require_once(TOOLKIT . '/class.event.php');
require_once(EXTENSIONS . '/members_login_twitter/extension.driver.php');

class eventmembers_twitter_login extends Event
{
    public static function about()
    {
        return array(
            'name' => extension_members_login_twitter::EXT_NAME,
            'author' => array(
                'name' => 'Deux Huit Huit',
                'website' => 'https://deuxhuithuit.com/',
                'email' => 'open-source@deuxhuithuit.com',
            ),
            'version' => '1.0.0',
            'release-date' => '2017-10-20T20:44:57+00:00',
            'trigger-condition' => 'member-twitter-action[login]'
        );
    }

    public function priority()
    {
        return self::kHIGH;
    }

    public static function getSource()
    {
        return extension_members_login_twitter::EXT_NAME;
    }

    public static function allowEditorToParse()
    {
        return false;
    }

    public function load()
    {
        try {
            $this->trigger();
        } catch (Exception $ex) {
            if (Symphony::Log()) {
                Symphony::Log()->pushExceptionToLog($ex, true);
            }
        }
    }

    public function trigger()
    {
        $TW_CONSUMER_KEY = Symphony::Configuration()->get('key', 'members_twitter_login');
        $TW_CONSUMER_SECRET = Symphony::Configuration()->get('secret', 'members_twitter_login');
        if (is_array($_POST['member-twitter-action']) && isset($_POST['member-twitter-action']['login'])) {
            $_SESSION['OAUTH_SERVICE'] = 'twitter';
            $_SESSION['OAUTH_START_URL'] = $_REQUEST['redirect'];
            $_SESSION['OAUTH_MEMBERS_SECTION_ID'] = General::intval($_REQUEST['members-section-id']);
            $_SESSION['OAUTH_TOKEN'] = null;
            
            $oauth = new OAuth($TW_CONSUMER_KEY, $TW_CONSUMER_SECRET);
            $request_token_response = @$oauth->getRequestToken('https://api.twitter.com/oauth/request_token');
            
            if ($request_token_response === false || empty($request_token_response)) {
                throw new Exception("Failed fetching request token, response was: " . $oauth->getLastResponse());
            } else {
                $_SESSION['OAUTH_TOKEN'] = $request_token_response;
                
                redirect('https://api.twitter.com/oauth/authenticate?oauth_token=' . $request_token_response['oauth_token']);
            }
        } elseif (isset($_POST['oauth_token']) && isset($_POST['oauth_verifier'])) {
            $request_token = $_SESSION['OAUTH_TOKEN'];
            if ($request_token == null || empty($request_token)) {
                throw new Exception('Could not find request token');
            }
            if ($_POST['oauth_token'] != $request_token['oauth_token']) {
                throw new Exception('Token do not match');
            }
            $oauth = new OAuth($TW_CONSUMER_KEY, $TW_CONSUMER_SECRET);
            $oauth->setToken($request_token['oauth_token'], $request_token['oauth_token_secret']);

            $access_token_url = 'https://api.twitter.com/oauth/access_token';
            $access_token_response = @$oauth->getAccessToken($access_token_url, "", $_POST['oauth_verifier'], 'POST');
            
            if ($access_token_response === false || empty($access_token_response)) {
                throw new Exception("Failed fetching request token, response was: " . $oauth->getLastResponse());
            } else {
                $url = 'https://api.twitter.com/1.1/account/verify_credentials.json?include_email=true';
                $oauth->setToken($access_token_response['oauth_token'], $access_token_response['oauth_token_secret']);
                $response = @$oauth->fetch($url);
                if ($response !== false) {
                    $response = json_decode($oauth->getLastResponse());
                    if (is_array($response)) {
                        $response = $response[0];
                    }
                }
                
                if (is_object($response) && isset($response->screen_name)) {
                    $_SESSION['OAUTH_TIMESTAMP'] = time();
                    $_SESSION['OAUTH_SERVICE'] = 'twitter';
                    $_SESSION['ACCESS_TOKEN'] = $access_token_response['oauth_token'];
                    $_SESSION['ACCESS_TOKEN_SECRET'] = $access_token_response['oauth_token_secret'];
                    $_SESSION['OAUTH_USER_ID'] = $access_token_response['user_id'];
                    $_SESSION['OAUTH_USER_EMAIL'] = $response->email;
                    $_SESSION['OAUTH_USER_NAME'] = $response->screen_name;
                    $_SESSION['OAUTH_USER_IMG'] = $response->profile_image_url;
                    $_SESSION['OAUTH_USER_CITY'] = $response->location;
                    $_SESSION['OAUTH_USER_EMAIL'] = null;
                    $edriver = Symphony::ExtensionManager()->create('members');
                    $edriver->setMembersSection($_SESSION['OAUTH_MEMBERS_SECTION_ID']);
                    $femail = $edriver->getField('email');
                    $mdriver = $edriver->getMemberDriver();
                    $email = $response->email;
                    if (!$email) {
                        $email = "twitter" . $response->screen_name . ".com";
                    }
                    $m = $femail->fetchMemberIDBy($email);
                    if (!$m) {
                        $m = new Entry();
                        $m->set('section_id', $_SESSION['OAUTH_MEMBERS_SECTION_ID']);
                        $m->setData($femail->get('id'), array('value' => $email));
                        $twHandle = Symphony::Configuration()->get('twitter-handle-field', 'members_twitter_login');
                        if ($twHandle) {
                            $m->setData(General::intval($twHandle), array(
                                'value' => $response->screen_name,
                            ));
                        }
                        $m->commit();
                        $m = $m->get('id');
                    }
                    $_SESSION['OAUTH_MEMBER_ID'] = $m;
                    $login = $mdriver->login(array(
                        'email' => $email
                    ));
                    if ($login) {
                        redirect($_SESSION['OAUTH_START_URL']);
                    } else {
                        throw new Exception('Twitter login failed');
                    }
                } else {
                    $_SESSION['OAUTH_SERVICE'] = null;
                    $_SESSION['ACCESS_TOKEN'] = null;
                    $_SESSION['OAUTH_TIMESTAMP'] = 0;
                    session_destroy();
                }
            }
        } elseif (is_array($_POST['member-twitter-action']) && isset($_POST['member-twitter-action']['logout']) ||
                  is_array($_POST['member-action']) && isset($_POST['member-action']['logout'])) {
            $_SESSION['OAUTH_SERVICE'] = null;
            $_SESSION['OAUTH_START_URL'] = null;
            $_SESSION['OAUTH_MEMBERS_SECTION_ID'] = null;
            $_SESSION['OAUTH_TOKEN'] = null;
            session_destroy();
        }
    }
}

<?php

use Codebird\Codebird;
use Cmfcmf\OpenWeatherMap;
use Cmfcmf\OpenWeatherMap\Exception as OWMException;

require('vendor/autoload.php');

//Set Consumer Key and Consumer Secret
Codebird::setConsumerKey('consumer-key', 'consumer-secret');

//Create instance of CodeBird
$cb = Codebird::getinstance();

//Set Access Token and Access Key
$cb->setToken('access-token', 'access-key');

//Setup For OpenWeatherMap
//create an OpenWeatherMap Object
$own = new OpenWeatherMap('OWM-key');

//Setting the return format to ARRAY
$cb->setReturnFormat(CODEBIRD_RETURNFORMAT_ARRAY);

//First, create a callback function
function some_callback($message) 
{

    //gets called for every new streamed message
    //gets called with $message =  NULl once per second

    if ($message !== null) {
        print_r($message); 

        //get tweet
        $tweet = strtolower($message['text']);

        //Use Regex to get City from the tweet format
        preg_match('/weather in(.*?)@urweatherbot/', $tweet, $match);
        $city = trim($match[1]);

        if ($city !== "") {

            $json_response->city->name !== null ? tweetWeatherToUser($json_response, $message) : tweetErrorMessage($json_response, $message);   
        }
        
        flush();
    }

    //return false to continue streaming
    return false;
}

// set the streaming callback in Codebird
$cb->setStreamingCallback('some_callback');

//set it to track tweets that contain '@urweatherbot'
$cb->statuses_filter('track=@urweatherbot');

//Start consuming the stream
$userStreamParams = [
    'with' => 'followings', //default for user stream
    'stringify_friend_ids' => true,
    'replies' => 'all',
];

$cb->user($userStreamParams);

//returns weather Data from OpenWeatherMapsAPI
function getCityWeatherData($city)
{
    global $own;
    $units = 'metric';
    $lang = 'en';

    try {
        return $own->getWeather($city, $units, $lang);

    } catch(OWMException $e) {
        print 'OpenWeatherMapException: '.$e->getMessage().' (Code: '.$e->getCode().').';
    } catch(\Exception $e) {
        print 'General Exception: '.$e->getMessage().' (Code: '.$e->getCode().').';
    }

}

function tweetWeatherToUser($json_response, $message)
{
    global $cb;
    $screen_name = $message['user']['screen_name'];
    $sender_id = $message['id']; 

    $city_name = $json_response->city->name;
    $weather_description = $json_response->weather->description;
    $current_temperature = $json_response->temperature->now;


    $current_temperature_decoded = html_entity_decode($current_temperature, 0, 'UTF-8');
    $weather_description = ucwords($weather_description);

    $params = [
        'status' => '@'.$screen_name." Weather in $city_name is $weather_description. Current Temperature is $current_temperature_decoded",
        'in_reply_to_status_id' => $sender_id,
    ];

    //tweet back at the user
    $response = $cb->statuses_update($params);

}

function tweetErrorMessage($json_response, $message)
{
    global $cb;
    $screen_name = $message['user']['screen_name'];
    $sender_id = $message['id'];

    $emoji = html_entity_decode("&#x1F640;", 0, 'UTF-8');

    $params = [
        'status' => "@$screen_name oops! Make sure you enter a city that exists. $emoji",
        'in_reply_to_status_id' => $sender_id
    ];

    $cb->statuses_update($params);

}

